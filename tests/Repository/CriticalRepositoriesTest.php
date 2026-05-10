<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Application\Analytics\TrackingEventType;
use App\Entity\Qse\CAPAAction;
use App\Entity\Qse\CapaOrigin;
use App\Entity\TrackingEvent;
use App\Qse\Enum\CapaStatus;
use App\Repository\Qse\CAPAActionRepository;
use App\Repository\Qse\CockpitMetricsRepository;
use App\Repository\TrackingEventRepository;
use App\Tests\TestCase\WebTestCaseWithDatabase;

final class CriticalRepositoriesTest extends WebTestCaseWithDatabase
{
    public function testCockpitMetricsOpenCapaIncreasesWithDraftCapa(): void
    {
        $user = $this->createTestUser('repo-metric-' . uniqid() . '@example.com', 'Test123456!');
        /** @var CockpitMetricsRepository $metrics */
        $metrics = self::getContainer()->get(CockpitMetricsRepository::class);
        $before = $metrics->getMetrics($user)['openCapaCount'];

        $origin = $this->entityManager->getRepository(CapaOrigin::class)->findOneBy(['slug' => 'qqoqccp']);
        self::assertInstanceOf(CapaOrigin::class, $origin);
        $capa = new CAPAAction();
        $capa->setOwner($user);
        $capa->setTitle('Ouverte pour métrique');
        $capa->setOrigin($origin);
        $capa->setStatus(CapaStatus::BROUILLON);
        $this->entityManager->persist($capa);
        $this->entityManager->flush();
        $this->entityManager->clear();
        $userReloaded = $this->entityManager->getRepository(\App\Entity\User::class)->findOneBy(['email' => $user->getEmail()]);
        self::assertNotNull($userReloaded);

        $after = $metrics->getMetrics($userReloaded)['openCapaCount'];
        self::assertSame($before + 1, $after);
    }

    public function testCountOpenCriticalByOwner(): void
    {
        $user = $this->createTestUser('repo-crit-' . uniqid() . '@example.com', 'Test123456!');
        $origin = $this->entityManager->getRepository(CapaOrigin::class)->findOneBy(['slug' => '8d']);
        self::assertInstanceOf(CapaOrigin::class, $origin);

        $capa = new CAPAAction();
        $capa->setOwner($user);
        $capa->setTitle('Critique ouverte');
        $capa->setOrigin($origin);
        $capa->setCriticality('critical');
        $capa->setStatus(CapaStatus::EN_COURS);
        $this->entityManager->persist($capa);
        $this->entityManager->flush();
        $this->entityManager->clear();
        $userReloaded = $this->entityManager->getRepository(\App\Entity\User::class)->findOneBy(['email' => $user->getEmail()]);
        self::assertNotNull($userReloaded);

        /** @var CAPAActionRepository $repo */
        $repo = self::getContainer()->get(CAPAActionRepository::class);
        self::assertGreaterThanOrEqual(1, $repo->countOpenCriticalByOwner($userReloaded));
    }

    public function testFindRecentForUserWithinWindow(): void
    {
        $user = $this->createTestUser('repo-track-' . uniqid() . '@example.com', 'Test123456!');
        $ev = new TrackingEvent();
        $ev->setUser($user);
        $ev->setEventType(TrackingEventType::DASHBOARD_OPENED);
        $ev->setCreatedAt(new \DateTimeImmutable('-1 hour'));
        $ev->setAction('open');
        $ev->setSource('web');
        $this->entityManager->persist($ev);
        $this->entityManager->flush();
        $this->entityManager->clear();
        $userReloaded = $this->entityManager->getRepository(\App\Entity\User::class)->findOneBy(['email' => $user->getEmail()]);
        self::assertNotNull($userReloaded);

        /** @var TrackingEventRepository $repo */
        $repo = self::getContainer()->get(TrackingEventRepository::class);
        $rows = $repo->findRecentForUser($userReloaded, new \DateTimeImmutable('-2 hours'), 10);
        self::assertNotEmpty($rows);
        self::assertSame(TrackingEventType::DASHBOARD_OPENED, $rows[0]->getEventType());
    }
}
