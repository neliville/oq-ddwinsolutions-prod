<?php

declare(strict_types=1);

namespace App\Download\Service;

use App\Download\Application\Port\FormSubmissionPort;
use App\Download\Application\Port\ResourceRegistryPort;
use App\Download\Domain\Repository\DownloadRequestRepositoryInterface;
use App\Entity\DownloadRequest;

/**
 * Service applicatif : création de demande de téléchargement et envoi à Mautic.
 */
final class DownloadRequestService
{
    public function __construct(
        private readonly DownloadRequestRepositoryInterface $repository,
        private readonly FormSubmissionPort $formSubmission,
        private readonly ResourceRegistryPort $resourceRegistry,
        private readonly int $mauticFormId,
    ) {
    }

    /**
     * Crée une demande, l'envoie à Mautic, retourne l'entité.
     *
     * @throws \InvalidArgumentException Si la ressource n'existe pas
     * @throws \RuntimeException Si Mautic échoue
     */
    public function createAndSubmitToMautic(string $email, string $firstname, string $resourceSlug): DownloadRequest
    {
        $resource = $this->resourceRegistry->get($resourceSlug);
        if ($resource === null) {
            throw new \InvalidArgumentException(sprintf('Ressource inconnue: %s', $resourceSlug));
        }

        $downloadRequest = new DownloadRequest($email, $resourceSlug);
        $this->repository->save($downloadRequest);

        $this->formSubmission->submit($this->mauticFormId, [
            'email' => $email,
            'firstname' => $firstname !== '' ? $firstname : null,
            'ressource_id' => $resourceSlug,
            'download_request_id' => $downloadRequest->getId()->toRfc4122(),
        ]);

        return $downloadRequest;
    }
}
