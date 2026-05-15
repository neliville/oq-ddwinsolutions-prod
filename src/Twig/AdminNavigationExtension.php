<?php

declare(strict_types=1);

namespace App\Twig;

use App\Admin\Navigation\AdminNavigationProvider;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class AdminNavigationExtension extends AbstractExtension
{
    public function __construct(
        private readonly AdminNavigationProvider $adminNavigationProvider,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('admin_navigation_sections', $this->adminNavigationSections(...)),
        ];
    }

    /**
     * @return list<array{label: string, items: list<array<string, mixed>>}>
     */
    public function adminNavigationSections(): array
    {
        return $this->adminNavigationProvider->getSectionsForRequest($this->requestStack->getCurrentRequest());
    }
}
