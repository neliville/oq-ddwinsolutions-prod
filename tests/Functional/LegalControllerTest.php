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

        $client->request('GET', '/politique-de-confidentialite/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Politique de confidentialité');
        $this->assertStringContainsString('Données collectées', $client->getResponse()->getContent());
    }

    public function testTermsOfUseNotFoundWhenMissing(): void
    {
        $client = static::createClient();
        $client->request('GET', '/conditions-utilisation/');

        $this->assertResponseStatusCodeSame(404);
    }
}


