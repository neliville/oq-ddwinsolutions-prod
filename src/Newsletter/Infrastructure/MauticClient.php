<?php

declare(strict_types=1);

namespace App\Newsletter\Infrastructure;

use App\Newsletter\Exception\MauticApiException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

/**
 * Client HTTP pour l'API REST Mautic.
 * Responsable uniquement de la communication avec l'API.
 */
final class MauticClient
{
    private const ENDPOINT_CONTACTS_NEW = '/api/contacts/new';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $mauticUrl,
        private readonly string $mauticUser,
        private readonly string $mauticPassword,
    ) {
    }

    /**
     * Crée ou met à jour un contact dans Mautic.
     * Si MAUTIC_URL est vide (ex. environnement de test), retourne un tableau vide sans appeler l'API.
     *
     * @param array<string, mixed> $payload Données du contact (email, firstname, tags, etc.)
     * @return array<string, mixed> Réponse Mautic (contact créé/mis à jour)
     *
     * @throws MauticApiException En cas d'échec HTTP (4xx, 5xx)
     */
    public function createOrUpdateContact(array $payload): array
    {
        if ('' === trim($this->mauticUrl)) {
            return ['contact' => ['id' => 0]];
        }

        $baseUrl = rtrim($this->mauticUrl, '/');
        $url = $baseUrl . self::ENDPOINT_CONTACTS_NEW;

        $auth = base64_encode($this->mauticUser . ':' . $this->mauticPassword);

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Basic ' . $auth,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getContent(false);

            if ($statusCode >= 400) {
                throw new MauticApiException(
                    sprintf('Mautic API error: HTTP %d', $statusCode),
                    $statusCode,
                    $responseBody,
                );
            }

            $data = json_decode($responseBody, true);
            if (!\is_array($data)) {
                throw new MauticApiException(
                    'Invalid Mautic API response',
                    $statusCode,
                    $responseBody,
                );
            }

            return $data;
        } catch (MauticApiException $e) {
            throw $e;
        } catch (ExceptionInterface $e) {
            throw new MauticApiException(
                'Mautic API request failed: ' . $e->getMessage(),
                0,
                null,
                $e,
            );
        }
    }
}
