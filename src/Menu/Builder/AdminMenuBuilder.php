<?php

declare(strict_types=1);

namespace App\Menu\Builder;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;

/**
 * Menu Knp « admin » — aligné sur la sidebar cockpit (routes valides uniquement).
 */
class AdminMenuBuilder
{
    public function __construct(
        private readonly FactoryInterface $factory,
    ) {
    }

    public function createAdminMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root');

        $this->addLink($menu, 'Dashboard global', 'app_admin_dashboard_index');
        $this->addLink($menu, 'Comptes utilisateurs', 'app_admin_users_index');
        $this->addLink($menu, 'Outils & créations', 'app_admin_tool_usage_index');
        $this->addLink($menu, 'Leads', 'app_admin_leads_index');
        $this->addLink($menu, 'CAPA (à venir)', 'app_admin_coming_soon', ['topic' => 'capa']);
        $this->addLink($menu, 'Standards d’audit', 'app_admin_qse_audit_standards_index');
        $this->addLink($menu, 'Exigences audit', 'app_admin_qse_audit_requirements_index');
        $this->addLink($menu, 'Import exigences CSV', 'app_admin_qse_req_import_index');
        $this->addLink($menu, 'Risques (à venir)', 'app_admin_coming_soon', ['topic' => 'risques']);
        $this->addLink($menu, 'Articles blog', 'app_admin_blog_index');
        $this->addLink($menu, 'Catégories', 'app_admin_categories_index');
        $this->addLink($menu, 'Tags', 'app_admin_tags_index');
        $this->addLink($menu, 'CMS', 'app_admin_cms_index');
        $this->addLink($menu, 'Analytics — synthèse', 'app_admin_analytics_index');
        $this->addLink($menu, 'Analytics — trafic', 'app_admin_analytics_traffic');
        $this->addLink($menu, 'Analytics — partages', 'app_admin_analytics_sharing');
        $this->addLink($menu, 'Exports fichiers', 'app_admin_export_analytics_index');
        $this->addLink($menu, 'Messages contact', 'app_admin_contact_index');
        $this->addLink($menu, 'Newsletter', 'app_admin_newsletter_index');
        $this->addLink($menu, 'Logs admin', 'app_admin_logs_index');

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
