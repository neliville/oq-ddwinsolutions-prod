<?php

namespace App\Menu\Builder;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Bundle\SecurityBundle\Security;

class MainMenuBuilder
{
    public function __construct(
        private readonly FactoryInterface $factory,
        private readonly Security $security,
    ) {
    }

    public function createMainMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root');

        $menu->addChild('Accueil', [
            'route' => 'app_home_index',
            'attributes' => ['class' => 'nav-item'],
            'linkAttributes' => ['class' => 'nav-link'],
        ]);

        $menu->addChild('Analyse des causes', [
            'route' => 'app_ishikawa_index',
            'attributes' => ['class' => 'nav-item'],
            'linkAttributes' => ['class' => 'nav-link'],
        ]);

        $menu->addChild('Méthode 5 Pourquoi', [
            'route' => 'app_fivewhy_index',
            'attributes' => ['class' => 'nav-item'],
            'linkAttributes' => ['class' => 'nav-link'],
        ]);

        $menu->addChild('Outils', [
            'route' => 'app_outils_index',
            'attributes' => ['class' => 'nav-item'],
            'linkAttributes' => ['class' => 'nav-link'],
        ]);

        $menu->addChild('Blog', [
            'route' => 'app_blog_index',
            'attributes' => ['class' => 'nav-item'],
            'linkAttributes' => ['class' => 'nav-link'],
        ]);

        $menu->addChild('Contact', [
            'route' => 'app_contact_index',
            'attributes' => ['class' => 'nav-item'],
            'linkAttributes' => ['class' => 'nav-link'],
        ]);

        // Affichage conditionnel Login/Logout
        if ($this->security->isGranted('ROLE_USER')) {
            if ($this->security->isGranted('ROLE_ADMIN')) {
                $menu->addChild('Administration', [
                    'route' => 'app_admin_dashboard_index',
                    'attributes' => ['class' => 'nav-item'],
                    'linkAttributes' => ['class' => 'nav-link'],
                ]);
            }
            $menu->addChild('Déconnexion', [
                'route' => 'app_logout',
                'attributes' => ['class' => 'nav-item'],
                'linkAttributes' => ['class' => 'nav-link'],
            ]);
        } else {
            $menu->addChild('Connexion', [
                'route' => 'app_login',
                'attributes' => ['class' => 'nav-item'],
                'linkAttributes' => ['class' => 'nav-link'],
            ]);
        }

        return $menu;
    }
}

