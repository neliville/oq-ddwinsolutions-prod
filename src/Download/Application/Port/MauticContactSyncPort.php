<?php

declare(strict_types=1);

namespace App\Download\Application\Port;

use App\Marketing\Exception\MauticApiException;

/**
 * Port: sync a download request contact to CRM (Mautic) via REST API.
 * Replaces form submission; Symfony is the backend authority.
 */
interface MauticContactSyncPort
{
    /**
     * Create or update the contact in Mautic with download context.
     *
     * @throws MauticApiException On API failure
     */
    public function syncContactForDownload(
        string $email,
        string $firstname,
        string $downloadRequestId,
        string $ressourceId,
    ): void;
}
