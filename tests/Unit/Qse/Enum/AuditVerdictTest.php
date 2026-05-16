<?php

declare(strict_types=1);

namespace App\Tests\Unit\Qse\Enum;

use App\Qse\Enum\AuditVerdict;
use PHPUnit\Framework\TestCase;

final class AuditVerdictTest extends TestCase
{
    public function testRequiresAutoCapaOnlyForFormalNonConformities(): void
    {
        self::assertTrue(AuditVerdict::MINOR_NC->requiresAutoCapa());
        self::assertTrue(AuditVerdict::MAJOR_NC->requiresAutoCapa());
        self::assertFalse(AuditVerdict::OBSERVATION->requiresAutoCapa());
        self::assertFalse(AuditVerdict::TO_REVIEW->requiresAutoCapa());
        self::assertFalse(AuditVerdict::CONFORM->requiresAutoCapa());
    }

    public function testSuggestsCapaIncludesObservations(): void
    {
        self::assertTrue(AuditVerdict::OBSERVATION->suggestsCapa());
        self::assertTrue(AuditVerdict::TO_REVIEW->suggestsCapa());
        self::assertFalse(AuditVerdict::CONFORM->suggestsCapa());
    }
}
