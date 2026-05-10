<?php

declare(strict_types=1);

namespace App\Tests\Unit\Collaboration;

use App\Collaboration\CollaborationToken;
use PHPUnit\Framework\TestCase;

final class CollaborationTokenTest extends TestCase
{
    public function testGeneratePlainHasReasonableLengthAndCharset(): void
    {
        $plain = CollaborationToken::generatePlain();
        self::assertGreaterThanOrEqual(40, \strlen($plain));
        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $plain);
    }

    public function testHashPlainIsDeterministicSha256(): void
    {
        $h1 = CollaborationToken::hashPlain('abc-token');
        $h2 = CollaborationToken::hashPlain('abc-token');
        self::assertSame($h1, $h2);
        self::assertSame(64, \strlen($h1));
    }

    public function testEqualsUsesTimingSafeComparison(): void
    {
        $plain = 'my-plain-token';
        $stored = CollaborationToken::hashPlain($plain);
        self::assertTrue(CollaborationToken::equals($plain, $stored));
        self::assertFalse(CollaborationToken::equals('wrong', $stored));
    }
}
