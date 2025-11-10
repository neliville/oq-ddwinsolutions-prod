<?php

namespace App\Tests\Functional\Admin;

use App\Entity\CmsPage;
use App\Tests\Functional\FixturesTrait;
use App\Tests\TestCase\WebTestCaseWithDatabase;

class CmsControllerTest extends WebTestCaseWithDatabase
{
    use FixturesTrait;

    public function testEditRequiresAuthentication(): void
    {
        $this->client->request('GET', '/admin/cms/privacy-policy');

        $this->assertResponseRedirects('/login');
    }

    public function testAdminCanUpdateCmsPage(): void
    {
        $entityManager = $this->entityManager;
        $entityManager->createQuery('DELETE FROM App\\Entity\\CmsPage c')->execute();
        $entityManager->createQuery('DELETE FROM App\\Entity\\User u')->execute();
        $this->loadAdminUser($entityManager);

        $cmsPage = new CmsPage();
        $cmsPage->setTitle('Politique de confidentialité');
        $cmsPage->setSlug('privacy-policy');
        $cmsPage->setContent('## Ancien contenu');
        $entityManager->persist($cmsPage);
        $entityManager->flush();

        $this->client->loginUser($this->getAdminUser($entityManager));
        $crawler = $this->client->request('GET', '/admin/cms/privacy-policy');

        $form = $crawler->selectButton('Enregistrer')->form();
        $form['cms_page[title]'] = 'Politique de confidentialité mise à jour';
        $form['cms_page[content]'] = '## Nouveau contenu';

        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/cms/privacy-policy');
        $this->client->followRedirect();

        $updatedPage = $entityManager->getRepository(CmsPage::class)->findOneBy(['slug' => 'privacy-policy']);
        $this->assertSame('Politique de confidentialité mise à jour', $updatedPage->getTitle());
        $this->assertSame('## Nouveau contenu', $updatedPage->getContent());
    }
}
