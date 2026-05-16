<?php

declare(strict_types=1);

namespace App\Twig\Extension;

use Symfony\Component\Clock\ClockInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class RelativeTimeExtension extends AbstractExtension
{
    public function __construct(
        private readonly ClockInterface $clock,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('time_ago', [$this, 'timeAgo']),
        ];
    }

    public function timeAgo(?\DateTimeInterface $date): string
    {
        if ($date === null) {
            return '—';
        }

        $now = $this->clock->now();
        $seconds = $now->getTimestamp() - $date->getTimestamp();

        if ($seconds < 0) {
            return 'à l’instant';
        }
        if ($seconds < 60) {
            return 'à l’instant';
        }
        if ($seconds < 3600) {
            $m = (int) floor($seconds / 60);

            return $m <= 1 ? 'il y a 1 min' : sprintf('il y a %d min', $m);
        }
        if ($seconds < 86400) {
            $h = (int) floor($seconds / 3600);

            return $h <= 1 ? 'il y a 1 h' : sprintf('il y a %d h', $h);
        }
        if ($seconds < 604800) {
            $d = (int) floor($seconds / 86400);

            return $d <= 1 ? 'il y a 1 jour' : sprintf('il y a %d jours', $d);
        }
        if ($seconds < 2592000) {
            $w = (int) floor($seconds / 604800);

            return $w <= 1 ? 'il y a 1 semaine' : sprintf('il y a %d semaines', $w);
        }

        return $date->format('d/m/Y');
    }
}
