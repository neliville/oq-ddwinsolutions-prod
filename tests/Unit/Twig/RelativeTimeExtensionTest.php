<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Twig\Extension\RelativeTimeExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class RelativeTimeExtensionTest extends TestCase
{
    public function testTimeAgoMinutes(): void
    {
        $clock = new MockClock('2026-05-16 12:00:00', 'UTC');
        $ext = new RelativeTimeExtension($clock);
        $date = new \DateTimeImmutable('2026-05-16 11:48:00', new \DateTimeZone('UTC'));

        self::assertSame('il y a 12 min', $ext->timeAgo($date));
    }

    public function testTimeAgoNull(): void
    {
        $clock = new MockClock();
        $ext = new RelativeTimeExtension($clock);

        self::assertSame('—', $ext->timeAgo(null));
    }
}
