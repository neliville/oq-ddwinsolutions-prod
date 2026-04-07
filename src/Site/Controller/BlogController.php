<?php

namespace App\Site\Controller;

use App\Entity\BlogPost;
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

        // Articles mis en avant (même filtre catégorie que la liste principale)
        $featuredPosts = $this->blogPostRepository->findPublishedFeaturedForBlogIndex($categorySlug, 3);

        // Articles les plus lus (idem)
        $mostViewedPosts = $this->blogPostRepository->findPublishedMostViewedForBlogIndex($categorySlug, 5);

        return $this->render('blog/index.html.twig', [
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

        return $this->render('blog/article.html.twig', [
            'post' => $post,
            'relatedPosts' => $relatedPosts,
            'show_public_navbar' => true,
        ]);
    }
}
