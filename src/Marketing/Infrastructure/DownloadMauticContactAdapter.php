<?php

declare(strict_types=1);

namespace App\Marketing\Infrastructure;

use App\Download\Application\Port\MauticContactSyncPort;

/**
 * Adapter: sync download request contact to Mautic via REST API.
 * Sends email, firstname, download_request_id, ressource_id (Mautic contact field aliases).
 */
final class DownloadMauticContactAdapter implements MauticContactSyncPort
{
    public function __construct(
        private readonly MauticApiClient $mauticApiClient,
    ) {
    }

    public function syncContactForDownload(
        string $email,
        string $firstname,
        string $downloadRequestId,
        string $ressourceId,
    ): void {
        $this->mauticApiClient->createOrUpdateContact([
            'email' => $email,
            'firstname' => $firstname,
            'download_request_id' => $downloadRequestId,
            'ressource_id' => $ressourceId,
        ]);
    }
}
