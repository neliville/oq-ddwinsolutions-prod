<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Qse\Audit;
use App\Entity\Qse\AuditStandard;
use App\Entity\User;
use App\Tests\TestCase\WebTestCaseWithDatabase;

final class QseAuditDeleteFunctionalTest extends WebTestCaseWithDatabase
{
    public function testDeleteAuditRemovesEntity(): void
    {
        $user = $this->createTestUser('audit-del-' . uniqid() . '@example.com', 'Test123456!');
        $std = $this->entityManager->getRepository(AuditStandard::class)->findOneBy(['code' => 'iso_9001']);
        $this->assertInstanceOf(AuditStandard::class, $std);

        $audit = new Audit();
        $audit->setOwner($user);
        $audit->setAuditStandard($std);
        $audit->setAuditedAt(new \DateTimeImmutable('2026-04-15'));
        $audit->setCompanyName('Société test');
        $this->entityManager->persist($audit);
        $this->entityManager->flush();
        $id = (int) $audit->getId();

        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/dashboard/qse/audit');
        $this->assertResponseIsSuccessful();
        $tokenNode = $crawler->filter('[data-csrf-token]')->first();
        $this->assertGreaterThan(0, $tokenNode->count());
        $token = (string) $tokenNode->attr('data-csrf-token');
        $this->assertNotSame('', $token);

        $this->client->request('POST', '/dashboard/qse/audit/' . $id . '/delete', [
            '_token' => $token,
        ]);
        $this->assertResponseRedirects('/dashboard/qse/audit');

        $this->entityManager->clear();
        $this->assertNull($this->entityManager->getRepository(Audit::class)->find($id));
    }

    public function testAuditIndexListsAuditsWithDeleteControl(): void
    {
        $user = $this->createTestUser('audit-idx-' . uniqid() . '@example.com', 'Test123456!');
        $std = $this->entityManager->getRepository(AuditStandard::class)->findOneBy(['code' => 'iso_9001']);
        $this->assertInstanceOf(AuditStandard::class, $std);

        $audit = new Audit();
        $audit->setOwner($user);
        $audit->setAuditStandard($std);
        $audit->setAuditedAt(new \DateTimeImmutable('2026-03-10'));
        $audit->setCompanyName('Index UX');
        $this->entityManager->persist($audit);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/dashboard/qse/audit');
        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('[data-action="click->qse-audit-delete#open"]'));
        $this->assertStringContainsString('Vos audits QSE', (string) $this->client->getResponse()->getContent());
    }
}
