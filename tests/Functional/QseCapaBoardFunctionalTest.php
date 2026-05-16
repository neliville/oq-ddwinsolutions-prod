<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Qse\CAPAAction;
use App\Entity\Qse\CapaOrigin;
use App\Qse\Enum\CapaStatus;
use App\Tests\TestCase\WebTestCaseWithDatabase;

final class QseCapaBoardFunctionalTest extends WebTestCaseWithDatabase
{
    public function testCapaIndexRendersManagementBoard(): void
    {
        $user = $this->createTestUser('capa-board-' . uniqid() . '@example.com', 'Test123456!');
        $origin = $this->entityManager->getRepository(CapaOrigin::class)->findOneBy(['slug' => 'autre']);
        $this->assertInstanceOf(CapaOrigin::class, $origin);

        $capa = new CAPAAction();
        $capa->setOwner($user);
        $capa->setOrigin($origin);
        $capa->setTitle('Board CAPA Test');
        $capa->setStatus(CapaStatus::A_VALIDER);
        $this->entityManager->persist($capa);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/dashboard/qse/capa');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('CAPA Management Board', (string) $this->client->getResponse()->getContent());
        $this->assertGreaterThan(0, $crawler->filter('.capa-kpi-strip')->count());
        $this->assertGreaterThan(0, $crawler->filter('.capa-card-row, .capa-board__list')->count());

        $kpiStrip = $crawler->filter('.capa-kpi-strip');
        self::assertStringContainsString('Total CAPA', $kpiStrip->text());
        self::assertMatchesRegularExpression('/\b1\b/', $kpiStrip->text());
        self::assertStringContainsString('Board CAPA Test', (string) $this->client->getResponse()->getContent());
    }
}
