<?php

declare(strict_types=1);

namespace App\Download\Service;

use App\Download\Application\Port\MauticContactSyncPort;
use App\Download\Application\Port\ResourceRegistryPort;
use App\Download\Domain\Repository\DownloadRequestRepositoryInterface;
use App\Entity\DownloadRequest;

/**
 * Application service: create download request and sync contact to Mautic via REST API.
 */
final class DownloadRequestService
{
    public function __construct(
        private readonly DownloadRequestRepositoryInterface $repository,
        private readonly MauticContactSyncPort $mauticContactSync,
        private readonly ResourceRegistryPort $resourceRegistry,
    ) {
    }

    /**
     * Creates the request and syncs contact to Mautic (POST /api/contacts/new).
     *
     * @throws \InvalidArgumentException If resource does not exist
     * @throws \App\Marketing\Exception\MauticApiException If Mautic API fails
     */
    public function createAndSubmitToMautic(string $email, string $firstname, string $resourceSlug): DownloadRequest
    {
        $resource = $this->resourceRegistry->get($resourceSlug);
        if ($resource === null) {
            throw new \InvalidArgumentException(sprintf('Ressource inconnue: %s', $resourceSlug));
        }

        $downloadRequest = new DownloadRequest($email, $resourceSlug);
        $this->repository->save($downloadRequest);

        $this->mauticContactSync->syncContactForDownload(
            $email,
            $firstname,
            $downloadRequest->getId()->toRfc4122(),
            $resourceSlug,
        );

        return $downloadRequest;
    }
}
