<?php

declare(strict_types=1);

namespace App\UserPreferences;

/**
 * Fonction / poste QHSE (onboarding + préférences).
 */
enum JobFunction: string
{
    case QSE_LEAD = 'qse_lead';
    case QUALITY_LEAD = 'quality_lead';
    case SAFETY_LEAD = 'safety_lead';
    case ENVIRONMENT_LEAD = 'environment_lead';
    case HSE_LEAD = 'hse_lead';
    case QSE_ANIMATOR = 'qse_animator';
    case INTERNAL_AUDITOR = 'internal_auditor';
    case CONTINUOUS_IMPROVEMENT = 'continuous_improvement_lead';
    case PRODUCTION_LEAD = 'production_lead';
    case MAINTENANCE_LEAD = 'maintenance_lead';
    case OPERATIONS_LEAD = 'operations_lead';
    case SITE_DIRECTOR = 'site_director';
    case EXECUTIVE = 'executive';
    case CONSULTANT = 'consultant';
    case FREELANCE = 'freelance';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::QSE_LEAD => 'Responsable QSE',
            self::QUALITY_LEAD => 'Responsable Qualité',
            self::SAFETY_LEAD => 'Responsable Sécurité',
            self::ENVIRONMENT_LEAD => 'Responsable Environnement',
            self::HSE_LEAD => 'Responsable HSE',
            self::QSE_ANIMATOR => 'Animateur QSE',
            self::INTERNAL_AUDITOR => 'Auditeur interne',
            self::CONTINUOUS_IMPROVEMENT => 'Responsable Amélioration continue',
            self::PRODUCTION_LEAD => 'Responsable Production',
            self::MAINTENANCE_LEAD => 'Responsable Maintenance',
            self::OPERATIONS_LEAD => 'Responsable Exploitation',
            self::SITE_DIRECTOR => 'Directeur de site',
            self::EXECUTIVE => 'Dirigeant / Gérant',
            self::CONSULTANT => 'Consultant QHSE',
            self::FREELANCE => 'Prestataire / Indépendant',
            self::OTHER => 'Autre',
        };
    }
}
