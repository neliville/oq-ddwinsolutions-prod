<?php

declare(strict_types=1);

namespace App\Menu\Builder;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class NavbarMenuBuilder
{
    public function __construct(
        private readonly FactoryInterface $factory,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RequestStack $requestStack,
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

        $request = $this->requestStack->getCurrentRequest();
        $currentRoute = $request?->attributes->get('_route');

        $this->addAnchorItem($menu, 'Accueil', 'app_home_index', 'hero', $currentRoute);
        $this->addAnchorItem($menu, 'Outils', 'app_home_index', 'outils', $currentRoute);
        $this->addAnchorItem($menu, 'Fonctionnalités', 'app_home_index', 'fonctionnalites', $currentRoute);
        $this->addAnchorItem($menu, 'Expertise', 'app_home_index', 'expertise', $currentRoute);

        $this->addRouteItem($menu, 'Blog', 'app_blog_index', $currentRoute);
        $this->addRouteItem($menu, 'Contact', 'app_contact_index', $currentRoute);

        return $menu;
    }

    private function addAnchorItem(ItemInterface $menu, string $label, string $route, string $fragment, ?string $currentRoute): void
    {
        $uri = $this->urlGenerator->generate($route) . '#' . $fragment;
        $isCurrent = $currentRoute === $route && $fragment === 'hero';

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
            'current' => $isCurrent,
        ]);
    }

    private function addRouteItem(ItemInterface $menu, string $label, string $route, ?string $currentRoute): void
    {
        $isCurrent = $currentRoute === $route;

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
            'current' => $isCurrent,
        ]);
    }
}


