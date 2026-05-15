<?php

declare(strict_types=1);

namespace App\Tests\Functional\Qse;

use App\Entity\Qse\Audit;
use App\Entity\Qse\AuditStandard;
use App\Repository\UserPreferencesRepository;
use App\Tests\TestCase\WebTestCaseWithDatabase;

final class QseAuditExportBrandingTest extends WebTestCaseWithDatabase
{
    public function testExportXlsxSucceedsWhenPreferencesSet(): void
    {
        $standardRepository = $this->entityManager->getRepository(AuditStandard::class);
        $standard = $standardRepository->findOneByCode('iso_14001');
        self::assertInstanceOf(AuditStandard::class, $standard);

        $user = $this->createTestUser('audit-export-branding-' . uniqid() . '@example.com', 'Test123456!');

        $prefsRepository = static::getContainer()->get(UserPreferencesRepository::class);
        $prefs = $prefsRepository->getOrCreateForUser($user);
        $prefs->setExportDisplayName('TESTBERLIGUE');
        $prefs->setExportJobTitle('Qualification');
        $prefs->setExportCompanyName('DDWIN SOLUTIONS');
        $this->entityManager->flush();

        $audit = new Audit();
        $audit->setOwner($user);
        $audit->setAuditStandard($standard);
        $audit->setAuditedAt(new \DateTimeImmutable('2026-05-01'));
        $audit->setCompanyName('Société audit');
        $this->entityManager->persist($audit);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard/qse/audit/' . $audit->getId() . '/export.xlsx');

        $this->assertResponseIsSuccessful();
        $ct = (string) $this->client->getResponse()->headers->get('Content-Type');
        self::assertStringContainsString('spreadsheetml', $ct);
        $body = (string) $this->client->getResponse()->getContent();
        self::assertStringStartsWith('PK', $body);
    }
}
