<?php

declare(strict_types=1);

namespace App\Tests\Unit\Qse;

use App\Entity\Qse\CAPAAction;
use App\Entity\Qse\RiskMatrixEntry;
use App\Qse\Service\RiskCapaPolicy;
use PHPUnit\Framework\TestCase;

final class RiskCapaPolicyTest extends TestCase
{
    public function testIsCriticalWhenScoreAboveThreshold(): void
    {
        $p = new RiskCapaPolicy();
        $e = new RiskMatrixEntry();
        $e->setCriticalityScore(12);

        self::assertTrue($p->isCritical($e));
        self::assertTrue($p->requiresLinkedCapa($e));
    }

    public function testIsCriticalWhenRiskLevelString(): void
    {
        $p = new RiskCapaPolicy();
        $e = new RiskMatrixEntry();
        $e->setCriticalityScore(1);
        $e->setRiskLevel('critical');

        self::assertTrue($p->isCritical($e));
    }

    public function testRequiresLinkedCapaFalseWhenNotCritical(): void
    {
        $p = new RiskCapaPolicy();
        $e = new RiskMatrixEntry();
        $e->setCriticalityScore(4);
        $e->setRiskLevel('low');

        self::assertFalse($p->isCritical($e));
        self::assertFalse($p->requiresLinkedCapa($e));
    }

    public function testAssertCanActivatePassesWhenCriticalWithLinkedCapa(): void
    {
        $p = new RiskCapaPolicy();
        $e = new RiskMatrixEntry();
        $e->setCriticalityScore(20);
        $capa = new CAPAAction();
        $e->addLinkedCapa($capa);

        $p->assertCanActivateCriticalRisk($e);
        $this->addToAssertionCount(1);
    }

    public function testAssertCanActivateThrowsWhenCriticalWithoutCapa(): void
    {
        $p = new RiskCapaPolicy();
        $e = new RiskMatrixEntry();
        $e->setCriticalityScore(20);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CAPA');
        $p->assertCanActivateCriticalRisk($e);
    }
}
