<?php

namespace App\Controller;

use App\Entity\BlogPost;
use App\Entity\NewsletterSubscriber;
use App\Form\NewsletterFormType;
use App\Repository\BlogPostRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class BlogController extends AbstractController
{
    public function __construct(
        private readonly BlogPostRepository $blogPostRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/blog', name: 'app_blog_index')]
    public function index(Request $request): Response
    {
        $subscriber = new NewsletterSubscriber();
        $form = $this->createForm(NewsletterFormType::class, $subscriber);

        // Filtres
        $categorySlug = $request->query->get('category');
        $tagSlug = $request->query->get('tag');
        $page = $request->query->getInt('page', 1);
        $limit = 12;

        // Récupérer les articles publiés
        $blogPosts = $this->blogPostRepository->findPublishedByCategory($categorySlug, $page, $limit);
        $totalPosts = $this->blogPostRepository->countPublishedByCategory($categorySlug);
        $totalPages = (int) ceil($totalPosts / $limit);

        // Récupérer toutes les catégories pour les filtres
        $categories = $this->categoryRepository->findAll();

        // Articles mis en avant
        $featuredPosts = $this->blogPostRepository->findByFilter('featured', 1, 3);

        // Articles les plus vus
        $mostViewedPosts = $this->blogPostRepository->findMostViewed(5);

        return $this->render('blog/index.html.twig', [
            'newsletterForm' => $form,
            'blogPosts' => $blogPosts,
            'featuredPosts' => $featuredPosts,
            'mostViewedPosts' => $mostViewedPosts,
            'categories' => $categories,
            'currentCategory' => $categorySlug,
            'currentTag' => $tagSlug,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalPosts' => $totalPosts,
            'show_public_navbar' => true,
        ]);
    }

    #[Route('/blog/{category}/{slug}', name: 'app_blog_article', methods: ['GET'])]
    public function article(string $category, string $slug): Response
    {
        $post = $this->blogPostRepository->findOneByCategoryAndSlug($category, $slug);

        if (!$post) {
            throw new NotFoundHttpException('Article non trouvé');
        }

        // Incrémenter le compteur de vues
        $post->incrementViews();
        $this->entityManager->flush();

        // Articles liés
        $relatedPosts = $this->blogPostRepository->findRelatedPosts($post, 3);

        // Formulaire newsletter
        $subscriber = new NewsletterSubscriber();
        $form = $this->createForm(NewsletterFormType::class, $subscriber);

        return $this->render('blog/article.html.twig', [
            'post' => $post,
            'relatedPosts' => $relatedPosts,
            'newsletterForm' => $form,
            'show_public_navbar' => true,
        ]);
    }
}
