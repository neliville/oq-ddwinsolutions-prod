<?php

declare(strict_types=1);

namespace App\Admin\Dashboard;

use App\Newsletter\Exception\MauticApiException;
use App\Newsletter\Infrastructure\MauticClient;
use Doctrine\DBAL\Connection;

/**
 * Résumé léger pour le dashboard et la page « Santé » admin (pas de vanity : états vérifiables).
 */
final class PlatformIntegrationSummaryProvider
{
    public function __construct(
        private readonly MauticClient $mauticClient,
        private readonly string $mauticUrl,
        private readonly ?string $brevoApiKey,
        private readonly Connection $connection,
    ) {
    }

    /**
     * @return array{
     *     mautic_configured: bool,
     *     mautic_reachable: bool|null,
     *     mautic_detail: string,
     *     brevo_configured: bool,
     *     messenger_failed_count: int|null,
     *     messenger_failed_detail: string
     * }
     */
    public function summarize(): array
    {
        $mauticConfigured = '' !== trim($this->mauticUrl);
        $mauticReachable = null;
        $mauticDetail = $mauticConfigured
            ? 'URL Mautic configurée.'
            : 'MAUTIC_URL vide — pas d’appel sortant (normal en dev).';

        if ($mauticConfigured) {
            try {
                $this->mauticClient->getContactFields();
                $mauticReachable = true;
                $mauticDetail = 'API Mautic joignable (GET /fields/contact).';
            } catch (MauticApiException $e) {
                $mauticReachable = false;
                $mauticDetail = sprintf('Erreur API Mautic : %s', $e->getMessage());
            } catch (\Throwable $e) {
                $mauticReachable = false;
                $mauticDetail = sprintf('Erreur : %s', $e->getMessage());
            }
        }

        $brevoConfigured = null !== $this->brevoApiKey && '' !== trim($this->brevoApiKey);

        $failedCount = null;
        $failedDetail = 'Transport Messenger « failed » non inspecté.';
        try {
            /** @var string|int|false $raw */
            $raw = $this->connection->fetchOne(
                'SELECT COUNT(*) FROM messenger_messages WHERE queue_name = ?',
                ['failed']
            );
            $failedCount = false !== $raw ? (int) $raw : 0;
            $failedDetail = sprintf('%d message(s) dans la file « failed ».', $failedCount);
        } catch (\Throwable) {
            $failedDetail = 'Table messenger_messages introuvable ou requête impossible.';
        }

        return [
            'mautic_configured' => $mauticConfigured,
            'mautic_reachable' => $mauticReachable,
            'mautic_detail' => $mauticDetail,
            'brevo_configured' => $brevoConfigured,
            'messenger_failed_count' => $failedCount,
            'messenger_failed_detail' => $failedDetail,
        ];
    }
}
