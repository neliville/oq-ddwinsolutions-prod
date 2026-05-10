<?php

declare(strict_types=1);

namespace App\Tests\Unit\Qse;

use App\Entity\Qse\Audit;
use App\Entity\Qse\AuditEvaluation;
use App\Entity\User;
use App\Qse\Service\AuditComplianceCalculator;
use PHPUnit\Framework\TestCase;

final class AuditComplianceCalculatorTest extends TestCase
{
    public function testRecalculateExcludesNaFromDenominator(): void
    {
        $owner = new User();
        $audit = new Audit();
        $e1 = new AuditEvaluation();
        $e1->setOwner($owner);
        $e1->setScore(3);
        $e2 = new AuditEvaluation();
        $e2->setOwner($owner);
        $e2->setScore(0);
        $audit->addEvaluation($e1);
        $audit->addEvaluation($e2);

        (new AuditComplianceCalculator())->recalculate($audit);

        self::assertSame(100.0, $audit->getGlobalComplianceRate());
        self::assertSame(100, $audit->getGlobalScore());
    }

    public function testRecalculateMixedScores(): void
    {
        $owner = new User();
        $audit = new Audit();
        foreach ([3, 2, 1] as $score) {
            $ev = new AuditEvaluation();
            $ev->setOwner($owner);
            $ev->setScore($score);
            $audit->addEvaluation($ev);
        }

        (new AuditComplianceCalculator())->recalculate($audit);

        self::assertSame(33.33, $audit->getGlobalComplianceRate());
        self::assertSame(33, $audit->getGlobalScore());
    }

    public function testRecalculateOnlyNaYieldsNullRate(): void
    {
        $owner = new User();
        $audit = new Audit();
        $ev = new AuditEvaluation();
        $ev->setOwner($owner);
        $ev->setScore(0);
        $audit->addEvaluation($ev);

        (new AuditComplianceCalculator())->recalculate($audit);

        self::assertNull($audit->getGlobalComplianceRate());
        self::assertNull($audit->getGlobalScore());
    }
}
