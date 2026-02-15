<?php

namespace App\Lead\Infrastructure\Brevo;

use App\Entity\Lead;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Synchronisation des leads et abonnés newsletter avec l'API Brevo (contacts, listes, attributs).
 */
class BrevoSyncService
{
    private const API_BASE = 'https://api.brevo.com/v3';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $brevoApiKey,
    ) {
    }

    /**
     * Crée ou met à jour un contact Brevo à partir d'un Lead.
     * Ne fait rien si la clé API Brevo n'est pas configurée.
     */
    public function syncLead(Lead $lead): void
    {
        if ('' === $this->brevoApiKey) {
            return;
        }
        $email = $lead->getEmail();
        if (null === $email || '' === trim($email)) {
            return;
        }

        $attributes = array_filter([
            'NOM' => $lead->getName(),
            'SOURCE' => $lead->getSource(),
            'OUTIL' => $lead->getTool(),
            'SCORE' => $lead->getScore(),
            'TYPE' => $lead->getType(),
            'UTM_SOURCE' => $lead->getUtmSource(),
            'UTM_MEDIUM' => $lead->getUtmMedium(),
            'UTM_CAMPAIGN' => $lead->getUtmCampaign(),
        ], fn ($v) => null !== $v && '' !== (string) $v);

        $payload = [
            'email' => $email,
            'attributes' => (object) $attributes,
            'updateEnabled' => true,
        ];

        $this->httpClient->request('POST', self::API_BASE . '/contacts', [
            'headers' => [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'api-key' => $this->brevoApiKey,
            ],
            'json' => $payload,
        ]);
    }
}
