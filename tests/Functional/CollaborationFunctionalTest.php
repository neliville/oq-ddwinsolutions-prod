<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Collaboration\InvitationStatus;
use App\Entity\Qse\Audit;
use App\Entity\Qse\AuditStandard;
use App\Entity\Qse\CAPAAction;
use App\Entity\Qse\CapaOrigin;
use App\Entity\UserInvitation;
use App\Tests\TestCase\WebTestCaseWithDatabase;
use Symfony\Component\HttpFoundation\Response;

final class CollaborationFunctionalTest extends WebTestCaseWithDatabase
{
    private function collaborationCsrfFromDashboard(): string
    {
        $this->client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();
        $node = $this->client->getCrawler()->filter('#app-collaboration-csrf');
        $this->assertGreaterThan(0, $node->count());
        $t = $node->attr('data-csrf');
        $this->assertNotEmpty($t);

        return (string) $t;
    }
    public function testShareAuditGuestViewAndRevoke(): void
    {
        $user = $this->createTestUser('collab-owner-' . uniqid() . '@example.com', 'Test123456!');
        $std = $this->entityManager->getRepository(AuditStandard::class)->findOneBy(['code' => 'iso_9001']);
        self::assertInstanceOf(AuditStandard::class, $std);
        $audit = new Audit();
        $audit->setOwner($user);
        $audit->setAuditStandard($std);
        $audit->setAuditedAt(new \DateTimeImmutable('2026-02-01'));
        $audit->setCompanyName('Entreprise Test Collab');
        $this->entityManager->persist($audit);
        $this->entityManager->flush();
        $auditId = (int) $audit->getId();

        $this->client->loginUser($user);
        $csrf = $this->collaborationCsrfFromDashboard();
        $this->client->request(
            'POST',
            '/api/collaboration/share',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'targetType' => 'audit',
                'targetId' => $auditId,
                'accessLevel' => 'lecture_seule',
                'ttlDays' => 7,
                'sendEmail' => false,
                'csrf_token' => $csrf,
            ], JSON_THROW_ON_ERROR),
        );
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($data['ok']);
        self::assertNotEmpty($data['plainToken']);
        $token = $data['plainToken'];
        $shareId = (int) $data['sharedAccessId'];

        $this->client->restart();
        $this->client->request('GET', '/share/qse/audit/' . $token);
        $this->assertResponseIsSuccessful();
        self::assertStringContainsString('Entreprise Test Collab', $this->client->getResponse()->getContent());

        $this->client->loginUser($user);
        $csrf2 = $this->collaborationCsrfFromDashboard();
        $this->client->request('POST', '/api/collaboration/share/' . $shareId . '/revoke', content: json_encode(['csrf_token' => $csrf2], JSON_THROW_ON_ERROR));
        $this->assertResponseIsSuccessful();

        $this->client->restart();
        $this->client->request('GET', '/share/qse/audit/' . $token);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testInviteApiCreatesPendingInvitation(): void
    {
        $user = $this->createTestUser('collab-inv-' . uniqid() . '@example.com', 'Test123456!');
        $this->client->loginUser($user);
        $csrf = $this->collaborationCsrfFromDashboard();
        $email = 'invitee-' . uniqid() . '@example.com';
        $this->client->request(
            'POST',
            '/api/collaboration/invite',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => $email,
                'firstName' => 'Alex',
                'role' => 'lecteur',
                'source' => 'dashboard',
                'sendEmail' => false,
                'csrf_token' => $csrf,
            ], JSON_THROW_ON_ERROR),
        );
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($data['ok']);
        self::assertNotEmpty($data['acceptUrl']);
    }

    public function testInvitationAcceptLandingForGuest(): void
    {
        $owner = $this->createTestUser('collab-land-owner-' . uniqid() . '@example.com', 'Test123456!');
        $this->client->loginUser($owner);
        $csrf = $this->collaborationCsrfFromDashboard();
        $email = 'collab-land-inv-' . uniqid() . '@example.com';
        $this->client->request(
            'POST',
            '/api/collaboration/invite',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => $email,
                'role' => 'lecteur',
                'source' => 'dashboard',
                'sendEmail' => false,
                'csrf_token' => $csrf,
            ], JSON_THROW_ON_ERROR),
        );
        $this->assertResponseIsSuccessful();
        $plain = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['plainToken'];
        $this->client->restart();
        $this->client->request('GET', '/collaboration/invitation/accept?t=' . rawurlencode($plain));
        $this->assertResponseIsSuccessful();
        self::assertStringContainsString('Créer mon compte', $this->client->getResponse()->getContent());
    }

    public function testDashboardStillLoadsWithCollaborationBlock(): void
    {
        $user = $this->createTestUser('collab-dash-' . uniqid() . '@example.com', 'Test123456!');
        $prefsRepo = $this->entityManager->getRepository(\App\Entity\UserPreferences::class);
        $prefsRepo->getOrCreateForUser($user);
        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();
        self::assertStringContainsString('Collaboration', $this->client->getResponse()->getContent());
    }

    public function testShareCapaGuestView(): void
    {
        $user = $this->createTestUser('collab-capa-owner-' . uniqid() . '@example.com', 'Test123456!');
        $origin = $this->entityManager->getRepository(CapaOrigin::class)->findOneBy(['slug' => 'amdec']);
        self::assertInstanceOf(CapaOrigin::class, $origin);
        $capa = new CAPAAction();
        $capa->setOwner($user);
        $capa->setTitle('CAPA partagée lecture seule');
        $capa->setOrigin($origin);
        $this->entityManager->persist($capa);
        $this->entityManager->flush();
        $capaId = (int) $capa->getId();

        $this->client->loginUser($user);
        $csrf = $this->collaborationCsrfFromDashboard();
        $this->client->request(
            'POST',
            '/api/collaboration/share',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'targetType' => 'capa',
                'targetId' => $capaId,
                'accessLevel' => 'lecture_seule',
                'ttlDays' => 7,
                'sendEmail' => false,
                'csrf_token' => $csrf,
            ], JSON_THROW_ON_ERROR),
        );
        $this->assertResponseIsSuccessful();
        $plain = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['plainToken'];

        $this->client->restart();
        $this->client->request('GET', '/share/qse/capa/' . rawurlencode($plain));
        $this->assertResponseIsSuccessful();
        self::assertStringContainsString('CAPA partagée lecture seule', (string) $this->client->getResponse()->getContent());
    }

    public function testInvitationAcceptWhenLoggedInWithMatchingEmail(): void
    {
        $inviteeEmail = 'collab-exist-' . uniqid() . '@example.com';
        $invitee = $this->createTestUser($inviteeEmail, 'Test123456!');
        $owner = $this->createTestUser('collab-own-exist-' . uniqid() . '@example.com', 'Test123456!');

        $this->client->loginUser($owner);
        $csrf = $this->collaborationCsrfFromDashboard();
        $this->client->request(
            'POST',
            '/api/collaboration/invite',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => $inviteeEmail,
                'role' => 'lecteur',
                'source' => 'dashboard',
                'sendEmail' => false,
                'csrf_token' => $csrf,
            ], JSON_THROW_ON_ERROR),
        );
        $this->assertResponseIsSuccessful();
        $plain = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['plainToken'];

        $this->client->restart();
        $this->client->loginUser($invitee);
        $this->client->request('GET', '/collaboration/invitation/accept?t=' . rawurlencode($plain));
        $this->assertResponseRedirects('/dashboard');
        $this->entityManager->clear();
        $invRow = $this->entityManager->getRepository(UserInvitation::class)->findOneBy(['email' => $inviteeEmail]);
        self::assertInstanceOf(UserInvitation::class, $invRow);
        self::assertSame(InvitationStatus::ACCEPTEE, $invRow->getStatus());
    }

    public function testInvitationAcceptAfterLoginFromSessionStoresToken(): void
    {
        $inviteeEmail = 'collab-sess-' . uniqid() . '@example.com';
        $this->createTestUser($inviteeEmail, 'InvSession9!');
        $owner = $this->createTestUser('collab-sess-own-' . uniqid() . '@example.com', 'Test123456!');

        $this->client->loginUser($owner);
        $csrf = $this->collaborationCsrfFromDashboard();
        $this->client->request(
            'POST',
            '/api/collaboration/invite',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => $inviteeEmail,
                'role' => 'lecteur',
                'source' => 'dashboard',
                'sendEmail' => false,
                'csrf_token' => $csrf,
            ], JSON_THROW_ON_ERROR),
        );
        $plain = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['plainToken'];

        $this->client->restart();
        $this->client->request('GET', '/collaboration/invitation/accept?t=' . rawurlencode($plain));
        $this->assertResponseIsSuccessful();

        $crawler = $this->client->request('GET', '/login');
        $this->assertResponseIsSuccessful();
        $loginCsrf = (string) $crawler->filter('input[name="_csrf_token"]')->attr('value');
        $this->client->request('POST', '/login', [
            '_username' => $inviteeEmail,
            '_password' => 'InvSession9!',
            '_csrf_token' => $loginCsrf,
        ]);
        $this->assertResponseRedirects('/dashboard');
        $this->entityManager->clear();
        $invRow = $this->entityManager->getRepository(UserInvitation::class)->findOneBy(['email' => $inviteeEmail]);
        self::assertInstanceOf(UserInvitation::class, $invRow);
        self::assertSame(InvitationStatus::ACCEPTEE, $invRow->getStatus());
    }

    public function testInvitationExpiredReturnsGone(): void
    {
        $owner = $this->createTestUser('collab-exp-own-' . uniqid() . '@example.com', 'Test123456!');
        $this->client->loginUser($owner);
        $csrf = $this->collaborationCsrfFromDashboard();
        $email = 'collab-exp-inv-' . uniqid() . '@example.com';
        $this->client->request(
            'POST',
            '/api/collaboration/invite',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => $email,
                'role' => 'lecteur',
                'source' => 'dashboard',
                'sendEmail' => false,
                'csrf_token' => $csrf,
            ], JSON_THROW_ON_ERROR),
        );
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $plain = $data['plainToken'];
        $invId = (int) $data['invitationId'];

        $inv = $this->entityManager->find(UserInvitation::class, $invId);
        self::assertInstanceOf(UserInvitation::class, $inv);
        $inv->setExpiresAt(new \DateTimeImmutable('-1 day'));
        $this->entityManager->flush();

        $this->client->restart();
        $this->client->request('GET', '/collaboration/invitation/accept?t=' . rawurlencode($plain));
        $this->assertResponseStatusCodeSame(Response::HTTP_GONE);
    }
}
