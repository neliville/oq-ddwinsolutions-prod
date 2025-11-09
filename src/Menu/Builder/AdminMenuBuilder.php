<?php

namespace App\Menu\Builder;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;

class AdminMenuBuilder
{
    public function __construct(
        private readonly FactoryInterface $factory,
    ) {
    }

    public function createAdminMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root');

        $menu->addChild('Dashboard', [
            'route' => 'app_admin_dashboard_index',
            'attributes' => ['class' => 'nav-item'],
            'linkAttributes' => ['class' => 'nav-link'],
        ]);

        $menu->addChild('Articles Blog', [
            'route' => 'app_admin_blog_index',
            'attributes' => ['class' => 'nav-item'],
            'linkAttributes' => ['class' => 'nav-link'],
        ]);

        $menu->addChild('Catégories', [
            'route' => 'app_admin_category_index',
            'attributes' => ['class' => 'nav-item'],
            'linkAttributes' => ['class' => 'nav-link'],
        ]);

        $menu->addChild('Tags', [
            'route' => 'app_admin_tag_index',
            'attributes' => ['class' => 'nav-item'],
            'linkAttributes' => ['class' => 'nav-link'],
        ]);

        $menu->addChild('Messages Contact', [
            'route' => 'app_admin_contact_index',
            'attributes' => ['class' => 'nav-item'],
            'linkAttributes' => ['class' => 'nav-link'],
        ]);

        $menu->addChild('Newsletter', [
            'route' => 'app_admin_newsletter_index',
            'attributes' => ['class' => 'nav-item'],
            'linkAttributes' => ['class' => 'nav-link'],
        ]);

        $menu->addChild('Analytics', [
            'route' => 'app_admin_analytics_index',
            'attributes' => ['class' => 'nav-item'],
            'linkAttributes' => ['class' => 'nav-link'],
        ]);

        $cmsMenu = $menu->addChild('CMS', [
            'route' => 'app_admin_cms_index',
            'attributes' => ['class' => 'nav-item has-children'],
            'linkAttributes' => ['class' => 'nav-link'],
        ]);

        $cmsMenu->addChild('Politique de confidentialité', [
            'route' => 'app_admin_cms_edit',
            'routeParameters' => ['slug' => 'privacy-policy'],
            'attributes' => ['class' => 'nav-item'],
            'linkAttributes' => ['class' => 'nav-link small ps-3'],
        ]);

        $cmsMenu->addChild('Conditions d\'utilisation', [
            'route' => 'app_admin_cms_edit',
            'routeParameters' => ['slug' => 'terms-of-use'],
            'attributes' => ['class' => 'nav-item'],
            'linkAttributes' => ['class' => 'nav-link small ps-3'],
        ]);

        $cmsMenu->addChild('Mentions légales', [
            'route' => 'app_admin_cms_edit',
            'routeParameters' => ['slug' => 'legal-notice'],
            'attributes' => ['class' => 'nav-item'],
            'linkAttributes' => ['class' => 'nav-link small ps-3'],
        ]);

        $menu->addChild('Exports', [
            'route' => 'app_admin_export_analytics_index',
            'attributes' => ['class' => 'nav-item'],
            'linkAttributes' => ['class' => 'nav-link'],
        ]);

        $menu->addChild('Logs', [
            'route' => 'app_admin_logs_index',
            'attributes' => ['class' => 'nav-item'],
            'linkAttributes' => ['class' => 'nav-link'],
        ]);

        $menu->addChild('Utilisateurs', [
            'route' => 'app_admin_user_index',
            'attributes' => ['class' => 'nav-item'],
            'linkAttributes' => ['class' => 'nav-link'],
        ]);

        $menu->addChild('Déconnexion', [
            'route' => 'app_logout',
            'attributes' => ['class' => 'nav-item'],
            'linkAttributes' => ['class' => 'nav-link'],
        ]);

        return $menu;
    }
}

