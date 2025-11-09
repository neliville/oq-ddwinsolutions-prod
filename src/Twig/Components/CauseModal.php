<?php

namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('cause_modal')]
class CauseModal
{
    use DefaultActionTrait;

    #[LiveProp]
    public bool $isOpen = false;

    #[LiveProp]
    public ?int $categoryId = null;

    #[LiveProp]
    public ?int $causeIndex = null;

    #[LiveProp]
    public ?string $causeText = null;
}
