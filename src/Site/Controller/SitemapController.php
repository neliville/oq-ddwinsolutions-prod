<?php

namespace App\Site\Controller;

use App\Repository\BlogPostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/sitemap.xml', name: 'app_sitemap_', methods: ['GET'])]
final class SitemapController extends AbstractController
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly BlogPostRepository $blogPostRepository,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(): Response
    {
        // Routes publiques statiques
        $staticRoutes = [];
        $today = date('Y-m-d');
        
        // Routes principales (priorités alignées spec SEO)
        $routesToAdd = [
            ['route' => 'app_home_index', 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['route' => 'app_outils_index', 'priority' => '0.9', 'changefreq' => 'monthly'],
            ['route' => 'app_ishikawa_index', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['route' => 'app_fivewhy_index', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['route' => 'app_qqoqccp_index', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['route' => 'app_amdec_index', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['route' => 'app_eightd_index', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['route' => 'app_pareto_index', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['route' => 'app_blog_index', 'priority' => '0.7', 'changefreq' => 'weekly'],
            ['route' => 'app_contact_index', 'priority' => '0.3', 'changefreq' => 'monthly'],
            ['route' => 'app_legal_politique_confidentialite', 'priority' => '0.5', 'changefreq' => 'yearly'],
            ['route' => 'app_legal_mentions_legales', 'priority' => '0.5', 'changefreq' => 'yearly'],
        ];
        
        foreach ($routesToAdd as $routeConfig) {
            try {
                $url = $this->router->generate($routeConfig['route'], [], UrlGeneratorInterface::ABSOLUTE_URL);
                $staticRoutes[] = [
                    'url' => $url,
                    'priority' => $routeConfig['priority'],
                    'changefreq' => $routeConfig['changefreq'],
                    'lastmod' => $today,
                ];
            } catch (\Exception $e) {
                // Route non disponible, on skip
                continue;
            }
        }

        // Articles de blog publiés
        $blogPosts = $this->blogPostRepository->findPublishedByCategory(null, 1, 1000);
        $blogRoutes = [];
        foreach ($blogPosts as $post) {
            if ($post->getPublishedAt() && $post->getCategory()) {
                try {
                    $url = $this->router->generate('app_blog_article', [
                        'category' => $post->getCategory()->getSlug(),
                        'slug' => $post->getSlug()
                    ], UrlGeneratorInterface::ABSOLUTE_URL);
                    
                    $blogRoutes[] = [
                        'url' => $url,
                        'priority' => '0.6',
                        'changefreq' => 'monthly',
                        'lastmod' => $post->getUpdatedAt() ? $post->getUpdatedAt()->format('Y-m-d') : $post->getPublishedAt()->format('Y-m-d'),
                    ];
                } catch (\Exception $e) {
                    // Route non encore créée, on skip
                    continue;
                }
            }
        }

        // Générer le XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Routes statiques
        foreach ($staticRoutes as $route) {
            $xml .= $this->generateUrlElement($route['url'], $route['priority'], $route['changefreq'], $route['lastmod']);
        }

        // Articles de blog
        foreach ($blogRoutes as $route) {
            $xml .= $this->generateUrlElement($route['url'], $route['priority'], $route['changefreq'], $route['lastmod']);
        }

        $xml .= '</urlset>';

        $response = new Response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        
        // Cache pour 1 heure (le sitemap change peu souvent)
        $response->setPublic();
        $response->setMaxAge(3600);
        $response->setSharedMaxAge(3600);

        return $response;
    }

    private function generateUrlElement(string $url, string $priority, string $changefreq, string $lastmod): string
    {
        // S'assurer que l'URL est valide et absolue
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }
        
        // Normaliser l'URL (supprimer les fragments, s'assurer du protocole)
        // Ne pas supprimer le slash final pour la page d'accueil
        if (preg_match('#^https?://[^/]+$#', $url)) {
            // Page d'accueil, garder le slash final
            $url = rtrim($url, '/') . '/';
        } else {
            $url = rtrim($url, '/');
        }
        
        if (!preg_match('#^https?://#', $url)) {
            return '';
        }
        
        // Valider et formater lastmod (format ISO 8601 ou YYYY-MM-DD)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $lastmod)) {
            $lastmod = date('Y-m-d');
        }
        
        return "  <url>\n" .
               "    <loc>" . htmlspecialchars($url, ENT_XML1 | ENT_COMPAT, 'UTF-8') . "</loc>\n" .
               "    <lastmod>" . htmlspecialchars($lastmod, ENT_XML1 | ENT_COMPAT, 'UTF-8') . "</lastmod>\n" .
               "    <changefreq>" . htmlspecialchars($changefreq, ENT_XML1 | ENT_COMPAT, 'UTF-8') . "</changefreq>\n" .
               "    <priority>" . htmlspecialchars($priority, ENT_XML1 | ENT_COMPAT, 'UTF-8') . "</priority>\n" .
               "  </url>\n";
    }
}
