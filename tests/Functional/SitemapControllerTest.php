<?php

namespace App\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SitemapControllerTest extends WebTestCase
{
    public function testSitemapIsAccessible(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $this->ensureSchema($entityManager);
        
        
        $client->request('GET', '/sitemap.xml');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/xml; charset=utf-8');
    }

    public function testSitemapContainsExpectedUrls(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $this->ensureSchema($entityManager);
        
        
        $crawler = $client->request('GET', '/sitemap.xml');

        $this->assertResponseIsSuccessful();

        $xml = $client->getResponse()->getContent();
        $this->assertIsString($xml);

        // Vérifier que le XML est bien formé
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $xml);

        // Vérifier que les routes principales sont présentes (URLs absolues)
        $this->assertStringContainsString('<loc>', $xml);
        $this->assertStringContainsString('http://localhost</loc>', $xml);
        $this->assertStringContainsString('http://localhost/ishikawa', $xml);
        $this->assertStringContainsString('http://localhost/5pourquoi', $xml);
        $this->assertStringContainsString('http://localhost/outils', $xml);
        $this->assertStringContainsString('http://localhost/blog', $xml);
        $this->assertStringContainsString('http://localhost/contact', $xml);

        // Vérifier la structure d'une URL
        $this->assertStringContainsString('<priority>', $xml);
        $this->assertStringContainsString('<changefreq>', $xml);
        $this->assertStringContainsString('<lastmod>', $xml);
    }

    public function testSitemapUrlsAreAbsolute(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $this->ensureSchema($entityManager);
        
        
        $crawler = $client->request('GET', '/sitemap.xml');

        $this->assertResponseIsSuccessful();

        $xml = $client->getResponse()->getContent();
        
        // Vérifier que les URLs sont absolues (commencent par http:// ou https://)
        // Au lieu de chercher directement, on parse le XML
        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        
        $urls = $dom->getElementsByTagName('loc');
        $this->assertGreaterThan(0, $urls->length, 'Le sitemap doit contenir au moins une URL');
        
        foreach ($urls as $url) {
            $urlValue = $url->nodeValue;
            $this->assertStringStartsWith('http://', $urlValue, "L'URL doit être absolue : $urlValue");
        }
    }

    public function testSitemapXmlIsValid(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $this->ensureSchema($entityManager);
        
        
        $crawler = $client->request('GET', '/sitemap.xml');

        $this->assertResponseIsSuccessful();

        $xml = $client->getResponse()->getContent();
        
        // Vérifier que le XML est valide
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $isValid = $dom->loadXML($xml);
        
        $errors = libxml_get_errors();
        libxml_clear_errors();
        
        $this->assertTrue($isValid, 'Le XML du sitemap doit être valide');
        
        if (!empty($errors)) {
            $errorMessages = array_map(fn($error) => $error->message, $errors);
            $this->fail('Erreurs XML détectées : ' . implode(', ', $errorMessages));
        }
    }

    private function ensureSchema(EntityManagerInterface $entityManager): void
    {
        $schemaTool = new SchemaTool($entityManager);
        $classes = [
            $entityManager->getClassMetadata(\App\Entity\Category::class),
            $entityManager->getClassMetadata(\App\Entity\BlogPost::class),
            $entityManager->getClassMetadata(\App\Entity\CmsPage::class),
        ];

        try {
            $schemaTool->dropSchema($classes);
        } catch (\Exception $exception) {
        }

        $schemaTool->createSchema($classes);
    }
}

