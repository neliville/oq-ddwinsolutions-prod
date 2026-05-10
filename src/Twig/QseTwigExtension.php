<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class QseTwigExtension extends AbstractExtension
{
    /** @var array<string, string> */
    private const CAPA_STATUS = [
        'brouillon' => 'Brouillon',
        'a_valider' => 'À valider',
        'validee' => 'Validée',
        'en_cours' => 'En cours',
        'en_attente_de_verification' => 'En attente de vérification d’efficacité',
        'cloturee' => 'Clôturée',
        'reouverte' => 'Rouverte',
        'annulee' => 'Annulée',
    ];

    /** @var array<string, string> */
    private const AUDIT_EXEC = [
        'brouillon' => 'Brouillon',
        'prepare' => 'Préparation',
        'en_cours' => 'En cours',
        'termine' => 'Terminé',
        'valide' => 'Validé',
        'archive' => 'Archivé',
    ];

    /** @var array<string, string> */
    private const AUDIT_PLAN = [
        'brouillon' => 'Brouillon',
        'planifie' => 'Planifié',
        'programme' => 'Programmé',
        'en_cours' => 'En cours',
        'termine' => 'Terminé',
        'archive' => 'Archivé',
    ];

    /** @var array<string, string> */
    private const RISK = [
        'identifie' => 'Identifié',
        'en_analyse' => 'En analyse',
        'maitrise' => 'Maîtrisé',
        'sous_surveillance' => 'Sous surveillance',
        'critique' => 'Critique',
        'cloture' => 'Clôturé',
    ];

    /** @var array<string, string> */
    private const CAPA_TYPE = [
        'corrective' => 'Corrective',
        'preventive' => 'Préventive',
        'maitrise' => 'Maîtrise',
    ];

    public function getFilters(): array
    {
        return [
            new TwigFilter('qse_status_label', [$this, 'statusLabel']),
            new TwigFilter('qse_capa_type_label', [$this, 'capaTypeLabel']),
        ];
    }

    public function statusLabel(?string $value, string $domain = 'capa'): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        $map = match ($domain) {
            'audit_exec' => self::AUDIT_EXEC,
            'audit_plan' => self::AUDIT_PLAN,
            'risk' => self::RISK,
            default => self::CAPA_STATUS,
        };

        return $map[$value] ?? $value;
    }

    public function capaTypeLabel(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return self::CAPA_TYPE[$value] ?? $value;
    }
}
