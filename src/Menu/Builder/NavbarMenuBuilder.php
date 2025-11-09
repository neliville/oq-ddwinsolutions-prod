<?php

declare(strict_types=1);

namespace App\Menu\Builder;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class NavbarMenuBuilder
{
    public function __construct(
        private readonly FactoryInterface $factory,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function createNavbar(array $options = []): ItemInterface
    {
        $menu = $this->factory->createItem('root', [
            'childrenAttributes' => [
                'class' => 'nav-menu',
            ],
            'attributes' => [
                'class' => 'navbar-nav-wrapper flex-grow-1',
            ],
        ]);

        $this->addAnchorItem($menu, 'Accueil', 'app_home_index', 'hero');
        $this->addAnchorItem($menu, 'Outils', 'app_home_index', 'outils');
        $this->addAnchorItem($menu, 'Fonctionnalités', 'app_home_index', 'fonctionnalites');
        $this->addAnchorItem($menu, 'Expertise', 'app_home_index', 'expertise');

        $this->addRouteItem($menu, 'Blog', 'app_blog_index');
        $this->addRouteItem($menu, 'Contact', 'app_contact_index');

        return $menu;
    }

    private function addAnchorItem(ItemInterface $menu, string $label, string $route, string $fragment): void
    {
        $uri = $this->urlGenerator->generate($route) . '#' . $fragment;

        $menu->addChild($label, [
            'uri' => $uri,
            'attributes' => [
                'class' => 'nav-item',
            ],
            'linkAttributes' => [
                'class' => 'nav-link',
            ],
            'extras' => [
                'translation_domain' => false,
            ],
        ]);
    }

    private function addRouteItem(ItemInterface $menu, string $label, string $route): void
    {
        $menu->addChild($label, [
            'route' => $route,
            'routeParameters' => [],
            'attributes' => [
                'class' => 'nav-item',
            ],
            'linkAttributes' => [
                'class' => 'nav-link',
            ],
            'extras' => [
                'translation_domain' => false,
            ],
        ]);
    }
}


