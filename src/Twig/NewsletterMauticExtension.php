<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Expose l'URL du script Mautic (form/generate.js) pour l'embed newsletter.
 */
final class NewsletterMauticExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly string $mauticUrl,
        private readonly int $mauticFormNewsletterId,
    ) {
    }

    public function getGlobals(): array
    {
        $base = rtrim(trim($this->mauticUrl), '/');
        $scriptUrl = '' !== $base
            ? $base.'/form/generate.js?id='.$this->mauticFormNewsletterId
            : null;

        return [
            'mautic_newsletter_script_url' => $scriptUrl,
        ];
    }
}
