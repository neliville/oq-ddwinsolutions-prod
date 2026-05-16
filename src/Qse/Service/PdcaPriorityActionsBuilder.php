<?php

declare(strict_types=1);

namespace App\Qse\Service;

/**
 * Construit la liste « À traiter aujourd’hui » pour le cockpit PDCA.
 */
final class PdcaPriorityActionsBuilder
{
    public function __construct()
    {
    }

    /**
     * @param array<string, mixed> $cockpit
     *
     * @return list<array{
     *   id: string,
     *   label: string,
     *   detail: string,
     *   count: int,
     *   tone: string,
     *   route: string,
     *   cta_primary: string,
     *   cta_secondary: string|null
     * }>
     */
    public function build(array $cockpit): array
    {
        $items = [];

        $overdueCapas = (int) ($cockpit['overdueOpenCapas'] ?? 0);
        if ($overdueCapas > 0) {
            $items[] = [
                'id' => 'capa-overdue',
                'label' => 'CAPA en retard',
                'detail' => sprintf('%d action(s) corrective(s) dépassent l’échéance.', $overdueCapas),
                'count' => $overdueCapas,
                'tone' => 'destructive',
                'route' => 'app_qse_capa_index',
                'cta_primary' => 'Corriger',
                'cta_secondary' => 'Voir',
            ];
        }

        $dueSoon = (int) ($cockpit['dueCapaNext7Days'] ?? 0);
        if ($dueSoon > 0) {
            $items[] = [
                'id' => 'capa-due-soon',
                'label' => 'Échéances sous 7 jours',
                'detail' => sprintf('%d CAPA à traiter cette semaine.', $dueSoon),
                'count' => $dueSoon,
                'tone' => 'amber',
                'route' => 'app_qse_capa_index',
                'cta_primary' => 'Voir',
                'cta_secondary' => null,
            ];
        }

        $overduePlans = (int) ($cockpit['overdueAuditPlans'] ?? 0);
        if ($overduePlans > 0) {
            $items[] = [
                'id' => 'audit-plans-overdue',
                'label' => 'Plans d’audit en retard',
                'detail' => sprintf('%d programme(s) non réalisé(s) à la date prévue.', $overduePlans),
                'count' => $overduePlans,
                'tone' => 'destructive',
                'route' => 'app_qse_audit_index',
                'cta_primary' => 'Voir',
                'cta_secondary' => null,
            ];
        }

        $openNc = (int) ($cockpit['openNonConformEvaluations'] ?? 0);
        if ($openNc > 0) {
            $items[] = [
                'id' => 'nc-open',
                'label' => 'Non-conformités ouvertes',
                'detail' => sprintf('%d évaluation(s) en NC mineure ou majeure.', $openNc),
                'count' => $openNc,
                'tone' => 'destructive',
                'route' => 'app_qse_audit_index',
                'cta_primary' => 'Corriger',
                'cta_secondary' => 'Voir',
            ];
        }

        $criticalNoCapa = (int) ($cockpit['criticalRisksWithoutCapa'] ?? 0);
        if ($criticalNoCapa > 0) {
            $items[] = [
                'id' => 'risks-no-capa',
                'label' => 'Risques critiques sans CAPA',
                'detail' => sprintf('%d risque(s) majeur(s) sans action liée.', $criticalNoCapa),
                'count' => $criticalNoCapa,
                'tone' => 'amber',
                'route' => 'app_qse_risk_index',
                'cta_primary' => 'Affecter',
                'cta_secondary' => 'Voir',
            ];
        }

        $staleDrafts = (int) ($cockpit['staleAuditDrafts'] ?? 0);
        if ($staleDrafts > 0) {
            $items[] = [
                'id' => 'audit-stale',
                'label' => 'Audits brouillon stagnants',
                'detail' => sprintf('%d audit(s) inactif(s) depuis plus de 14 jours.', $staleDrafts),
                'count' => $staleDrafts,
                'tone' => 'sky',
                'route' => 'app_qse_audit_index',
                'cta_primary' => 'Reprendre',
                'cta_secondary' => null,
            ];
        }

        $noResp = (int) ($cockpit['openCapasWithoutResponsible'] ?? 0);
        if ($noResp > 0) {
            $items[] = [
                'id' => 'capa-no-responsible',
                'label' => 'CAPA sans responsable',
                'detail' => sprintf('%d action(s) sans pilote identifié.', $noResp),
                'count' => $noResp,
                'tone' => 'amber',
                'route' => 'app_qse_capa_index',
                'cta_primary' => 'Affecter',
                'cta_secondary' => null,
            ];
        }

        $awaiting = (int) ($cockpit['capasAwaitingVerification'] ?? 0);
        if ($awaiting > 0) {
            $items[] = [
                'id' => 'capa-verification',
                'label' => 'Vérification d’efficacité',
                'detail' => sprintf('%d CAPA en attente de validation.', $awaiting),
                'count' => $awaiting,
                'tone' => 'sky',
                'route' => 'app_qse_capa_index',
                'cta_primary' => 'Voir',
                'cta_secondary' => null,
            ];
        }

        return \array_slice($items, 0, 8);
    }
}
