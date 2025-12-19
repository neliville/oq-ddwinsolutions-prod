<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class DeleteConfirmationModal
{
    public ?string $id = null;
    public ?string $title = null;
    public ?string $message = null;
    public ?string $deleteUrl = null;
    public ?string $deleteMethod = 'DELETE';
    public ?string $redirectUrl = null;
    public bool $autoShow = false;
}

