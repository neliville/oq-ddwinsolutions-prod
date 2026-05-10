<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Qse\Audit;
use App\Entity\Qse\AuditRequirement;
use App\Entity\Qse\AuditStandard;
use App\Repository\Qse\AuditRequirementRepository;
use App\Tests\TestCase\WebTestCaseWithDatabase;

final class QseAuditMultiReferentialFunctionalTest extends WebTestCaseWithDatabase
{
    public function testChaptersAreIsolatedByAuditStandard(): void
    {
        $s9001 = $this->entityManager->getRepository(AuditStandard::class)->findOneBy(['code' => 'iso_9001']);
        $s14001 = $this->entityManager->getRepository(AuditStandard::class)->findOneBy(['code' => 'iso_14001']);
        $this->assertInstanceOf(AuditStandard::class, $s9001);
        $this->assertInstanceOf(AuditStandard::class, $s14001);

        $r9 = new AuditRequirement();
        $r9->setAuditStandard($s9001);
        $r9->setChapter('4. Contexte qualité');
        $r9->setIsoArticle('4.1');
        $r9->setRequirementText('Exigence 9001 test');
        $r9->setLegacyKey('multi_ref_9001_' . uniqid());
        $r9->setDisplayOrder(1);
        $r9->setActive(true);

        $r14 = new AuditRequirement();
        $r14->setAuditStandard($s14001);
        $r14->setChapter('6. Planification env');
        $r14->setIsoArticle('6.1');
        $r14->setRequirementText('Exigence 14001 test');
        $r14->setLegacyKey('multi_ref_14001_' . uniqid());
        $r14->setDisplayOrder(1);
        $r14->setActive(true);

        $this->entityManager->persist($r9);
        $this->entityManager->persist($r14);
        $this->entityManager->flush();

        /** @var AuditRequirementRepository $repo */
        $repo = $this->entityManager->getRepository(AuditRequirement::class);
        $ch9 = $repo->findDistinctChaptersForStandard($s9001);
        $ch14 = $repo->findDistinctChaptersForStandard($s14001);

        $this->assertContains('4. Contexte qualité', $ch9);
        $this->assertNotContains('6. Planification env', $ch9);
        $this->assertContains('6. Planification env', $ch14);
        $this->assertNotContains('4. Contexte qualité', $ch14);
    }

    public function testAuditShowUsesOnlyStandardChapters(): void
    {
        $user = $this->createTestUser('qse-mr-' . uniqid() . '@example.com', 'Test123456!');
        $s14001 = $this->entityManager->getRepository(AuditStandard::class)->findOneBy(['code' => 'iso_14001']);
        $this->assertInstanceOf(AuditStandard::class, $s14001);

        $r14 = new AuditRequirement();
        $r14->setAuditStandard($s14001);
        $r14->setChapter('Chapitre SST démo');
        $r14->setIsoArticle('8.1');
        $r14->setRequirementText('Exigence cockpit 14001');
        $r14->setLegacyKey('mr_show_' . uniqid());
        $r14->setDisplayOrder(1);
        $r14->setActive(true);
        $this->entityManager->persist($r14);

        $s9001 = $this->entityManager->getRepository(AuditStandard::class)->findOneBy(['code' => 'iso_9001']);
        $this->assertInstanceOf(AuditStandard::class, $s9001);
        $r9 = new AuditRequirement();
        $r9->setAuditStandard($s9001);
        $r9->setChapter('Chapitre qualité démo');
        $r9->setIsoArticle('5.1');
        $r9->setRequirementText('Exigence cockpit 9001');
        $r9->setLegacyKey('mr_show9_' . uniqid());
        $r9->setDisplayOrder(1);
        $r9->setActive(true);
        $this->entityManager->persist($r9);
        $this->entityManager->flush();

        $audit = new Audit();
        $audit->setOwner($user);
        $audit->setAuditStandard($s14001);
        $audit->setAuditedAt(new \DateTimeImmutable('2026-03-01'));
        $audit->setCompanyName('Entreprise MR');
        $this->entityManager->persist($audit);
        $this->entityManager->flush();
        $auditId = (int) $audit->getId();

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard/qse/audit/' . $auditId);
        $this->assertResponseIsSuccessful();
        $html = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Chapitre SST démo', $html);
        $this->assertStringNotContainsString('Chapitre qualité démo', $html);
    }
}
