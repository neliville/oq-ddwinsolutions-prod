<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Qse\Audit;
use App\Entity\Qse\AuditStandard;
use App\Tests\TestCase\WebTestCaseWithDatabase;

final class QseAuditBoardFunctionalTest extends WebTestCaseWithDatabase
{
    public function testAuditIndexRendersManagementBoard(): void
    {
        $user = $this->createTestUser('audit-board-' . uniqid() . '@example.com', 'Test123456!');
        $std = $this->entityManager->getRepository(AuditStandard::class)->findOneBy(['code' => 'iso_9001']);
        $this->assertInstanceOf(AuditStandard::class, $std);

        $audit = new Audit();
        $audit->setOwner($user);
        $audit->setAuditStandard($std);
        $audit->setAuditedAt(new \DateTimeImmutable('2026-05-01'));
        $audit->setCompanyName('Board Test SA');
        $this->entityManager->persist($audit);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/dashboard/qse/audit');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Audit Management Board', (string) $this->client->getResponse()->getContent());
        $this->assertGreaterThan(0, $crawler->filter('[data-controller*="live"]')->count() + $crawler->filter('.audit-kpi-strip')->count());
        $this->assertGreaterThan(0, $crawler->filter('.audit-card-row, .audit-board__list')->count());

        $kpiStrip = $crawler->filter('.audit-kpi-strip');
        self::assertGreaterThan(0, $kpiStrip->count());
        self::assertStringContainsString('Total audits', $kpiStrip->text());
        self::assertMatchesRegularExpression('/\b1\b/', $kpiStrip->text(), 'Le KPI total doit refléter l’audit créé (pas rester bloqué à 0 par GSAP).');
        self::assertStringNotContainsString('CAPA critiques', $kpiStrip->text());
        self::assertStringNotContainsString('Référentiels', $kpiStrip->text());
    }
}
