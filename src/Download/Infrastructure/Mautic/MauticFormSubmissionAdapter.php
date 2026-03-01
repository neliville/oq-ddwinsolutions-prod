<?php

declare(strict_types=1);

namespace App\Download\Infrastructure\Mautic;

use App\Download\Application\Port\FormSubmissionPort;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

/**
 * Adapter : soumission de formulaires vers Mautic.
 * POST vers {baseUrl}/form/submit?formId=XXX
 */
final class MauticFormSubmissionAdapter implements FormSubmissionPort
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $mauticFormBaseUrl,
    ) {
    }

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
