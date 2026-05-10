<?php

declare(strict_types=1);

namespace App\Collaboration;

use App\Application\Analytics\TrackingEventType;
use App\Entity\TrackingEvent;
use App\Entity\User;
use App\Entity\UserPreferences;
use App\Repository\Qse\CAPAActionRepository;
use App\Repository\TrackingEventRepository;
use App\Repository\UserPreferencesRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Suggestions douces basées sur l’activité récente (cooldown + dismiss dans {@see UserPreferences::collaborationUiState}).
 *
 * @phpstan-type Suggestion array{suggestion_key: string, title: string, body: string, cta_label: string, cta_href: string}
 */
final class CollaborationSuggestionEngine
{
    private const COOLDOWN_SHOWN_DAYS = 5;

    private const COOLDOWN_DISMISS_DAYS = 14;

    public function __construct(
        private readonly TrackingEventRepository $trackingEventRepository,
        private readonly CAPAActionRepository $capaActionRepository,
        private readonly UserPreferencesRepository $userPreferencesRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return Suggestion|null
     */
    public function evaluate(User $user, UserPreferences $preferences): ?array
    {
        $state = $preferences->getCollaborationUiState() ?? [];
        $suggestionsState = \is_array($state['suggestions'] ?? null) ? $state['suggestions'] : [];

        $since14 = new \DateTimeImmutable('-14 days');
        $events = $this->trackingEventRepository->findRecentForUser($user, $since14, 400);

        $candidates = [];

        $exportAuditCount = $this->countAuditJsonExports($events);
        if ($exportAuditCount >= 3) {
            $candidates['share_audit_exports'] = [
                'title' => 'Souhaitez-vous partager cet audit ?',
                'body' => 'Vous avez exporté plusieurs fois vos données d’audit. Un lien sécurisé permet à votre responsable de consulter la synthèse en lecture seule.',
                'cta_label' => 'Partager un audit',
                'cta_href' => '/dashboard/qse/audit',
            ];
        }

        $dashOpens = $this->countEventType($events, TrackingEventType::DASHBOARD_OPENED);
        if ($dashOpens >= 5) {
            $candidates['invite_manager'] = [
                'title' => 'Invitez votre responsable QSE',
                'body' => 'Vous consultez souvent le tableau de bord : un collaborateur peut vous aider sur le pilotage.',
                'cta_label' => 'Inviter un collaborateur',
                'cta_href' => '/dashboard',
            ];
        }

        if ($this->capaActionRepository->countOpenCriticalByOwner($user) >= 2) {
            $candidates['share_critical_capas'] = [
                'title' => 'Partagez ces actions avec votre manager',
                'body' => 'Plusieurs CAPA à criticité élevée sont ouvertes. Un partage lecture seule peut faciliter l’alignement.',
                'cta_label' => 'Voir les CAPA',
                'cta_href' => '/dashboard/qse/capa',
            ];
        }

        $auditDone = $this->countEventTypeSince($events, TrackingEventType::AUDIT_COMPLETED, new \DateTimeImmutable('-10 days'));
        if ($auditDone >= 1) {
            $candidates['send_completed_audit'] = [
                'title' => 'Envoyer à votre responsable ?',
                'body' => 'Un audit terminé ou validé peut être partagé simplement par lien sécurisé.',
                'cta_label' => 'Ouvrir les audits',
                'cta_href' => '/dashboard/qse/audit',
            ];
        }

        foreach ($candidates as $key => $payload) {
            if ($this->isBlockedByState($suggestionsState, $key)) {
                continue;
            }

            return array_merge(['suggestion_key' => $key], $payload);
        }

        return null;
    }

    public function markShown(User $user, string $suggestionKey): void
    {
        $prefs = $this->userPreferencesRepository->getOrCreateForUser($user);
        $state = $prefs->getCollaborationUiState() ?? [];
        if (!isset($state['suggestions']) || !\is_array($state['suggestions'])) {
            $state['suggestions'] = [];
        }
        $state['suggestions'][$suggestionKey] = array_merge(
            \is_array($state['suggestions'][$suggestionKey] ?? null) ? $state['suggestions'][$suggestionKey] : [],
            ['last_shown_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)],
        );
        $prefs->setCollaborationUiState($state);
        $prefs->touchUpdatedAt();
        $this->entityManager->flush();
    }

    public function dismiss(User $user, string $suggestionKey): void
    {
        $prefs = $this->userPreferencesRepository->getOrCreateForUser($user);
        $state = $prefs->getCollaborationUiState() ?? [];
        if (!isset($state['suggestions']) || !\is_array($state['suggestions'])) {
            $state['suggestions'] = [];
        }
        $until = new \DateTimeImmutable('+'.self::COOLDOWN_DISMISS_DAYS.' days');
        $state['suggestions'][$suggestionKey] = array_merge(
            \is_array($state['suggestions'][$suggestionKey] ?? null) ? $state['suggestions'][$suggestionKey] : [],
            ['dismissed_until' => $until->format(\DateTimeInterface::ATOM)],
        );
        $prefs->setCollaborationUiState($state);
        $prefs->touchUpdatedAt();
        $this->entityManager->flush();
    }

    /**
     * @param array<string, mixed> $suggestionsState
     */
    private function isBlockedByState(array $suggestionsState, string $key): bool
    {
        $row = $suggestionsState[$key] ?? null;
        if (!\is_array($row)) {
            return false;
        }
        $now = new \DateTimeImmutable();
        if (isset($row['dismissed_until']) && \is_string($row['dismissed_until'])) {
            try {
                if (new \DateTimeImmutable($row['dismissed_until']) > $now) {
                    return true;
                }
            } catch (\Throwable) {
            }
        }
        if (isset($row['last_shown_at']) && \is_string($row['last_shown_at'])) {
            try {
                $last = new \DateTimeImmutable($row['last_shown_at']);
                if ($last > $now->modify('-'.self::COOLDOWN_SHOWN_DAYS.' days')) {
                    return true;
                }
            } catch (\Throwable) {
            }
        }

        return false;
    }

    /**
     * @param iterable<TrackingEvent> $events
     */
    private function countAuditJsonExports(iterable $events): int
    {
        $n = 0;
        foreach ($events as $e) {
            if ($e->getEventType() !== TrackingEventType::EXPORT_TRIGGERED) {
                continue;
            }
            $meta = $e->getMetadata() ?? [];
            if (($meta['resource_type'] ?? null) === 'qse_audit' && ($meta['format'] ?? null) === 'json') {
                ++$n;
            }
        }

        return $n;
    }

    /**
     * @param iterable<TrackingEvent> $events
     */
    private function countEventType(iterable $events, TrackingEventType $type): int
    {
        $n = 0;
        foreach ($events as $e) {
            if ($e->getEventType() === $type) {
                ++$n;
            }
        }

        return $n;
    }

    /**
     * @param iterable<TrackingEvent> $events
     */
    private function countEventTypeSince(iterable $events, TrackingEventType $type, \DateTimeImmutable $since): int
    {
        $n = 0;
        foreach ($events as $e) {
            if ($e->getEventType() === $type && $e->getCreatedAt() >= $since) {
                ++$n;
            }
        }

        return $n;
    }
}
