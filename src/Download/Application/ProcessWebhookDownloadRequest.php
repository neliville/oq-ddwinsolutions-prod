<?php

declare(strict_types=1);

namespace App\Download\Application;

use App\Download\Application\Port\MauticContactSyncPort;
use App\Download\Application\Port\ResourceRegistryPort;
use App\Download\Domain\Repository\DownloadRequestRepositoryInterface;
use App\Download\Service\DownloadAccessService;
use App\Entity\DownloadRequest;
use App\Marketing\Exception\MauticApiException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Application service: process webhook payload (n8n → create-from-mautic).
 * Creates DownloadRequest, syncs contact to Mautic via REST API, returns download URL.
 * Si DOWNLOAD_SECRET est défini, retourne l'URL /ressources/download avec token HMAC (pour l'email).
 */
final class ProcessWebhookDownloadRequest
{
    private const EXPIRES_IN_SECONDS = 86400; // 24h

    public function __construct(
        private readonly DownloadRequestRepositoryInterface $repository,
        private readonly ResourceRegistryPort $resourceRegistry,
        private readonly DownloadAccessService $downloadAccessService,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly MauticContactSyncPort $mauticContactSync,
        private readonly string $downloadSecret = '',
    ) {
    }

    /**
     * @param array<string, mixed> $data Raw payload (n8n / Mautic webhook format)
     *
     * @return array{success: true, download_url: string, download_request_id: string, ressource_id: string, expires_in_hours: int}
     *
     * @throws \InvalidArgumentException On validation (missing email, invalid email, unknown resource)
     * @throws MauticApiException On Mautic API failure
     */
    public function execute(array $data): array
    {
        $email = $this->extractEmail($data);
        $firstname = $this->extractFirstname($data);
        $resourceSlug = $this->extractRessourceId($data);

        if ('' === $email) {
            throw new \InvalidArgumentException('Email requis.');
        }
        if (!filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email invalide.');
        }
        if ('' === $resourceSlug) {
            $resourceSlug = 'ishikawa-5m-template';
        }
        if (!$this->resourceRegistry->has($resourceSlug)) {
            throw new \InvalidArgumentException('Ressource inconnue.');
        }

        $downloadRequest = new DownloadRequest($email, $resourceSlug);
        $this->repository->save($downloadRequest);

        $this->mauticContactSync->syncContactForDownload(
            $email,
            $firstname,
            $downloadRequest->getId()->toRfc4122(),
            $resourceSlug,
        );

        $token = $this->downloadAccessService->authorize($downloadRequest);
        $downloadUrl = $this->buildDownloadUrl($resourceSlug, $token);

        return [
            'success' => true,
            'download_url' => $downloadUrl,
            'download_request_id' => $downloadRequest->getId()->toRfc4122(),
            'ressource_id' => $resourceSlug,
            'expires_in_hours' => 24,
        ];
    }

    private function extractEmail(array $data): string
    {
        if ('' !== trim((string) ($data['email'] ?? ''))) {
            return trim((string) $data['email']);
        }
        $contact = $data['mautic.lead_post_save_new'][0]['contact'] ?? $data['mautic.lead_post_save_update'][0]['contact'] ?? null;
        if ($contact !== null) {
            $value = $contact['fields']['core']['email']['value'] ?? $contact['fields']['core']['email']['normalizedValue'] ?? '';
            return trim((string) $value);
        }
        return '';
    }

    private function extractFirstname(array $data): string
    {
        if ('' !== trim((string) ($data['firstname'] ?? $data['first_name'] ?? ''))) {
            return trim((string) ($data['firstname'] ?? $data['first_name'] ?? ''));
        }
        $contact = $data['mautic.lead_post_save_new'][0]['contact'] ?? $data['mautic.lead_post_save_update'][0]['contact'] ?? null;
        if ($contact !== null) {
            $value = $contact['fields']['core']['firstname']['value'] ?? $contact['fields']['core']['firstname']['normalizedValue'] ?? '';
            return trim((string) $value);
        }
        return '';
    }

    private function extractRessourceId(array $data): string
    {
        if ('' !== trim((string) ($data['ressource_id'] ?? $data['resource_id'] ?? ''))) {
            return trim((string) ($data['ressource_id'] ?? $data['resource_id'] ?? ''));
        }
        $contact = $data['mautic.lead_post_save_new'][0]['contact'] ?? $data['mautic.lead_post_save_update'][0]['contact'] ?? null;
        if ($contact !== null) {
            $value = $contact['fields']['core']['ressource_id']['value'] ?? $contact['fields']['core']['ressource_id']['normalizedValue'] ?? '';
            $slug = trim((string) $value);
            if ('' !== $slug) {
                return $slug;
            }
        }
        return '';
    }

    /**
     * Construit l'URL de téléchargement : /ressources/download avec token HMAC si DOWNLOAD_SECRET est défini,
     * sinon /download/access/{token} (lien avec token en base).
     */
    private function buildDownloadUrl(string $resourceSlug, string $accessToken): string
    {
        if ('' !== $this->downloadSecret) {
            $resource = $this->resourceRegistry->get($resourceSlug);
            $relativePath = $resource['path'] ?? null;
            if ($relativePath !== null && $relativePath !== '') {
                $expires = time() + self::EXPIRES_IN_SECONDS;
                $hmacToken = hash_hmac('sha256', $relativePath . $expires, $this->downloadSecret);
                return $this->urlGenerator->generate(
                    'app_download',
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ) . '?' . http_build_query([
                    'file' => $relativePath,
                    'expires' => $expires,
                    'token' => $hmacToken,
                ]);
            }
        }

        return $this->urlGenerator->generate(
            'app_download_access',
            ['token' => $accessToken],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}
