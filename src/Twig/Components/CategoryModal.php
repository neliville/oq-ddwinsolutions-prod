<?php

namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('category_modal')]
class CategoryModal
{
    use DefaultActionTrait;

    #[LiveProp]
    public bool $isOpen = false;

    #[LiveProp]
    public ?int $editingCategoryId = null;

    #[LiveProp]
    public ?string $editingCategoryName = null;

    #[LiveProp]
    public array $availableCategories = [];

    #[LiveProp]
    public string $selectedCategory = '';

    #[LiveProp]
    public bool $isCustom = false;

    #[LiveProp]
    public string $customCategoryName = '';
}
