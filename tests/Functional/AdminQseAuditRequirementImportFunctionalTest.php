<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Qse\AuditRequirement;
use App\Entity\Qse\AuditStandard;
use App\Tests\TestCase\WebTestCaseWithDatabase;

final class AdminQseAuditRequirementImportFunctionalTest extends WebTestCaseWithDatabase
{
    public function testImportJsonPreviewAndApply(): void
    {
        $admin = $this->createTestUser('adm-' . uniqid() . '@example.com', 'Test123456!', ['ROLE_ADMIN']);
        $this->client->loginUser($admin);
        $std = $this->entityManager->getRepository(AuditStandard::class)->findOneBy(['code' => 'iso_14001']);
        $this->assertInstanceOf(AuditStandard::class, $std);

        $crawler = $this->client->request('GET', '/admin/qse/import/requirements');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Prévisualiser')->form();
        $jsonPath = dirname(__DIR__, 2) . '/data/fixtures/iso_14001_requirements_sample.json';
        $this->assertFileExists($jsonPath);
        $form['standard_id']->setValue((string) $std->getId());
        $form['import_file']->upload($jsonPath);
        $this->client->submit($form);
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('iso14001_demo', (string) $this->client->getResponse()->getContent());

        $crawler = $this->client->getCrawler();
        $applyForm = $crawler->selectButton('Appliquer en base')->form();
        $this->client->submit($applyForm);
        $this->assertResponseRedirects();

        $this->entityManager->clear();
        $stdReloaded = $this->entityManager->getRepository(AuditStandard::class)->findOneBy(['code' => 'iso_14001']);
        $this->assertInstanceOf(AuditStandard::class, $stdReloaded);
        $count = $this->entityManager->getRepository(AuditRequirement::class)->count(['auditStandard' => $stdReloaded]);
        $this->assertGreaterThanOrEqual(2, $count);
    }

    public function testWorkbookXlsxPreviewIso14001DoesNotRequireLegacyKeyColumn(): void
    {
        $xlsx = dirname(__DIR__, 2) . '/Exigences ISO 9001v2015__en_cours.xlsx';
        if (!is_readable($xlsx)) {
            self::markTestSkipped('Classeur métier absent (non versionné) : ' . $xlsx);
        }

        $admin = $this->createTestUser('adm-wb-' . uniqid() . '@example.com', 'Test123456!', ['ROLE_ADMIN']);
        $this->client->loginUser($admin);
        $std = $this->entityManager->getRepository(AuditStandard::class)->findOneBy(['code' => 'iso_14001']);
        $this->assertInstanceOf(AuditStandard::class, $std);

        $crawler = $this->client->request('GET', '/admin/qse/import/requirements');
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('Prévisualiser')->form();
        $form['standard_id']->setValue((string) $std->getId());
        $form['import_file']->upload($xlsx);
        $this->client->submit($form);
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $html = (string) $this->client->getResponse()->getContent();
        $this->assertStringNotContainsString('legacy_key obligatoire', $html);
    }
}
