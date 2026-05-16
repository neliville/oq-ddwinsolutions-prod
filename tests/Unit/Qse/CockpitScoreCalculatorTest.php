<?php

declare(strict_types=1);

namespace App\Tests\Unit\Qse;

use App\Qse\Service\CockpitScoreCalculator;
use PHPUnit\Framework\TestCase;

final class CockpitScoreCalculatorTest extends TestCase
{
    private CockpitScoreCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new CockpitScoreCalculator();
    }

    public function testGlobalScoreWithFullCockpitData(): void
    {
        $cockpit = [
            'avgAuditCompliancePercent' => 80.0,
            'openCapaCount' => 10,
            'overdueOpenCapas' => 2,
            'criticalRisksWithoutCapa' => 1,
            'openNonConformEvaluations' => 3,
        ];
        $plan = ['qqoqccp' => 1, 'amdec' => 1, 'audit_plans' => 1, 'risks' => 5];
        $do = ['capa_open' => 10];
        $check = ['audits' => 4, 'pareto' => 0];
        $act = ['ishikawa' => 2, 'five_why' => 1, 'eight_d' => 0];

        $result = $this->calculator->computeGlobalScore($cockpit, $plan, $do, $check, $act);

        self::assertTrue($result['initialized']);
        self::assertIsInt($result['score']);
        self::assertGreaterThanOrEqual(0, $result['score']);
        self::assertLessThanOrEqual(100, $result['score']);
    }

    public function testGlobalScoreEmptyUserReturnsUninitialized(): void
    {
        $cockpit = [
            'avgAuditCompliancePercent' => null,
            'openCapaCount' => 0,
            'overdueOpenCapas' => 0,
            'criticalRisksWithoutCapa' => 0,
            'openNonConformEvaluations' => 0,
        ];
        $plan = ['qqoqccp' => 0, 'amdec' => 0, 'audit_plans' => 0, 'risks' => 0];
        $do = ['capa_open' => 0];
        $check = ['audits' => 0, 'pareto' => 0];
        $act = ['ishikawa' => 0, 'five_why' => 0, 'eight_d' => 0];

        $result = $this->calculator->computeGlobalScore($cockpit, $plan, $do, $check, $act);

        self::assertFalse($result['initialized']);
        self::assertNull($result['score']);
    }

    public function testPhaseScoresReturnsFourKeys(): void
    {
        $cockpit = [
            'avgAuditCompliancePercent' => 72.5,
            'openCapaCount' => 5,
            'overdueOpenCapas' => 1,
            'capasAwaitingVerification' => 2,
            'overdueAuditPlans' => 0,
        ];
        $plan = ['qqoqccp' => 2, 'amdec' => 1, 'audit_plans' => 2, 'risks' => 3];
        $do = ['capa_open' => 5];
        $check = ['audits' => 2, 'pareto' => 1];
        $act = ['ishikawa' => 1, 'five_why' => 1, 'eight_d' => 1];

        $phases = $this->calculator->computePhaseScores($cockpit, $plan, $do, $check, $act);

        self::assertArrayHasKey('plan', $phases);
        self::assertArrayHasKey('do', $phases);
        self::assertArrayHasKey('check', $phases);
        self::assertArrayHasKey('act', $phases);
        self::assertSame(73, $phases['check']);
    }

    public function testPdcaProgressAveragesAvailablePhases(): void
    {
        $progress = $this->calculator->computePdcaProgress([
            'plan' => 80,
            'do' => 60,
            'check' => 90,
            'act' => null,
        ]);

        self::assertSame(77, $progress);
    }
}
