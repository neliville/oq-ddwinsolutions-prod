<?php

namespace App\Controller\Admin;

use App\Entity\AdminLog;
use App\Entity\BlogPost;
use App\Form\BlogPostFormType;
use App\Repository\AdminLogRepository;
use App\Repository\BlogPostRepository;
use App\Repository\CategoryRepository;
use App\Repository\TagRepository;
use App\Service\ImageOptimizerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/blog', name: 'app_admin_blog_')]
#[IsGranted('ROLE_ADMIN')]
final class BlogController extends AbstractController
{
    public function __construct(
        private readonly BlogPostRepository $blogPostRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly TagRepository $tagRepository,
        private readonly AdminLogRepository $adminLogRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly SluggerInterface $slugger,
        private readonly ImageOptimizerService $imageOptimizer,
        #[Autowire(param: 'blog_images_directory')] private readonly string $blogImagesDirectory,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $filter = $request->query->get('filter', 'all');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 20;

        $posts = $this->blogPostRepository->findByFilter($filter, $page, $limit);
        $total = $this->blogPostRepository->countByFilter($filter);
        $pages = ceil($total / $limit);

        $publishedCount = $this->blogPostRepository->countByFilter('published');
        $draftCount = $this->blogPostRepository->countByFilter('draft');
        $featuredCount = $this->blogPostRepository->countByFilter('featured');

        $response = $this->render('admin/blog/index.html.twig', [
            'posts' => $posts,
            'currentFilter' => $filter,
            'currentPage' => $page,
            'totalPages' => $pages,
            'total' => $total,
            'publishedCount' => $publishedCount,
            'draftCount' => $draftCount,
            'featuredCount' => $featuredCount,
        ]);
        $response->headers->addCacheControlDirective('no-cache');
        $response->headers->addCacheControlDirective('no-store');
        return $response;
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $post = new BlogPost();
        $form = $this->createForm(BlogPostFormType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('featuredImage')->getData();
            $imageUrl = trim((string) $form->get('featuredImageUrl')->getData());

            if ($imageFile) {
                if (!$this->handleFeaturedImage($post, $imageFile)) {
                    return $this->render('admin/blog/new.html.twig', [
                        'form' => $form,
                        'post' => $post,
                    ]);
                }
            } elseif ($imageUrl !== '') {
                $post->setImage($imageUrl);
            }

            // Générer le slug automatiquement s'il est vide
            if (empty($post->getSlug())) {
                $slug = strtolower($this->slugger->slug($post->getTitle())->toString());
                $post->setSlug($slug);
            }

            // Vérifier l'unicité du slug
            $slugExists = $this->blogPostRepository->findOneBy(['slug' => $post->getSlug()]);
            if ($slugExists) {
                $post->setSlug($post->getSlug() . '-' . time());
            }

            // Mettre à jour les dates
            $post->setCreatedAt(new \DateTimeImmutable());
            $post->setUpdatedAt(new \DateTimeImmutable());

            // Si publié, définir la date de publication
            if ($post->getPublishedAt() === null && $request->request->get('publish_now')) {
                $post->setPublishedAt(new \DateTimeImmutable());
            }

            try {
                $this->entityManager->persist($post);
                $this->entityManager->flush();
            } catch (\Throwable $e) {
                $this->logger->error('Erreur lors de la création de l\'article', ['exception' => $e]);
                $this->addFlash('danger', 'Une erreur est survenue lors de l\'enregistrement. Vérifiez les champs (catégorie, temps de lecture) et réessayez.');
                return $this->render('admin/blog/new.html.twig', [
                    'form' => $form,
                    'post' => $post,
                ]);
            }

            // Logger l'action
            $this->logAction('CREATE', BlogPost::class, $post->getId(), "Article créé : {$post->getTitle()}");

            $this->addFlash('success', 'Article créé avec succès.');

            return $this->redirectToRoute('app_admin_blog_index');
        }

        return $this->render('admin/blog/new.html.twig', [
            'form' => $form,
            'post' => $post,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(BlogPost $post, Request $request): Response
    {
        $form = $this->createForm(BlogPostFormType::class, $post);
        $currentImage = $post->getImage();
        if ($currentImage && (str_starts_with($currentImage, 'http://') || str_starts_with($currentImage, 'https://'))) {
            $form->get('featuredImageUrl')->setData($currentImage);
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('featuredImage')->getData();
            $imageUrl = trim((string) $form->get('featuredImageUrl')->getData());

            if ($imageFile) {
                if (!$this->handleFeaturedImage($post, $imageFile)) {
                    return $this->render('admin/blog/edit.html.twig', [
                        'form' => $form,
                        'post' => $post,
                    ]);
                }
            } elseif ($imageUrl !== '') {
                $post->setImage($imageUrl);
            }

            // Vérifier l'unicité du slug (sauf pour l'article actuel)
            $slugExists = $this->blogPostRepository->findOneBy(['slug' => $post->getSlug()]);
            if ($slugExists && $slugExists->getId() !== $post->getId()) {
                $post->setSlug($post->getSlug() . '-' . time());
            }

            // Mettre à jour la date de modification
            $post->setUpdatedAt(new \DateTimeImmutable());

            // Si publié, définir la date de publication si elle n'existe pas
            if ($post->getPublishedAt() === null && $request->request->get('publish_now')) {
                $post->setPublishedAt(new \DateTimeImmutable());
            }

            try {
                $this->entityManager->flush();
            } catch (\Throwable $e) {
                $this->logger->error('Erreur lors de la mise à jour de l\'article', ['exception' => $e]);
                $this->addFlash('danger', 'Une erreur est survenue lors de l\'enregistrement. Vérifiez les champs et réessayez.');
                return $this->render('admin/blog/edit.html.twig', [
                    'form' => $form,
                    'post' => $post,
                ]);
            }

            // Logger l'action
            $this->logAction('UPDATE', BlogPost::class, $post->getId(), "Article modifié : {$post->getTitle()}");

            $this->addFlash('success', 'Article modifié avec succès.');

            return $this->redirectToRoute('app_admin_blog_index');
        }

        return $this->render('admin/blog/edit.html.twig', [
            'form' => $form,
            'post' => $post,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(BlogPost $post): Response
    {
        return $this->render('admin/blog/show.html.twig', [
            'post' => $post,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(BlogPost $post, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete_' . $post->getId(), $request->request->get('_token'))) {
            $title = $post->getTitle();
            $postId = $post->getId();

            $this->entityManager->remove($post);
            $this->entityManager->flush();

            // Logger l'action
            $this->logAction('DELETE', BlogPost::class, $postId, "Article supprimé : {$title}");

            $this->addFlash('success', 'Article supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_blog_index');
    }

    #[Route('/{id}/publish', name: 'publish', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function publish(BlogPost $post, Request $request): Response
    {
        if ($this->isCsrfTokenValid('publish_' . $post->getId(), $request->request->get('_token'))) {
            if ($post->getPublishedAt() === null) {
                $post->setPublishedAt(new \DateTimeImmutable());
            }
            $this->entityManager->flush();

            // Logger l'action
            $this->logAction('UPDATE', BlogPost::class, $post->getId(), "Article publié : {$post->getTitle()}");

            $this->addFlash('success', 'Article publié avec succès.');
        }

        return $this->redirectToRoute('app_admin_blog_index');
    }

    #[Route('/{id}/unpublish', name: 'unpublish', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function unpublish(BlogPost $post, Request $request): Response
    {
        if ($this->isCsrfTokenValid('unpublish_' . $post->getId(), $request->request->get('_token'))) {
            $post->setPublishedAt(null);
            $this->entityManager->flush();

            // Logger l'action
            $this->logAction('UPDATE', BlogPost::class, $post->getId(), "Article dépublié : {$post->getTitle()}");

            $this->addFlash('success', 'Article dépublié avec succès.');
        }

        return $this->redirectToRoute('app_admin_blog_index');
    }

    private function logAction(string $action, string $entityType, ?int $entityId, string $description): void
    {
        $adminLog = new AdminLog();
        $adminLog->setUser($this->getUser());
        $adminLog->setAction($action);
        $adminLog->setEntityType($entityType);
        $adminLog->setEntityId($entityId);
        $adminLog->setDescription($description);
        $adminLog->setIpAddress($this->getClientIp());

        $this->entityManager->persist($adminLog);
        $this->entityManager->flush();
    }

    private function getClientIp(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return null;
        }

        return $request->getClientIp();
    }

    private function handleFeaturedImage(BlogPost $post, ?UploadedFile $imageFile): bool
    {
        if (!$imageFile) {
            return true;
        }

        if (!is_dir($this->blogImagesDirectory)) {
            if (!@mkdir($this->blogImagesDirectory, 0775, true) && !is_dir($this->blogImagesDirectory)) {
                $this->addFlash('danger', 'Impossible de créer le répertoire des images.');
                return false;
            }
        }

        $newFilename = $this->generateImageFilename($post, $imageFile);

        try {
            $imageFile->move($this->blogImagesDirectory, $newFilename);
        } catch (FileException $exception) {
            $this->addFlash('danger', 'Erreur lors du téléchargement de l\'image. Merci de réessayer.');
            return false;
        }

        if ($post->getImage()) {
            $existingPath = $this->blogImagesDirectory . '/' . basename($post->getImage());
            if (is_file($existingPath)) {
                @unlink($existingPath);
            }
        }

        $imagePath = sprintf('uploads/blog/%s', $newFilename);
        $post->setImage($imagePath);

        // Générer automatiquement les variantes LiipImagine
        try {
            $this->imageOptimizer->generateBlogImageVariants($imagePath);
        } catch (\Exception $e) {
            // Ne pas bloquer si la génération des variantes échoue
            // Les variantes seront générées à la volée lors de l'affichage
        }

        return true;
    }

    private function generateImageFilename(BlogPost $post, UploadedFile $imageFile): string
    {
        $baseName = $post->getTitle() ?: pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $slug = $this->slugger->slug($baseName)->lower();
        $extension = $imageFile->guessExtension() ?: $imageFile->getClientOriginalExtension() ?: 'jpg';

        return sprintf('%s-%s.%s', $slug, uniqid('', true), $extension);
    }

    /**
     * Endpoint API pour l'upload dynamique d'image avec prévisualisation
     */
    #[Route('/upload-image-preview', name: 'upload_image_preview', methods: ['POST'])]
    public function uploadImagePreview(Request $request): JsonResponse
    {
        $imageFile = $request->files->get('image');
        
        if (!$imageFile instanceof UploadedFile) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Aucun fichier image fourni.',
            ], 400);
        }

        // Validation basique
        $allowedMimeTypes = ['image/jpeg', 'image/webp'];
        if (!in_array($imageFile->getMimeType(), $allowedMimeTypes, true)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Format non supporté. Utilisez JPG ou WEBP.',
            ], 400);
        }

        if ($imageFile->getSize() > 4 * 1024 * 1024) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Le fichier est trop volumineux (max 4 Mo).',
            ], 400);
        }

        // Créer un nom de fichier temporaire
        $tempFilename = sprintf('preview-%s.%s', uniqid('', true), $imageFile->guessExtension() ?: 'jpg');
        $tempPath = $this->blogImagesDirectory . '/temp/' . $tempFilename;

        // Créer le dossier temp s'il n'existe pas
        $tempDir = dirname($tempPath);
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        try {
            $imageFile->move(dirname($tempPath), basename($tempPath));
            
            // Chemin relatif depuis public/
            $relativePath = sprintf('uploads/blog/temp/%s', $tempFilename);
            
            // Générer les variantes
            $variants = $this->imageOptimizer->generateBlogImageVariants($relativePath);
            
            // Générer l'URL de prévisualisation
            $previewUrl = $this->imageOptimizer->generateVariant($relativePath, 'card_desktop') 
                ?: $relativePath;

            return new JsonResponse([
                'success' => true,
                'previewUrl' => $previewUrl,
                'originalUrl' => $relativePath,
                'variants' => $variants,
                'tempFilename' => $tempFilename,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur lors du traitement de l\'image : ' . $e->getMessage(),
            ], 500);
        }
    }
}
