<?php

declare(strict_types=1);

namespace App\UserPreferences;

enum MainActivity: string
{
    case INDUSTRY = 'industry';
    case RAIL = 'rail';
    case CONSTRUCTION = 'construction';
    case LOGISTICS = 'logistics';
    case MAINTENANCE_INDUSTRY = 'maintenance_industry';
    case FOOD = 'food';
    case HEALTH = 'health';
    case ENERGY = 'energy';
    case SERVICES = 'services';
    case PUBLIC_SECTOR = 'public_sector';
    case CONSULTING = 'consulting';
    case RETAIL = 'retail';
    case ENVIRONMENT = 'environment';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::INDUSTRY => 'Industrie',
            self::RAIL => 'Ferroviaire',
            self::CONSTRUCTION => 'BTP / Chantier',
            self::LOGISTICS => 'Logistique / Transport',
            self::MAINTENANCE_INDUSTRY => 'Maintenance industrielle',
            self::FOOD => 'Agroalimentaire',
            self::HEALTH => 'Santé / Médico-social',
            self::ENERGY => 'Énergie',
            self::SERVICES => 'Services',
            self::PUBLIC_SECTOR => 'Collectivités / Public',
            self::CONSULTING => 'Conseil / Audit',
            self::RETAIL => 'Commerce / Distribution',
            self::ENVIRONMENT => 'Environnement / Déchets',
            self::OTHER => 'Autre',
        };
    }
}
