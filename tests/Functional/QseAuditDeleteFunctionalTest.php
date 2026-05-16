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

    public function testAjaxDeleteAuditReturnsJsonPayloadWithoutRedirect(): void
    {
        $user = $this->createTestUser('audit-del-ajax-' . uniqid() . '@example.com', 'Test123456!');
        $std = $this->entityManager->getRepository(AuditStandard::class)->findOneBy(['code' => 'iso_9001']);
        $this->assertInstanceOf(AuditStandard::class, $std);

        $audit = new Audit();
        $audit->setOwner($user);
        $audit->setAuditStandard($std);
        $audit->setAuditedAt(new \DateTimeImmutable('2026-04-20'));
        $audit->setCompanyName('Suppression AJAX');
        $this->entityManager->persist($audit);
        $this->entityManager->flush();
        $id = (int) $audit->getId();

        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/dashboard/qse/audit');
        $this->assertResponseIsSuccessful();
        $tokenNode = $crawler->filter('[data-csrf-token]')->first();
        $this->assertGreaterThan(0, $tokenNode->count());
        $token = (string) $tokenNode->attr('data-csrf-token');

        $this->client->request(
            'POST',
            '/dashboard/qse/audit/' . $id . '/delete',
            ['_token' => $token],
            [],
            [
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        $this->assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($payload['success'] ?? false);
        $this->assertSame('L’audit a été supprimé.', $payload['message'] ?? null);
        $this->assertSame('/dashboard/qse/audit', $payload['redirect'] ?? null);

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
        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Audit Management Board', $content);
        $this->assertStringContainsString('fixed top-[50%] left-[50%]', $content, 'Le dialogue de suppression doit reprendre le positionnement centré des autres dialogs.');
        $this->assertStringNotContainsString('&amp;#x20;', $content, 'Le libellé d’audit ne doit pas être doublement encodé dans les attributs du bouton de suppression.');
        $this->assertStringContainsString('submit->qse-audit-delete#submit', $content, 'Le formulaire de suppression doit être intercepté côté Stimulus.');
        $this->assertStringContainsString('data-delete-url="/dashboard/qse/audit/', $content, 'Chaque bouton doit fournir une URL de suppression explicite au contrôleur Stimulus.');
    }
}
