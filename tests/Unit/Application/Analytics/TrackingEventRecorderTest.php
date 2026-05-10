<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Analytics;

use App\Application\Analytics\TrackingEventRecorder;
use App\Application\Analytics\TrackingEventType;
use App\Entity\TrackingEvent;
use App\Repository\TrackingEventRepository;
use App\Tests\TestCase\WebTestCaseWithDatabase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class TrackingEventRecorderTest extends WebTestCaseWithDatabase
{
    public function testRecordPersistsEventWithMetadataAndHashes(): void
    {
        $user = $this->createTestUser('recorder-unit-' . uniqid() . '@example.com', 'Test123456!');

        $request = Request::create('/dashboard', 'GET', [], [], [], ['REMOTE_ADDR' => '203.0.113.10']);
        $request->attributes->set('_route', 'app_dashboard_index');
        $session = new Session(new MockArraySessionStorage());
        $session->set('x', 'y');
        $request->setSession($session);

        /** @var RequestStack $stack */
        $stack = static::getContainer()->get(RequestStack::class);
        $stack->push($request);

        /** @var TrackingEventRecorder $recorder */
        $recorder = static::getContainer()->get(TrackingEventRecorder::class);
        $recorder->record(
            TrackingEventType::DASHBOARD_OPENED,
            ['hello' => 'world'],
            $user,
            null,
            null,
            'web',
        );

        $stack->pop();

        /** @var TrackingEventRepository $repo */
        $repo = static::getContainer()->get(TrackingEventRepository::class);
        $rows = $repo->findBy(['eventType' => TrackingEventType::DASHBOARD_OPENED], ['id' => 'DESC'], 1);
        $this->assertCount(1, $rows);
        /** @var TrackingEvent $ev */
        $ev = $rows[0];
        $this->assertSame(64, strlen((string) $ev->getIpHash()));
        $this->assertSame(64, strlen((string) $ev->getSessionKey()));
        $meta = $ev->getMetadata();
        $this->assertIsArray($meta);
        $this->assertSame('world', $meta['hello'] ?? null);
        $this->assertArrayHasKey('route', $meta);
    }
}
