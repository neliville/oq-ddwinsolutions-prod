<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Qse\Audit;
use App\Entity\Qse\AuditStandard;
use App\Qse\Import\AuditRequirementsJsonDocumentParser;
use App\Qse\Import\AuditRequirementUpserter;
use App\Repository\Qse\AuditRequirementRepository;
use App\Repository\Qse\AuditStandardRepository;
use App\Tests\TestCase\WebTestCaseWithDatabase;

/**
 * Régression : onglets chapitres ISO 14001 triés comme le 9001 (4 … 10), pas en ordre lexicographique (0, 10, 4 …).
 */
final class QseAuditIso14001ChapterOrderingFunctionalTest extends WebTestCaseWithDatabase
{
    private const FIXTURE = __DIR__ . '/../../data/fixtures/iso_14001.json.json';

    public function testDistinctChaptersAreNumericOrderFourThroughTen(): void
    {
        $container = static::getContainer();
        $parser = new AuditRequirementsJsonDocumentParser();
        $upserter = $container->get(AuditRequirementUpserter::class);
        /** @var AuditStandardRepository $standardRepository */
        $standardRepository = $container->get(AuditStandardRepository::class);
        /** @var AuditRequirementRepository $requirementRepository */
        $requirementRepository = $container->get(AuditRequirementRepository::class);

        $standard = $standardRepository->findOneByCode('iso_14001');
        self::assertInstanceOf(AuditStandard::class, $standard);

        $json = json_decode((string) file_get_contents(self::FIXTURE), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($json);
        $parsed = $parser->parse($json, 'iso_14001');
        $upserter->upsertRows($standard, $parsed->rows, basename(self::FIXTURE));
        $this->entityManager->clear();

        $standardReloaded = $standardRepository->findOneByCode('iso_14001');
        self::assertInstanceOf(AuditStandard::class, $standardReloaded);

        $chapters = $requirementRepository->findDistinctChaptersForStandard($standardReloaded);
        self::assertGreaterThanOrEqual(7, \count($chapters));
        self::assertStringStartsWith('4.', $chapters[0]);
        self::assertStringStartsWith('10.', $chapters[\count($chapters) - 1]);

        $user = $this->createTestUser('qse-ch-order-' . uniqid() . '@example.com', 'Test123456!');
        $audit = new Audit();
        $audit->setOwner($user);
        $audit->setAuditStandard($standardReloaded);
        $audit->setAuditedAt(new \DateTimeImmutable('2026-05-01'));
        $audit->setCompanyName('Test ordre chapitres');
        $this->entityManager->persist($audit);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard/qse/audit/' . $audit->getId());
        $this->assertResponseIsSuccessful();
        $html = (string) $this->client->getResponse()->getContent();

        self::assertTrue(
            str_contains($html, $chapters[0])
            || str_contains($html, htmlspecialchars($chapters[0], \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8')),
        );
        self::assertMatchesRegularExpression('/name="eval\[\d+\]\[verdict\]"/', $html);
    }
}
