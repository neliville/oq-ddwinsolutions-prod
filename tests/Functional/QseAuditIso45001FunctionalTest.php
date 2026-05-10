<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Qse\Audit;
use App\Entity\Qse\AuditRequirement;
use App\Entity\Qse\AuditStandard;
use App\Tests\TestCase\WebTestCaseWithDatabase;

final class QseAuditIso45001FunctionalTest extends WebTestCaseWithDatabase
{
    public function testAuditShowListsOnlyIso45001Chapters(): void
    {
        $user = $this->createTestUser('qse-45001-' . uniqid() . '@example.com', 'Test123456!');
        $s45001 = $this->entityManager->getRepository(AuditStandard::class)->findOneBy(['code' => 'iso_45001']);
        $s9001 = $this->entityManager->getRepository(AuditStandard::class)->findOneBy(['code' => 'iso_9001']);
        self::assertInstanceOf(AuditStandard::class, $s45001);
        self::assertInstanceOf(AuditStandard::class, $s9001);

        $r45 = new AuditRequirement();
        $r45->setAuditStandard($s45001);
        $r45->setChapter('4. Contexte organisation SST (45001)');
        $r45->setIsoArticle('4.1');
        $r45->setRequirementText('Exigence ISO 45001 test');
        $r45->setLegacyKey('iso45001_ch_' . uniqid());
        $r45->setDisplayOrder(1);
        $r45->setActive(true);

        $r9 = new AuditRequirement();
        $r9->setAuditStandard($s9001);
        $r9->setChapter('5. Politique qualité uniquement 9001');
        $r9->setIsoArticle('5.1');
        $r9->setRequirementText('Exigence 9001 hors périmètre 45001');
        $r9->setLegacyKey('iso9001_ch_' . uniqid());
        $r9->setDisplayOrder(1);
        $r9->setActive(true);

        $this->entityManager->persist($r45);
        $this->entityManager->persist($r9);
        $this->entityManager->flush();

        $audit = new Audit();
        $audit->setOwner($user);
        $audit->setAuditStandard($s45001);
        $audit->setAuditedAt(new \DateTimeImmutable('2026-04-01'));
        $audit->setCompanyName('Entreprise SST test');
        $this->entityManager->persist($audit);
        $this->entityManager->flush();
        $auditId = (int) $audit->getId();

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard/qse/audit/' . $auditId);
        $this->assertResponseIsSuccessful();
        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('4. Contexte organisation SST (45001)', $html);
        self::assertStringNotContainsString('5. Politique qualité uniquement 9001', $html);
    }
}
