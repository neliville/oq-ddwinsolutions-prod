<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot(): void
    {
        if (!$this->booted) {
            $tz = $_ENV['APP_TIMEZONE'] ?? $_SERVER['APP_TIMEZONE'] ?? 'Europe/Paris';
            if (\is_string($tz) && '' !== $tz) {
                date_default_timezone_set($tz);
            }
        }

        parent::boot();
    }
}
