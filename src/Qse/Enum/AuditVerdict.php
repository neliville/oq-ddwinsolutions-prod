<?php

declare(strict_types=1);

namespace App\Qse\Enum;

/**
 * Verdict d’audit par exigence (grille ISO étendue).
 * Le champ legacy `score` (0–3) reste synchronisé pour compatibilité / CAPA.
 */
enum AuditVerdict: string
{
    case NOT_EVALUATED = 'not_evaluated';
    case CONFORM = 'conform';
    case OBSERVATION = 'observation';
    case MINOR_NC = 'minor_nc';
    case MAJOR_NC = 'major_nc';
    case TO_REVIEW = 'to_review';
    case NOT_APPLICABLE = 'not_applicable';

    public function label(): string
    {
        return match ($this) {
            self::NOT_EVALUATED => 'Non évalué',
            self::CONFORM => 'Conforme',
            self::OBSERVATION => 'Observation',
            self::MINOR_NC => 'NC mineure',
            self::MAJOR_NC => 'NC majeure',
            self::TO_REVIEW => 'À revoir',
            self::NOT_APPLICABLE => 'N/A',
        };
    }

    /** Filtre cockpit (valeur data-filter sur les cartes). */
    public function filterKey(): string
    {
        return $this->value;
    }

    /**
     * @return list<self> Ordre d’affichage dans les selects
     */
    public static function orderedChoices(): array
    {
        return [
            self::NOT_EVALUATED,
            self::CONFORM,
            self::OBSERVATION,
            self::MINOR_NC,
            self::MAJOR_NC,
            self::TO_REVIEW,
            self::NOT_APPLICABLE,
        ];
    }

    public static function tryFromLegacyScore(?int $score): ?self
    {
        return match ($score) {
            null => null,
            0 => self::NOT_APPLICABLE,
            1 => self::MINOR_NC,
            2 => self::OBSERVATION,
            3 => self::CONFORM,
            default => null,
        };
    }

    public function toLegacyScore(): ?int
    {
        return match ($this) {
            self::NOT_EVALUATED => null,
            self::CONFORM => 3,
            self::OBSERVATION, self::TO_REVIEW => 2,
            self::MINOR_NC, self::MAJOR_NC => 1,
            self::NOT_APPLICABLE => 0,
        };
    }

    public function suggestsCapa(): bool
    {
        return match ($this) {
            self::MINOR_NC, self::MAJOR_NC, self::OBSERVATION, self::TO_REVIEW => true,
            default => false,
        };
    }
}
