<?php

declare(strict_types=1);

namespace App\Marketing\Infrastructure;

use App\Marketing\Exception\MauticApiException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

/**
 * Communicates with Mautic REST API.
 * Creates or updates contacts via POST /api/contacts/new.
 * Symfony is the backend authority; Mautic is CRM only.
 */
final class MauticApiClient
{
    private const ENDPOINT_CONTACTS_NEW = '/api/contacts/new';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $baseUrl,
        private readonly string $username,
        private readonly string $password,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Create or update a contact in Mautic.
     * If contact exists (by email), Mautic updates it; otherwise creates.
     *
     * @param array<string, mixed> $data Contact data; keys must match Mautic contact field aliases:
     *                                   - email (required)
     *                                   - firstname
     *                                   - download_request_id
     *                                   - ressource_id
     *
     * @throws MauticApiException On HTTP 4xx/5xx or network failure
     */
    public function createOrUpdateContact(array $data): void
    {
        if ('' === trim($this->baseUrl)) {
            $this->logger->debug('Mautic API skipped: MAUTIC_BASE_URL not set');
            return;
        }

        $url = rtrim($this->baseUrl, '/') . self::ENDPOINT_CONTACTS_NEW;
        $auth = base64_encode($this->username . ':' . $this->password);

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Basic ' . $auth,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => $data,
                'timeout' => 15,
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getContent(false);

            if ($statusCode >= 400) {
                $this->logger->error('Mautic API error', [
                    'status_code' => $statusCode,
                    'response_body' => $responseBody,
                    'endpoint' => self::ENDPOINT_CONTACTS_NEW,
                ]);
                throw new MauticApiException(
                    sprintf('Mautic API error: HTTP %d', $statusCode),
                    $statusCode,
                    $responseBody,
                );
            }

            $this->logger->debug('Mautic contact created or updated', [
                'email' => $data['email'] ?? null,
                'status_code' => $statusCode,
            ]);
        } catch (MauticApiException $e) {
            throw $e;
        } catch (ExceptionInterface $e) {
            $this->logger->error('Mautic API request failed', [
                'message' => $e->getMessage(),
                'endpoint' => self::ENDPOINT_CONTACTS_NEW,
            ]);
            throw new MauticApiException(
                'Mautic API request failed: ' . $e->getMessage(),
                0,
                null,
                $e,
            );
        }
    }
}
