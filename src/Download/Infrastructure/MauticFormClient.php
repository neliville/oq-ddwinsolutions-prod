<?php

declare(strict_types=1);

namespace App\Download\Infrastructure;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

/**
 * Client pour soumettre des formulaires Mautic.
 * POST vers https://mautic.example.com/form/submit?formId=XXX
 * Format: mauticform[alias]=value
 */
final class MauticFormClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $mauticFormBaseUrl,
    ) {
    }

    /**
     * Soumet les données au formulaire Mautic.
     *
     * @param int   $formId ID du formulaire Mautic
     * @param array $fields Champs [alias => valeur], ex. ['votre_email' => 'x@y.fr', 'ressource' => 'Modèle 5M']
     *
     * @throws \RuntimeException En cas d'échec HTTP
     */
    public function submit(int $formId, array $fields): void
    {
        if ('' === trim($this->mauticFormBaseUrl)) {
            return;
        }

        $baseUrl = rtrim($this->mauticFormBaseUrl, '/');
        $url = $baseUrl . '/form/submit?formId=' . $formId;

        $formData = [
            'mauticform[formId]' => (string) $formId,
        ];
        foreach ($fields as $alias => $value) {
            if (null !== $value && '' !== (string) $value) {
                $formData['mauticform[' . $alias . ']'] = (string) $value;
            }
        }
        $body = http_build_query($formData);

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                    'X-Requested-With' => 'XMLHttpRequest',
                ],
                'body' => $body,
                'timeout' => 15,
            ]);

            if ($response->getStatusCode() >= 400) {
                throw new \RuntimeException(
                    sprintf('Mautic form submit failed: HTTP %d', $response->getStatusCode())
                );
            }
        } catch (ExceptionInterface $e) {
            throw new \RuntimeException('Mautic form submit failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
