<?php

namespace App\Tests\Functional\Admin;

use App\Entity\CmsPage;
use App\Entity\User;
use App\Repository\CmsPageRepository;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CmsControllerTest extends WebTestCase
{
    public function testEditRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/cms/privacy-policy');

        $this->assertResponseRedirects('/login');
    }

    public function testAdminCanUpdateCmsPage(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $schemaTool = new SchemaTool($entityManager);
        $classes = [
            $entityManager->getClassMetadata(CmsPage::class),
            $entityManager->getClassMetadata(\App\Entity\User::class),
        ];

        try {
            $schemaTool->dropSchema($classes);
        } catch (\Exception $exception) {
            // Ignore si le schéma n'existe pas encore
        }

        $schemaTool->createSchema($classes);
        $passwordHasher = $container->get('security.user_password_hasher');

        $admin = new User();
        $admin->setEmail(sprintf('admin-cms-%s@example.com', uniqid()));
        $admin->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $admin->setPassword($passwordHasher->hashPassword($admin, 'SecurePass!23'));

        $entityManager->persist($admin);
        $entityManager->flush();

        $client->loginUser($admin);

        $token = $container->get('security.token_storage')->getToken();
        $this->assertNotNull($token, 'Le token de sécurité doit être présent après authentification.');
        $this->assertContains('ROLE_ADMIN', $token->getRoleNames(), 'Le token doit contenir le rôle administrateur.');

        $crawler = $client->request('GET', '/admin/cms/privacy-policy');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[name="cms_page"]');

        $form = $crawler->selectButton('Enregistrer')->form([
            'cms_page[title]' => 'Politique de confidentialité mise à jour',
            'cms_page[content]' => "## Politique\n\nContenu de test pour la politique de confidentialité.",
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/admin/cms/privacy-policy');

        $client->followRedirect();

        /** @var CmsPageRepository $repository */
        $repository = $container->get(CmsPageRepository::class);
        $cmsPage = $repository->findOneBySlug('privacy-policy');

        $this->assertNotNull($cmsPage);
        $this->assertSame('Politique de confidentialité mise à jour', $cmsPage->getTitle());
        $this->assertStringContainsString('Contenu de test', (string) $cmsPage->getContent());
    }
}


