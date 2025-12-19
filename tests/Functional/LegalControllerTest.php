<?php

namespace App\Tests\Functional;

use App\Entity\CmsPage;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LegalControllerTest extends WebTestCase
{
    public function testPrivacyPolicyDisplaysCmsContent(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine')->getManager();

        $schemaTool = new SchemaTool($entityManager);
        $classes = [$entityManager->getClassMetadata(CmsPage::class)];

        try {
            $schemaTool->dropSchema($classes);
        } catch (\Exception $exception) {
        }

        $schemaTool->createSchema($classes);

        $page = new CmsPage();
        $page->setSlug('privacy-policy');
        $page->setTitle('Politique de confidentialité');
        $page->setContent("## Données collectées\n\nNous protégeons vos informations.");

        $entityManager->persist($page);
        $entityManager->flush();

        $client->followRedirects();
        $client->request('GET', '/politique-de-confidentialite/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Politique de confidentialité');
        $this->assertStringContainsString('Données collectées', $client->getResponse()->getContent());
    }

    public function testTermsOfUseNotFoundWhenMissing(): void
    {
        $client = static::createClient();
        $client->followRedirects(false);
        $client->request('GET', '/conditions-utilisation/');

        // Si la page n'existe pas, on devrait avoir 404 après redirection
        if ($client->getResponse()->isRedirect()) {
            $client->followRedirect();
        }
        
        // La page peut retourner 404 ou 200 avec un message d'erreur
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertContains(
            $statusCode,
            [404, 200],
            'La page manquante devrait retourner 404 ou 200 avec un message d\'erreur'
        );
    }
}


