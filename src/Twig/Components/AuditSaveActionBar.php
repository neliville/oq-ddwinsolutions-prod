<?php

declare(strict_types=1);

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Barre d’actions Enregistrer (hors flux du formulaire) via attribut HTML form="…".
 *
 * @see templates/components/AuditSaveActionBar.html.twig
 *
 * P2 (optionnel) : une variante Live Component pourrait piloter cette barre (autosave partiel,
 * conflits, spinner réseau) sans dupliquer le POST chapitre — ajouter une route dédiée ou
 * un composant Live par exigence si le métier exige une persistance continue.
 */
#[AsTwigComponent('AuditSaveActionBar')]
final class AuditSaveActionBar
{
    /** id du formulaire cible (ex. audit-chapter-form) */
    public string $formId = 'audit-chapter-form';

    /** desktop | mobile — deux instances complémentaires (responsive) */
    public string $placement = 'desktop';
}
