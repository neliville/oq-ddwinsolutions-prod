<?php

declare(strict_types=1);

namespace App\Tests\Functional\Preferences;

use App\Repository\UserPreferencesRepository;
use App\Tests\TestCase\WebTestCaseWithDatabase;

/**
 * Vérifie la chaîne préférences → API branding (utilisée par les exports PDF/JSON client).
 */
final class ExportPreferencesPersistenceTest extends WebTestCaseWithDatabase
{
    public function testSavedExportPreferencesAreReturnedByBrandingApi(): void
    {
        $user = $this->createTestUser('export-prefs-chain-' . uniqid() . '@example.com', 'Test123456!');
        $prefsRepository = static::getContainer()->get(UserPreferencesRepository::class);
        $prefs = $prefsRepository->getOrCreateForUser($user);
        $prefs->setExportDisplayName('TESTBERLIGUE');
        $prefs->setExportJobTitle('Qualification');
        $prefs->setExportCompanyName('DDWIN SOLUTIONS');
        $prefs->setExportPdfFooter('Document interne QHSE');
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/api/user/export-branding');

        $this->assertResponseIsSuccessful();
        $data = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('TESTBERLIGUE', $data['exportDisplayName']);
        self::assertSame('Qualification', $data['exportJobTitle']);
        self::assertSame('DDWIN SOLUTIONS', $data['exportCompanyName']);
        self::assertSame('Document interne QHSE', $data['exportPdfFooter']);
        self::assertSame('TESTBERLIGUE', $data['user']['displayName']);
        self::assertArrayHasKey('system', $data);
        self::assertSame('OUTILS-QUALITÉ', $data['system']['brandName']);
    }
}
