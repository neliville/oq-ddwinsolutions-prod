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
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Vérifie que les trois référentiels ISO chargent des exigences et que la page audit les affiche.
 */
final class QseAuditReferentialRequirementsFunctionalTest extends WebTestCaseWithDatabase
{
    private const FIXTURES_DIR = __DIR__ . '/../../data/fixtures';

    private AuditRequirementsJsonDocumentParser $documentParser;

    private AuditRequirementUpserter $upserter;

    private AuditStandardRepository $standardRepository;

    private AuditRequirementRepository $requirementRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $container = static::getContainer();
        $this->documentParser = new AuditRequirementsJsonDocumentParser();
        $this->upserter = $container->get(AuditRequirementUpserter::class);
        $this->standardRepository = $container->get(AuditStandardRepository::class);
        $this->requirementRepository = $container->get(AuditRequirementRepository::class);
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2: string}>
     */
    public static function referentialFixturesProvider(): iterable
    {
        yield 'iso_9001' => ['iso_9001', self::FIXTURES_DIR . '/iso9001_requirements.json', 'Déterminer les enjeux externes et internes'];
        yield 'iso_14001' => ['iso_14001', self::FIXTURES_DIR . '/iso_14001.json.json', "Contexte de l'entreprise"];
        yield 'iso_45001' => ['iso_45001', self::FIXTURES_DIR . '/iso_45001.json.json', 'Contexte'];
    }

    #[DataProvider('referentialFixturesProvider')]
    public function testReferentialLoadsRequirementsFromJsonFixture(string $code, string $fixturePath, string $sampleText): void
    {
        $this->importJsonFixture($fixturePath, $code);
        $standard = $this->standardRepository->findOneByCode($code);
        self::assertInstanceOf(AuditStandard::class, $standard);

        $chapters = $this->requirementRepository->findDistinctChaptersForStandard($standard);
        self::assertNotEmpty($chapters, sprintf('Aucun chapitre pour %s après import.', $code));

        $count = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(\App\Entity\Qse\AuditRequirement::class, 'r')
            ->where('r.auditStandard = :std')
            ->andWhere('r.active = true')
            ->setParameter('std', $standard)
            ->getQuery()
            ->getSingleScalarResult();

        self::assertGreaterThan(50, $count, sprintf('Trop peu d’exigences pour %s (%d).', $code, $count));

        $qb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(\App\Entity\Qse\AuditRequirement::class, 'r')
            ->where('r.auditStandard = :std')
            ->andWhere('r.requirementText LIKE :needle')
            ->setParameter('std', $standard)
            ->setParameter('needle', '%' . addcslashes(mb_substr($sampleText, 0, 40), '%_') . '%');
        self::assertGreaterThan(0, (int) $qb->getQuery()->getSingleScalarResult(), sprintf('Texte attendu absent pour %s.', $code));
    }

    #[DataProvider('referentialFixturesProvider')]
    public function testAuditShowDisplaysRequirementsForReferential(string $code, string $fixturePath, string $sampleText): void
    {
        unset($sampleText);
        $this->importJsonFixture($fixturePath, $code);
        $standard = $this->standardRepository->findOneByCode($code);
        self::assertInstanceOf(AuditStandard::class, $standard);

        $chapters = $this->requirementRepository->findDistinctChaptersForStandard($standard);
        self::assertNotEmpty($chapters);
        $requirements = $this->requirementRepository->findByChapterOrderedForStandard($chapters[0], $standard);
        self::assertNotEmpty($requirements);
        $snippet = mb_substr($requirements[0]->getRequirementText(), 0, 40);

        $user = $this->createTestUser('qse-ref-' . $code . '-' . uniqid() . '@example.com', 'Test123456!');
        $audit = new Audit();
        $audit->setOwner($user);
        $audit->setAuditStandard($standard);
        $audit->setAuditedAt(new \DateTimeImmutable('2026-05-01'));
        $audit->setCompanyName('Test ' . $code);
        $this->entityManager->persist($audit);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard/qse/audit/' . $audit->getId());
        $this->assertResponseIsSuccessful();
        $html = (string) $this->client->getResponse()->getContent();

        self::assertStringNotContainsString('Aucune exigence n’est chargée pour ce référentiel', $html);
        self::assertMatchesRegularExpression('/name="eval\[\d+\]\[verdict\]"/', $html);
        self::assertTrue(
            str_contains($html, $chapters[0])
            || str_contains($html, htmlspecialchars($chapters[0], \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8')),
            'Le libellé de chapitre attendu est absent de la page.',
        );
        self::assertStringContainsString(htmlspecialchars($snippet, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8'), $html);
    }

    public function testIso9001RequirementCountUnchangedWhenImporting14001And45001Only(): void
    {
        $this->importJsonFixture(self::FIXTURES_DIR . '/iso9001_requirements.json', 'iso_9001');
        $s9001 = $this->standardRepository->findOneByCode('iso_9001');
        self::assertInstanceOf(AuditStandard::class, $s9001);
        $count9001Before = $this->countActiveRequirements($s9001);

        $this->importJsonFixture(self::FIXTURES_DIR . '/iso_14001.json.json', 'iso_14001');
        $this->importJsonFixture(self::FIXTURES_DIR . '/iso_45001.json.json', 'iso_45001');

        self::assertSame($count9001Before, $this->countActiveRequirements($s9001));
        self::assertSame(226, $this->countActiveRequirements($this->standardRepository->findOneByCode('iso_14001')));
        self::assertSame(320, $this->countActiveRequirements($this->standardRepository->findOneByCode('iso_45001')));
    }

    private function importJsonFixture(string $path, string $expectedCode): void
    {
        self::assertFileIsReadable($path);
        $json = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($json);
        $parsed = $this->documentParser->parse($json, $expectedCode);
        self::assertSame($expectedCode, $parsed->standardCode);
        $standard = $this->standardRepository->findOneByCode($expectedCode);
        self::assertInstanceOf(AuditStandard::class, $standard);
        $this->upserter->upsertRows($standard, $parsed->rows, basename($path));
        $this->entityManager->clear();
    }

    private function countActiveRequirements(?AuditStandard $standard): int
    {
        self::assertInstanceOf(AuditStandard::class, $standard);

        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(\App\Entity\Qse\AuditRequirement::class, 'r')
            ->where('r.auditStandard = :std')
            ->andWhere('r.active = true')
            ->setParameter('std', $standard)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
