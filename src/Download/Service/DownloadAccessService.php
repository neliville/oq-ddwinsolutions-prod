<?php

declare(strict_types=1);

namespace App\Download\Service;

use App\Download\Application\Port\ResourceRegistryPort;
use App\Download\Domain\Repository\DownloadRequestRepositoryInterface;
use App\Entity\DownloadRequest;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Service : validation du token et streaming du fichier.
 * Fichiers stockés hors du dossier public. Aucun chemin exposé.
 */
final class DownloadAccessService
{
    private const TOKEN_LIFETIME_HOURS = 24;
    private const MAX_ACCESS_COUNT = 5;

    /** resourceSlug: alphanumeric, dash, underscore only (prevent directory traversal) */
    private const RESOURCE_SLUG_PATTERN = '/^[a-z0-9_-]+$/';

    public function __construct(
        private readonly DownloadRequestRepositoryInterface $repository,
        private readonly ResourceRegistryPort $resourceRegistry,
        private readonly string $downloadsDir,
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
     * Valide le token, incrémente le compteur et stream le fichier.
     *
     * @throws AccessDeniedHttpException Si token invalide, expiré ou non autorisé (403)
     */
    public function validateAndStream(string $token): BinaryFileResponse
    {
        $downloadRequest = $this->repository->findOneByToken($token);
        if ($downloadRequest === null) {
            $this->logger->warning('Download access attempt with unknown token');
            throw new AccessDeniedHttpException('Lien de téléchargement invalide.');
        }

        if ($downloadRequest->getStatus() !== DownloadRequest::STATUS_AUTHORIZED) {
            $this->logger->warning('Download access attempt with non-authorized request', [
                'download_request_id' => $downloadRequest->getId()->toRfc4122(),
            ]);
            throw new AccessDeniedHttpException('Lien de téléchargement invalide.');
        }

        if ($downloadRequest->isExpired()) {
            $downloadRequest->setStatus(DownloadRequest::STATUS_EXPIRED);
            $this->repository->save($downloadRequest);
            $this->logger->info('Download access denied: token expired', [
                'download_request_id' => $downloadRequest->getId()->toRfc4122(),
            ]);
            throw new AccessDeniedHttpException('Ce lien a expiré.');
        }

        if ($downloadRequest->getAccessCount() >= self::MAX_ACCESS_COUNT) {
            $this->logger->warning('Download access denied: max count reached', [
                'download_request_id' => $downloadRequest->getId()->toRfc4122(),
            ]);
            throw new AccessDeniedHttpException('Nombre de téléchargements maximal atteint.');
        }

        $resourceSlug = $downloadRequest->getResourceSlug();
        if (!preg_match(self::RESOURCE_SLUG_PATTERN, $resourceSlug)) {
            $this->logger->warning('Download access denied: invalid resource slug', [
                'resource_slug' => $resourceSlug,
            ]);
            throw new AccessDeniedHttpException('Ressource invalide.');
        }

        $resource = $this->resourceRegistry->get($resourceSlug);
        if ($resource === null) {
            throw new AccessDeniedHttpException('Ressource introuvable.');
        }

        $relativePath = $resource['path'];
        if (str_contains($relativePath, '..') || str_starts_with($relativePath, '/')) {
            $this->logger->warning('Download access denied: path traversal attempt');
            throw new AccessDeniedHttpException('Chemin invalide.');
        }

        $fullPath = $this->downloadsDir . '/' . $relativePath;
        $realPath = realpath($fullPath);
        $canonicalBase = realpath($this->downloadsDir);

        if ($realPath === false || $canonicalBase === false) {
            throw new AccessDeniedHttpException('Le fichier n\'est pas disponible.');
        }

        if (!str_starts_with($realPath, $canonicalBase)) {
            $this->logger->warning('Download access denied: path escape attempt');
            throw new AccessDeniedHttpException('Accès refusé.');
        }

        if (!is_file($realPath)) {
            throw new AccessDeniedHttpException('Le fichier n\'est pas disponible.');
        }

        $downloadRequest->recordAccess();
        $this->repository->save($downloadRequest);

        $this->logger->info('Download access granted', [
            'download_request_id' => $downloadRequest->getId()->toRfc4122(),
            'resource' => $resourceSlug,
        ]);

        $response = new BinaryFileResponse($realPath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $resource['filename']
        );

        return $response;
    }
}
