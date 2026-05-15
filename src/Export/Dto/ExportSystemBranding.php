<?php

declare(strict_types=1);

namespace App\Export\Dto;

/**
 * Branding système OUTILS-QUALITÉ (toujours présent sur les exports).
 */
final readonly class ExportSystemBranding
{
    public const string BRAND_NAME = 'OUTILS-QUALITÉ';

    public const string WEBSITE = 'www.outils-qualite.com';

    public const string COPYRIGHT = '© OUTILS-QUALITÉ - www.outils-qualite.com';

    public const string WATERMARK = 'OUTILS-QUALITÉ';

    public function toArray(): array
    {
        return [
            'brandName' => self::BRAND_NAME,
            'website' => self::WEBSITE,
            'copyright' => self::COPYRIGHT,
            'watermark' => self::WATERMARK,
        ];
    }
}
