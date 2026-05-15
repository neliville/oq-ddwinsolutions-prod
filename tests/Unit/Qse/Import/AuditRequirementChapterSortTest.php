<?php

declare(strict_types=1);

namespace App\Tests\Unit\Qse\Import;

use App\Qse\Import\AuditRequirementChapterSort;
use PHPUnit\Framework\TestCase;

final class AuditRequirementChapterSortTest extends TestCase
{
    public function testSortsNumericChapterPrefixesNaturally(): void
    {
        $sorted = AuditRequirementChapterSort::sortDistinctChapters(['10. Amélioration', '4. Contexte', '9. Évaluation']);

        self::assertSame(['4. Contexte', '9. Évaluation', '10. Amélioration'], $sorted);
    }
}
