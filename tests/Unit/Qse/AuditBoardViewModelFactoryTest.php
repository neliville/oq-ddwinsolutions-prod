<?php

declare(strict_types=1);

namespace App\Tests\Unit\Qse;

use App\Entity\Qse\Audit;
use App\Entity\Qse\AuditStandard;
use App\Qse\Audit\ViewModel\AuditBoardFilters;
use App\Qse\Audit\ViewModel\AuditBoardViewModelFactory;
use App\Qse\Enum\AuditExecutionStatus;
use App\Tests\TestCase\WebTestCaseWithDatabase;

final class AuditBoardViewModelFactoryTest extends WebTestCaseWithDatabase
{
    public function testBuildReturnsKpisAndPaginatedRows(): void
    {
        $user = $this->createTestUser('audit-vm-' . uniqid() . '@example.com', 'Test123456!');
        $std = $this->entityManager->getRepository(AuditStandard::class)->findOneBy(['code' => 'iso_9001']);
        $this->assertInstanceOf(AuditStandard::class, $std);

        $audit = new Audit();
        $audit->setOwner($user);
        $audit->setAuditStandard($std);
        $audit->setAuditedAt(new \DateTimeImmutable('2026-04-01'));
        $audit->setCompanyName('ACME');
        $audit->setStatus(AuditExecutionStatus::EN_COURS);
        $audit->setGlobalComplianceRate(72.5);
        $this->entityManager->persist($audit);
        $this->entityManager->flush();

        /** @var AuditBoardViewModelFactory $factory */
        $factory = static::getContainer()->get(AuditBoardViewModelFactory::class);
        $vm = $factory->build($user, new AuditBoardFilters());

        self::assertSame($vm->totalCount, $vm->kpis->totalAudits);
        self::assertGreaterThanOrEqual(1, $vm->kpis->totalAudits);
        self::assertGreaterThanOrEqual(1, $vm->kpis->inProgress);
        self::assertGreaterThanOrEqual(1, $vm->totalCount);
        self::assertNotEmpty($vm->rows);
        self::assertStringContainsString('ACME', $vm->rows[0]->label);
    }
}
