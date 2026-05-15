<?php

declare(strict_types=1);

namespace App\Menu\Builder;

use App\Admin\Navigation\AdminNavigationProvider;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;

/**
 * Menu Knp « admin » — généré depuis {@see AdminNavigationProvider} (même ordre que la sidebar).
 */
class AdminMenuBuilder
{
    public function __construct(
        private readonly FactoryInterface $factory,
        private readonly AdminNavigationProvider $adminNavigationProvider,
    ) {
    }

    public function createAdminMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root');

        foreach ($this->adminNavigationProvider->getFlatLinks() as $link) {
            $this->addLink($menu, $link['label'], $link['route'], $link['route_parameters']);
        }

        return $menu;
    }

    /**
     * @param array<string, scalar|null> $routeParameters
     */
    private function addLink(ItemInterface $menu, string $label, string $route, array $routeParameters = []): void
    {
        $menu->addChild($label, [
            'route' => $route,
            'routeParameters' => $routeParameters,
            'attributes' => ['class' => 'nav-item'],
            'linkAttributes' => ['class' => 'nav-link'],
        ]);
    }
}
