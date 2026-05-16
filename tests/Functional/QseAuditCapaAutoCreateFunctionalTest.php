<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Qse\Audit;
use App\Entity\Qse\AuditEvaluation;
use App\Entity\Qse\AuditRequirement;
use App\Entity\Qse\AuditStandard;
use App\Entity\Qse\CAPAAction;
use App\Entity\User;
use App\Repository\Qse\AuditEvaluationRepository;
use App\Repository\Qse\CAPAActionRepository;
use App\Repository\Qse\CockpitMetricsRepository;
use App\Tests\TestCase\WebTestCaseWithDatabase;

final class QseAuditCapaAutoCreateFunctionalTest extends WebTestCaseWithDatabase
{
    public function testSaveChapterWithMinorNcAutoCreatesCapa(): void
    {
        [$user, $audit, $req, $chapter] = $this->createAuditWithRequirement();
        $this->saveChapterVerdict($user, $audit, $req, $chapter, 'minor_nc');

        $this->entityManager->clear();
        $ev = $this->findEvaluation($audit, $req);
        $capa = self::getContainer()->get(CAPAActionRepository::class)->findOpenBySourceAuditEvaluation((int) $ev->getId());
        self::assertInstanceOf(CAPAAction::class, $capa);
        self::assertSame('audit-interne', $capa->getOrigin()?->getSlug());
        self::assertNotEmpty($capa->getMetadata()['auto_created_at'] ?? null);
    }

    public function testResavingSameNcDoesNotDuplicateOpenCapa(): void
    {
        [$user, $audit, $req, $chapter] = $this->createAuditWithRequirement();
        $this->saveChapterVerdict($user, $audit, $req, $chapter, 'major_nc');
        $this->saveChapterVerdict($user, $audit, $req, $chapter, 'major_nc');

        $this->entityManager->clear();
        $ev = $this->findEvaluation($audit, $req);
        $capas = $this->entityManager->getRepository(CAPAAction::class)->findBy([
            'sourceAuditEvaluation' => $ev,
        ]);
        $open = array_filter($capas, static fn (CAPAAction $c): bool => !\in_array($c->getStatus()->value, ['cloturee', 'annulee'], true));
        self::assertCount(1, $open);
    }

    public function testObservationDoesNotAutoCreateCapa(): void
    {
        [$user, $audit, $req, $chapter] = $this->createAuditWithRequirement();
        $this->saveChapterVerdict($user, $audit, $req, $chapter, 'observation');

        $this->entityManager->clear();
        $ev = $this->findEvaluation($audit, $req);
        $capa = self::getContainer()->get(CAPAActionRepository::class)->findOpenBySourceAuditEvaluation((int) $ev->getId());
        self::assertNull($capa);
    }

    public function testCockpitCountsOnlyMinorAndMajorNc(): void
    {
        $user = $this->createTestUser('capa-metrics-' . uniqid() . '@example.com', 'Test123456!');
        [$user, $audit, $req, $chapter] = $this->createAuditWithRequirement($user);
        $this->saveChapterVerdict($user, $audit, $req, $chapter, 'minor_nc');

        $std = $this->entityManager->getRepository(AuditStandard::class)->findOneBy(['code' => 'iso_9001']);
        self::assertInstanceOf(AuditStandard::class, $std);
        $req2 = new AuditRequirement();
        $req2->setAuditStandard($std);
        $req2->setChapter($chapter);
        $req2->setIsoArticle('4.2');
        $req2->setRequirementText('Observation test');
        $req2->setDisplayOrder(2);
        $req2->setLegacyKey('exig_obs_' . uniqid());
        $req2->setActive(true);
        $this->entityManager->persist($req2);
        $this->entityManager->flush();

        $this->saveChapterVerdict($user, $audit, $req2, $chapter, 'observation');

        $this->entityManager->clear();
        $userReloaded = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
        self::assertNotNull($userReloaded);

        $metrics = self::getContainer()->get(CockpitMetricsRepository::class)->getMetrics($userReloaded);
        self::assertSame(1, $metrics['openNonConformEvaluations']);

        $ncCount = self::getContainer()->get(AuditEvaluationRepository::class)->countNonConformitiesForOwner($userReloaded);
        self::assertSame(1, $ncCount);
    }

    /**
     * @return array{0: User, 1: Audit, 2: AuditRequirement, 3: string}
     */
    private function createAuditWithRequirement(?User $user = null): array
    {
        $user ??= $this->createTestUser('capa-auto-' . uniqid() . '@example.com', 'Test123456!');
        $std = $this->entityManager->getRepository(AuditStandard::class)->findOneBy(['code' => 'iso_9001']);
        self::assertInstanceOf(AuditStandard::class, $std);

        $req = new AuditRequirement();
        $req->setAuditStandard($std);
        $req->setChapter('4. Contexte');
        $req->setIsoArticle('4.1');
        $req->setRequirementText('Exigence NC auto');
        $req->setDisplayOrder(1);
        $req->setLegacyKey('exig_nc_' . uniqid());
        $req->setActive(true);
        $this->entityManager->persist($req);

        $audit = new Audit();
        $audit->setOwner($user);
        $audit->setAuditStandard($std);
        $audit->setAuditedAt(new \DateTimeImmutable('2026-05-01'));
        $audit->setCompanyName('ACME');
        $this->entityManager->persist($audit);
        $this->entityManager->flush();

        return [$user, $audit, $req, $req->getChapter()];
    }

    private function saveChapterVerdict(User $user, Audit $audit, AuditRequirement $req, string $chapter, string $verdictValue): void
    {
        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/dashboard/qse/audit/' . $audit->getId() . '?chapter=' . rawurlencode($chapter));
        self::assertResponseIsSuccessful();
        $csrf = $crawler->filter('form#audit-chapter-form input[name="_token"]')->first()->attr('value');
        self::assertNotEmpty($csrf);

        $this->client->request('POST', '/dashboard/qse/audit/' . $audit->getId() . '/chapter', [
            '_token' => $csrf,
            'chapter' => $chapter,
            'eval' => [
                (string) $req->getId() => [
                    'verdict' => $verdictValue,
                    'comment' => 'Test',
                ],
            ],
        ]);
        self::assertResponseRedirects();
    }

    private function findEvaluation(Audit $audit, AuditRequirement $req): AuditEvaluation
    {
        $auditReloaded = $this->entityManager->getRepository(Audit::class)->find($audit->getId());
        $reqReloaded = $this->entityManager->getRepository(AuditRequirement::class)->find($req->getId());
        $ev = $this->entityManager->getRepository(AuditEvaluation::class)->findOneBy([
            'audit' => $auditReloaded,
            'requirement' => $reqReloaded,
        ]);
        self::assertInstanceOf(AuditEvaluation::class, $ev);

        return $ev;
    }
}
