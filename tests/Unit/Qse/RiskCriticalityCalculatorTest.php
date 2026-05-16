<?php

declare(strict_types=1);

namespace App\Tests\Unit\Qse;

use App\Entity\Qse\RiskMatrixEntry;
use App\Qse\Service\RiskCriticalityCalculator;
use PHPUnit\Framework\TestCase;

final class RiskCriticalityCalculatorTest extends TestCase
{
    private RiskCriticalityCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new RiskCriticalityCalculator();
    }

    public function testComputeScoreReturnsProductWhenAllSet(): void
    {
        self::assertSame(18, $this->calculator->computeScore(3, 3, 2));
    }

    public function testComputeScoreReturnsNullWhenIncomplete(): void
    {
        self::assertNull($this->calculator->computeScore(3, null, 2));
    }

    public function testDeriveRiskLevelLowBelowMediumThreshold(): void
    {
        self::assertSame(RiskCriticalityCalculator::LEVEL_LOW, $this->calculator->deriveRiskLevel(5));
    }

    public function testDeriveRiskLevelHighBetweenThresholds(): void
    {
        self::assertSame(RiskCriticalityCalculator::LEVEL_HIGH, $this->calculator->deriveRiskLevel(6));
        self::assertSame(RiskCriticalityCalculator::LEVEL_HIGH, $this->calculator->deriveRiskLevel(11));
    }

    public function testDeriveRiskLevelCriticalAtThreshold(): void
    {
        self::assertSame(RiskCriticalityCalculator::LEVEL_CRITICAL, $this->calculator->deriveRiskLevel(12));
    }

    public function testApplyToEntrySetsScoreAndLevel(): void
    {
        $entry = new RiskMatrixEntry();
        $entry->setSeverity(4);
        $entry->setProbability(3);
        $entry->setDetection(2);

        $this->calculator->applyToEntry($entry);

        self::assertSame(24, $entry->getCriticalityScore());
        self::assertSame(RiskCriticalityCalculator::LEVEL_CRITICAL, $entry->getRiskLevel());
    }

    public function testNormalizeForMatrixClampsToFive(): void
    {
        self::assertSame(5, $this->calculator->normalizeForMatrix(10));
        self::assertSame(1, $this->calculator->normalizeForMatrix(0));
    }

    public function testZoneForCellMatchesHeatmapRules(): void
    {
        self::assertSame('low', $this->calculator->zoneForCell(1, 1));
        self::assertSame('medium', $this->calculator->zoneForCell(3, 4));
        self::assertSame('high', $this->calculator->zoneForCell(5, 5));
    }
}
