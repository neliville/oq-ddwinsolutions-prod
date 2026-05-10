<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Application\Analytics\TrackingEventType;
use App\Entity\Qse\AuditStandard;
use App\Entity\TrackingEvent;
use App\Repository\TrackingEventRepository;
use App\Tests\TestCase\WebTestCaseWithDatabase;
use Symfony\Component\HttpFoundation\Response;
final class AdminPhase2TrackingTest extends WebTestCaseWithDatabase
{
    public function testAdminDashboardShowsUserViewSwitch(): void
    {
        $admin = $this->createTestUser('admin-phase2-' . uniqid() . '@example.com', 'Test123456!', ['ROLE_ADMIN', 'ROLE_USER']);
        $this->client->loginUser($admin);
        $this->client->request('GET', '/admin/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Vue utilisateur', (string) $this->client->getResponse()->getContent());
    }

    public function testNonAdminIsDeniedAdminArea(): void
    {
        $user = $this->createTestUser('user-phase2-' . uniqid() . '@example.com', 'Test123456!', ['ROLE_USER']);
        $this->client->loginUser($user);
        $this->client->request('GET', '/admin/dashboard');

        $this->assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testExportEndpointCreatesTrackingEvent(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $this->client->request(
            'POST',
            '/analytics/track-export',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['tool' => 'ishikawa', 'format' => 'pdf'], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseIsSuccessful();

        /** @var TrackingEventRepository $repo */
        $repo = static::getContainer()->get(TrackingEventRepository::class);
        $found = $repo->findBy(['eventType' => TrackingEventType::EXPORT_TRIGGERED], ['id' => 'DESC'], 1);
        $this->assertCount(1, $found);
        $this->assertSame('ishikawa', $found[0]->getTool());
        $this->assertSame('api', $found[0]->getSource());
    }

    public function testAuditCreationPersistsTrackingEvent(): void
    {
        $user = $this->createTestUser('audit-te-' . uniqid() . '@example.com', 'Test123456!');
        $standard = $this->entityManager->getRepository(AuditStandard::class)->findOneBy(['code' => 'iso_9001']);
        $this->assertInstanceOf(AuditStandard::class, $standard);

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard/qse/audit/new?standard=' . $standard->getId());
        $this->assertResponseIsSuccessful();
        $token = (string) $this->client->getCrawler()->filter('input[name="_token"]')->attr('value');
        $this->assertNotSame('', $token);

        $this->client->request('POST', '/dashboard/qse/audit/new?standard=' . $standard->getId(), [
            '_token' => $token,
            'audit_standard_id' => (string) $standard->getId(),
            'companyName' => 'Société test TE',
            'mainAuditor' => 'Auditeur TE',
            'auditedAt' => '2026-05-01',
            'auditVersion' => '1.0',
        ]);

        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        /** @var TrackingEventRepository $repo */
        $repo = static::getContainer()->get(TrackingEventRepository::class);
        $events = $repo->findBy(['eventType' => TrackingEventType::AUDIT_CREATED], ['id' => 'DESC'], 5);
        $this->assertNotEmpty($events);
        $latest = $events[0];
        $this->assertInstanceOf(TrackingEvent::class, $latest);
        $this->assertSame($user->getId(), $latest->getUser()?->getId());
        $meta = $latest->getMetadata();
        $this->assertIsArray($meta);
        $this->assertArrayHasKey('audit_id', $meta);
    }
}
