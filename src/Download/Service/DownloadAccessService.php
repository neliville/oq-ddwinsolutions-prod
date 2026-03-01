<?php

declare(strict_types=1);

namespace App\Download\Service;

use App\Download\Application\Port\ResourceRegistryPort;
use App\Download\Domain\Repository\DownloadRequestRepositoryInterface;
use App\Entity\DownloadRequest;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Service : validation du token et streaming du fichier.
 */
final class DownloadAccessService
{
    private const TOKEN_LIFETIME_HOURS = 24;
    private const MAX_ACCESS_COUNT = 5;

    public function __construct(
        private readonly DownloadRequestRepositoryInterface $repository,
        private readonly ResourceRegistryPort $resourceRegistry,
        private readonly string $projectDir,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Autorise une demande (appelé par n8n via /api/download/authorize).
     */
    public function authorize(DownloadRequest $downloadRequest): string
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = new \DateTimeImmutable('+' . self::TOKEN_LIFETIME_HOURS . ' hours');

        $downloadRequest->setToken($token);
        $downloadRequest->setExpiresAt($expiresAt);
        $downloadRequest->setStatus(DownloadRequest::STATUS_AUTHORIZED);
        $this->repository->save($downloadRequest);

        $this->logger->info('Download request authorized', [
            'download_request_id' => $downloadRequest->getId()->toRfc4122(),
            'resource' => $downloadRequest->getResourceSlug(),
        ]);

        return $token;
    }

    /**
     * Valide le token et retourne le chemin du fichier. Enregistre l'accès.
     *
     * @throws NotFoundHttpException Si token invalide, expiré ou non autorisé
     */
    public function validateAndStream(string $token): BinaryFileResponse
    {
        $downloadRequest = $this->repository->findOneByToken($token);
        if ($downloadRequest === null) {
            $this->logger->warning('Download access attempt with unknown token');
            throw new NotFoundHttpException('Lien de téléchargement invalide.');
        }

        if ($downloadRequest->getStatus() !== DownloadRequest::STATUS_AUTHORIZED) {
            $this->logger->warning('Download access attempt with non-authorized request', [
                'download_request_id' => $downloadRequest->getId()->toRfc4122(),
            ]);
            throw new NotFoundHttpException('Lien de téléchargement invalide.');
        }

        if ($downloadRequest->isExpired()) {
            $downloadRequest->setStatus(DownloadRequest::STATUS_EXPIRED);
            $this->repository->save($downloadRequest);
            $this->logger->info('Download access denied: token expired', [
                'download_request_id' => $downloadRequest->getId()->toRfc4122(),
            ]);
            throw new NotFoundHttpException('Ce lien a expiré.');
        }

        if ($downloadRequest->getAccessCount() >= self::MAX_ACCESS_COUNT) {
            $this->logger->warning('Download access denied: max count reached', [
                'download_request_id' => $downloadRequest->getId()->toRfc4122(),
            ]);
            throw new NotFoundHttpException('Nombre de téléchargements maximal atteint.');
        }

        $resource = $this->resourceRegistry->get($downloadRequest->getResourceSlug());
        if ($resource === null) {
            throw new NotFoundHttpException('Ressource introuvable.');
        }

        $path = $this->projectDir . '/public/' . $resource['path'];
        if (!is_file($path)) {
            throw new NotFoundHttpException('Le fichier n\'est pas disponible.');
        }

        $downloadRequest->recordAccess();
        $this->repository->save($downloadRequest);

        $this->logger->info('Download access granted', [
            'download_request_id' => $downloadRequest->getId()->toRfc4122(),
            'resource' => $downloadRequest->getResourceSlug(),
        ]);

        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $resource['filename']
        );

        return $response;
    }
}
