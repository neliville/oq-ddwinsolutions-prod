<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Lead\Service\QuotaService;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Centralise les règles d'accès aux fonctionnalités (export, sauvegarde, IA).
 * Branché sur QuotaService pour le freemium (quotas désactivés par défaut côté UX si besoin).
 *
 * BMAD : on ne facture jamais l'outil, on facture l'usage et le confort.
 */
final class FeatureAccessService
{
    public function __construct(
        private readonly Security $security,
        private readonly QuotaService $quotaService,
    ) {
    }

    /**
     * L'utilisateur peut-il exporter (PDF, Word, etc.) ?
     */
    public function canExport(?User $user = null): bool
    {
        $user ??= $this->getCurrentUser();
        if (!$user) {
            return true;
        }

        return $this->quotaService->hasExportQuotaRemaining($user);
    }

    /**
     * L'utilisateur peut-il sauvegarder (cloud, historique) ?
     */
    public function canSave(?User $user = null): bool
    {
        $user ??= $this->getCurrentUser();
        if (!$user) {
            return true;
        }

        return $this->quotaService->canSaveNew($user);
    }

    /**
     * L'utilisateur peut-il utiliser l'IA d'assistance ?
     * Aujourd'hui : oui. Demain : quota ou plan premium quand l'IA sera branchée.
     */
    public function canUseAI(?User $user = null): bool
    {
        $user ??= $this->getCurrentUser();
        if (!$user) {
            return true;
        }
        // TODO: quand IA + quota IA activés → $this->quotaService->hasAIQuotaRemaining($user)
        return true;
    }

    /**
     * Nombre d'exports restants ce mois (null = illimité).
     */
    public function getExportQuotaRemaining(?User $user = null): ?int
    {
        $user ??= $this->getCurrentUser();

        return $user ? $this->quotaService->getExportQuotaRemaining($user) : null;
    }

    /**
     * Nombre de créations sauvegardées autorisées (null = illimité).
     */
    public function getSaveQuotaLimit(?User $user = null): ?int
    {
        $user ??= $this->getCurrentUser();

        return $user ? $this->quotaService->getSaveQuotaLimit($user) : null;
    }

    /**
     * Message court pour l'UX (ex. "Fonctionnalité avancée – bientôt disponible").
     * À afficher près des actions bridées en pré-freemium.
     */
    public function getUpgradeMessage(string $feature = 'export'): string
    {
        return 'Fonctionnalité avancée – bientôt disponible';
    }

    private function getCurrentUser(): ?User
    {
        $user = $this->security->getUser();

        return $user instanceof User ? $user : null;
    }
}
