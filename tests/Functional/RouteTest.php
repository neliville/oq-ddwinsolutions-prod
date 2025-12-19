<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels pour toutes les routes publiques
 * Selon la documentation Symfony: https://symfony.com/doc/current/testing.html
 * 
 * Avec DAMADoctrineTestBundle, le schéma de base de données est créé une fois pour toutes
 * et chaque test est isolé dans une transaction qui est rollback après le test.
 */
class RouteTest extends WebTestCase
{
    public function testPublicRouteIsAccessible(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
        $classes = [
            $entityManager->getClassMetadata(\App\Entity\CmsPage::class),
            $entityManager->getClassMetadata(\App\Entity\Category::class),
            $entityManager->getClassMetadata(\App\Entity\BlogPost::class),
        ];

        try {
            $schemaTool->dropSchema($classes);
        } catch (\Exception $exception) {
            // Ignorer si le schéma n'existe pas encore
        }

        $schemaTool->createSchema($classes);

        $cmsRepository = $entityManager->getRepository(\App\Entity\CmsPage::class);
        $requiredPages = [
            'privacy-policy' => 'Politique de confidentialité',
            'terms-of-use' => "Conditions d'utilisation",
            'legal-notice' => 'Mentions légales',
        ];

        foreach ($requiredPages as $slug => $title) {
            if (!$cmsRepository->findOneBy(['slug' => $slug])) {
                $page = new \App\Entity\CmsPage();
                $page->setSlug($slug);
                $page->setTitle($title);
                $page->setContent('## Contenu généré pour les tests');
                $entityManager->persist($page);
            }
        }

        $entityManager->flush();

        $publicRoutes = [
            ['GET', '/'],
            ['GET', '/ishikawa/'],
            ['GET', '/5pourquoi/'],
            ['GET', '/outils/'],
            ['GET', '/contact/'],
            ['GET', '/login'],
            ['GET', '/register'],
            ['GET', '/politique-de-confidentialite/'],
            ['GET', '/mentions-legales/'],
            ['GET', '/conditions-utilisation/'],
            ['GET', '/blog'],
        ];

        // Tester toutes les routes publiques
        // Selon la doc Symfony, SQLite en mémoire persiste pendant toute la durée du test
        $client->followRedirects();
        foreach ($publicRoutes as [$method, $url]) {
            $client->request($method, $url);
            $this->assertResponseIsSuccessful(
                sprintf('La route %s %s devrait être accessible', $method, $url)
            );
        }
    }

    public function testApiRoutesRequireAuthentication(): void
    {
        $client = static::createClient();
        $client->followRedirects(false);
        
        $protectedRoutes = [
            ['GET', '/api/records'],
            ['POST', '/api/ishikawa/save'],
            ['POST', '/api/fivewhy/save'],
        ];

        foreach ($protectedRoutes as [$method, $route]) {
            $client->request($method, $route, [], [], [
                'CONTENT_TYPE' => 'application/json',
            ]);
            $this->assertTrue(
                in_array($client->getResponse()->getStatusCode(), [302, 401], true),
                sprintf('La route %s devrait nécessiter une authentification', $route)
            );
        }
    }
}

