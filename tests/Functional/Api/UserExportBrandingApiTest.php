<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Export\Dto\ExportSystemBranding;
use App\Repository\UserPreferencesRepository;
use App\Tests\TestCase\WebTestCaseWithDatabase;
use Symfony\Component\HttpFoundation\Response;

final class UserExportBrandingApiTest extends WebTestCaseWithDatabase
{
    public function testExportBrandingRequiresAuthentication(): void
    {
        $this->client->followRedirects(false);
        $this->client->request('GET', '/api/user/export-branding');

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        $this->assertStringContainsString('/login', $this->client->getResponse()->headers->get('Location') ?? '');
    }

    public function testExportBrandingReturnsJsonForAuthenticatedUser(): void
    {
        $user = $this->createTestUser('export-branding-' . uniqid() . '@example.com', 'Test123456!');
        $this->client->loginUser($user);

        $this->client->request('GET', '/api/user/export-branding');

        $this->assertResponseIsSuccessful();
        $ct = $this->client->getResponse()->headers->get('content-type') ?? '';
        $this->assertStringContainsString('application/json', $ct);
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('exportDisplayName', $data);
        $this->assertArrayHasKey('exportPdfFooter', $data);
        $this->assertArrayHasKey('profileDisplayName', $data);
        $this->assertSame($user->getEmail(), $data['profileDisplayName']);
        $this->assertArrayHasKey('system', $data);
        $this->assertSame(ExportSystemBranding::BRAND_NAME, $data['system']['brandName']);
        $this->assertSame(ExportSystemBranding::COPYRIGHT, $data['system']['copyright']);
    }

    public function testExportBrandingReturnsUserBlockWhenPreferencesSet(): void
    {
        $user = $this->createTestUser('export-branding-prefs-' . uniqid() . '@example.com', 'Test123456!');
        $prefsRepository = static::getContainer()->get(UserPreferencesRepository::class);
        $prefs = $prefsRepository->getOrCreateForUser($user);
        $prefs->setExportDisplayName('Nom Export');
        $prefs->setExportPdfFooter('Pied personnalisé');
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/api/user/export-branding');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Nom Export', $data['exportDisplayName']);
        $this->assertSame('Nom Export', $data['user']['displayName']);
        $this->assertSame('Pied personnalisé', $data['user']['pdfFooter']);
    }
}
