<?php

declare(strict_types=1);

namespace App\Admin\Navigation;

use Symfony\Component\HttpFoundation\Request;

/**
 * Source unique des entrées du menu administration (sidebar Twig + menu Knp « admin »).
 */
final class AdminNavigationProvider
{
    /**
     * @return list<array{
     *     label: string,
     *     items: list<array{
     *         label: string,
     *         route: string,
     *         route_parameters?: array<string, scalar|null>,
     *         icon: string,
     *         active_when: array{type: string, ...}
     *     }>
     * }>
     */
    public function getSectionDefinitions(): array
    {
        return [
            [
                'label' => 'Cockpit',
                'items' => [
                    [
                        'label' => 'Dashboard global',
                        'route' => 'app_admin_dashboard_index',
                        'route_parameters' => [],
                        'icon' => 'layout-dashboard',
                        'active_when' => ['type' => 'route_exact', 'route' => 'app_admin_dashboard_index'],
                    ],
                ],
            ],
            [
                'label' => 'Utilisateurs',
                'items' => [
                    [
                        'label' => 'Comptes',
                        'route' => 'app_admin_users_index',
                        'route_parameters' => [],
                        'icon' => 'users',
                        'active_when' => ['type' => 'route_prefix', 'prefix' => 'app_admin_users'],
                    ],
                ],
            ],
            [
                'label' => 'Outils & usage',
                'items' => [
                    [
                        'label' => 'Créations par outil',
                        'route' => 'app_admin_tool_usage_index',
                        'route_parameters' => [],
                        'icon' => 'wrench',
                        'active_when' => ['type' => 'route_prefix', 'prefix' => 'app_admin_tool_usage'],
                    ],
                    [
                        'label' => 'Leads',
                        'route' => 'app_admin_leads_index',
                        'route_parameters' => [],
                        'icon' => 'user-plus',
                        'active_when' => ['type' => 'route_prefix', 'prefix' => 'app_admin_leads'],
                    ],
                ],
            ],
            [
                'label' => 'CAPA',
                'items' => [
                    [
                        'label' => 'Pilotage CAPA',
                        'route' => 'app_admin_qse_capa_overview',
                        'route_parameters' => [],
                        'icon' => 'target',
                        'active_when' => ['type' => 'route_prefix', 'prefix' => 'app_admin_qse_capa'],
                    ],
                ],
            ],
            [
                'label' => 'Audits',
                'items' => [
                    [
                        'label' => 'Référentiels (standards)',
                        'route' => 'app_admin_qse_audit_standards_index',
                        'route_parameters' => [],
                        'icon' => 'book-marked',
                        'active_when' => ['type' => 'route_prefix', 'prefix' => 'app_admin_qse_audit_standards'],
                    ],
                    [
                        'label' => 'Exigences',
                        'route' => 'app_admin_qse_audit_requirements_index',
                        'route_parameters' => [],
                        'icon' => 'list-checks',
                        'active_when' => ['type' => 'route_prefix', 'prefix' => 'app_admin_qse_audit_requirements'],
                    ],
                    [
                        'label' => 'Import CSV exigences',
                        'route' => 'app_admin_qse_req_import_index',
                        'route_parameters' => [],
                        'icon' => 'upload',
                        'active_when' => ['type' => 'route_prefix', 'prefix' => 'app_admin_qse_req_import'],
                    ],
                ],
            ],
            [
                'label' => 'Risques',
                'items' => [
                    [
                        'label' => 'Matrice des risques',
                        'route' => 'app_admin_qse_risk_overview',
                        'route_parameters' => [],
                        'icon' => 'shield-alert',
                        'active_when' => ['type' => 'route_prefix', 'prefix' => 'app_admin_qse_risk'],
                    ],
                ],
            ],
            [
                'label' => 'Contenus',
                'items' => [
                    [
                        'label' => 'Articles blog',
                        'route' => 'app_admin_blog_index',
                        'route_parameters' => [],
                        'icon' => 'file-text',
                        'active_when' => ['type' => 'route_prefix', 'prefix' => 'app_admin_blog'],
                    ],
                    [
                        'label' => 'Catégories',
                        'route' => 'app_admin_categories_index',
                        'route_parameters' => [],
                        'icon' => 'folder',
                        'active_when' => ['type' => 'route_prefix', 'prefix' => 'app_admin_categories'],
                    ],
                    [
                        'label' => 'Tags',
                        'route' => 'app_admin_tags_index',
                        'route_parameters' => [],
                        'icon' => 'tag',
                        'active_when' => ['type' => 'route_prefix', 'prefix' => 'app_admin_tags'],
                    ],
                    [
                        'label' => 'CMS pages légales',
                        'route' => 'app_admin_cms_index',
                        'route_parameters' => [],
                        'icon' => 'file-cog',
                        'active_when' => ['type' => 'route_prefix', 'prefix' => 'app_admin_cms'],
                    ],
                    [
                        'label' => 'Homepage (slots)',
                        'route' => 'app_admin_homepage_slots_index',
                        'route_parameters' => [],
                        'icon' => 'layout-template',
                        'active_when' => ['type' => 'route_prefix', 'prefix' => 'app_admin_homepage_slots'],
                    ],
                    [
                        'label' => 'Témoignages homepage',
                        'route' => 'app_admin_homepage_testimonials_index',
                        'route_parameters' => [],
                        'icon' => 'message-square-quote',
                        'active_when' => ['type' => 'route_prefix', 'prefix' => 'app_admin_homepage_testimonials'],
                    ],
                ],
            ],
            [
                'label' => 'Analytics & conversion',
                'items' => [
                    [
                        'label' => 'Synthèse',
                        'route' => 'app_admin_analytics_index',
                        'route_parameters' => [],
                        'icon' => 'pie-chart',
                        'active_when' => ['type' => 'route_exact', 'route' => 'app_admin_analytics_index'],
                    ],
                    [
                        'label' => 'Trafic',
                        'route' => 'app_admin_analytics_traffic',
                        'route_parameters' => [],
                        'icon' => 'activity',
                        'active_when' => ['type' => 'route_exact', 'route' => 'app_admin_analytics_traffic'],
                    ],
                    [
                        'label' => 'Partages Ishikawa',
                        'route' => 'app_admin_analytics_sharing',
                        'route_parameters' => [],
                        'icon' => 'share-2',
                        'active_when' => ['type' => 'route_exact', 'route' => 'app_admin_analytics_sharing'],
                    ],
                    [
                        'label' => 'Exports fichiers',
                        'route' => 'app_admin_export_analytics_index',
                        'route_parameters' => [],
                        'icon' => 'download',
                        'active_when' => ['type' => 'route_prefix', 'prefix' => 'app_admin_export_analytics'],
                    ],
                ],
            ],
            [
                'label' => 'Plateforme',
                'items' => [
                    [
                        'label' => 'Santé & intégrations',
                        'route' => 'app_admin_platform_health_index',
                        'route_parameters' => [],
                        'icon' => 'heart-pulse',
                        'active_when' => ['type' => 'route_prefix', 'prefix' => 'app_admin_platform_health'],
                    ],
                ],
            ],
            [
                'label' => 'Messages & paramètres',
                'items' => [
                    [
                        'label' => 'Contact',
                        'route' => 'app_admin_contact_index',
                        'route_parameters' => [],
                        'icon' => 'mail',
                        'active_when' => ['type' => 'route_prefix', 'prefix' => 'app_admin_contact'],
                    ],
                    [
                        'label' => 'Newsletter',
                        'route' => 'app_admin_newsletter_index',
                        'route_parameters' => [],
                        'icon' => 'inbox',
                        'active_when' => ['type' => 'route_prefix', 'prefix' => 'app_admin_newsletter'],
                    ],
                    [
                        'label' => 'Logs admin',
                        'route' => 'app_admin_logs_index',
                        'route_parameters' => [],
                        'icon' => 'scroll-text',
                        'active_when' => ['type' => 'route_exact', 'route' => 'app_admin_logs_index'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return list<array{
     *     label: string,
     *     items: list<array{
     *         label: string,
     *         route: string,
     *         route_parameters: array<string, scalar|null>,
     *         icon: string,
     *         active: bool
     *     }>
     * }>
     */
    public function getSectionsForRequest(?Request $request): array
    {
        $currentRoute = $request ? (string) $request->attributes->get('_route', '') : '';

        $out = [];
        foreach ($this->getSectionDefinitions() as $section) {
            $items = [];
            foreach ($section['items'] as $item) {
                $params = $item['route_parameters'] ?? [];
                $items[] = [
                    'label' => $item['label'],
                    'route' => $item['route'],
                    'route_parameters' => $params,
                    'icon' => $item['icon'],
                    'active' => $this->isActive($currentRoute, $request, $item['active_when']),
                ];
            }
            $out[] = [
                'label' => $section['label'],
                'items' => $items,
            ];
        }

        return $out;
    }

    /**
     * Liste plate pour construction du menu Knp (ordre identique à la sidebar).
     *
     * @return list<array{label: string, route: string, route_parameters: array<string, scalar|null>}>
     */
    public function getFlatLinks(): array
    {
        $flat = [];
        foreach ($this->getSectionDefinitions() as $section) {
            foreach ($section['items'] as $item) {
                $flat[] = [
                    'label' => $item['label'],
                    'route' => $item['route'],
                    'route_parameters' => $item['route_parameters'] ?? [],
                ];
            }
        }

        return $flat;
    }

    /**
     * @param array<string, mixed> $activeWhen
     */
    private function isActive(string $currentRoute, ?Request $request, array $activeWhen): bool
    {
        return match ($activeWhen['type']) {
            'route_exact' => $currentRoute === ($activeWhen['route'] ?? ''),
            'route_prefix' => '' !== ($activeWhen['prefix'] ?? '') && str_starts_with($currentRoute, (string) $activeWhen['prefix']),
            default => false,
        };
    }
}
