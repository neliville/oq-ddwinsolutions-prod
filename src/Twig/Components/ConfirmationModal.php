<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class ConfirmationModal
{
    public ?string $id = null;
    public ?string $title = 'Confirmation';
    public ?string $message = 'Êtes-vous sûr de vouloir continuer ?';
    public ?string $confirmText = 'Confirmer';
    public ?string $cancelText = 'Annuler';
    public ?string $confirmClass = 'btn-primary';
}

