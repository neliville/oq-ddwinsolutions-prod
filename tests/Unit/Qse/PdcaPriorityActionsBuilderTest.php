<?php

declare(strict_types=1);

namespace App\Tests\Unit\Qse;

use App\Qse\Service\PdcaPriorityActionsBuilder;
use PHPUnit\Framework\TestCase;

final class PdcaPriorityActionsBuilderTest extends TestCase
{
    public function testBuildIncludesOverdueCapasWhenPresent(): void
    {
        $items = (new PdcaPriorityActionsBuilder())->build([
            'overdueOpenCapas' => 3,
            'dueCapaNext7Days' => 0,
            'overdueAuditPlans' => 0,
            'openNonConformEvaluations' => 0,
            'criticalRisksWithoutCapa' => 0,
            'staleAuditDrafts' => 0,
            'openCapasWithoutResponsible' => 0,
            'capasAwaitingVerification' => 0,
        ]);

        self::assertNotEmpty($items);
        self::assertSame('capa-overdue', $items[0]['id']);
        self::assertSame(3, $items[0]['count']);
    }

    public function testBuildEmptyWhenNoAlerts(): void
    {
        $items = (new PdcaPriorityActionsBuilder())->build([
            'overdueOpenCapas' => 0,
            'dueCapaNext7Days' => 0,
            'overdueAuditPlans' => 0,
            'openNonConformEvaluations' => 0,
            'criticalRisksWithoutCapa' => 0,
            'staleAuditDrafts' => 0,
            'openCapasWithoutResponsible' => 0,
            'capasAwaitingVerification' => 0,
        ]);

        self::assertSame([], $items);
    }
}
