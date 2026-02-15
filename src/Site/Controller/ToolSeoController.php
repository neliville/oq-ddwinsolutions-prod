<?php

namespace App\Site\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur pour les pages SEO dédiées aux outils
 * Ces pages sont optimisées pour le référencement et incluent Schema.org
 */
#[Route('/outil')]
final class ToolSeoController extends AbstractController
{
    #[Route('/ishikawa', name: 'app_tool_seo_ishikawa')]
    public function ishikawa(): Response
    {
        return $this->render('tool_seo/ishikawa.html.twig', [
            'tool' => [
                'name' => 'Diagramme Ishikawa',
                'slug' => 'ishikawa',
                'description' => 'Créez des diagrammes de causes à effets (Ishikawa) professionnels pour identifier les causes racines de vos problèmes qualité.',
                'long_description' => 'Le diagramme Ishikawa, aussi appelé diagramme de causes à effets ou diagramme en arête de poisson, est un outil essentiel de la qualité. Il permet d\'identifier visuellement toutes les causes possibles d\'un problème en les organisant par catégories (Méthodes, Matériel, Main-d\'œuvre, Milieu, Matière, Machine).',
                'features' => [
                    'Interface intuitive avec drag & drop',
                    'Export PDF et JPEG haute qualité',
                    'Sauvegarde automatique en local',
                    'Partage sécurisé avec lien temporaire',
                    'Conforme aux standards ISO 9001',
                ],
                'use_cases' => [
                    'Analyse de non-conformité',
                    'Résolution de problème qualité',
                    'Audit interne et externe',
                    'Amélioration continue (PDCA)',
                    'Formation équipe qualité',
                ],
                'keywords' => 'diagramme ishikawa, causes à effets, arête de poisson, analyse qualité, ISO 9001, résolution problème, lean manufacturing',
                'schema_type' => 'SoftwareApplication',
            ],
        ]);
    }

    #[Route('/5-pourquoi', name: 'app_tool_seo_5pourquoi')]
    public function fiveWhy(): Response
    {
        return $this->render('tool_seo/fivewhy.html.twig', [
            'tool' => [
                'name' => 'Méthode des 5 Pourquoi',
                'slug' => '5-pourquoi',
                'description' => 'Appliquez la méthode des 5 Pourquoi pour identifier la cause racine de vos problèmes de manière systématique et efficace.',
                'long_description' => 'La méthode des 5 Pourquoi est une technique simple mais puissante de résolution de problème. En posant successivement la question "Pourquoi ?" cinq fois, vous remontez jusqu\'à la cause racine du problème, au-delà des symptômes apparents.',
                'features' => [
                    'Interface guidée étape par étape',
                    'Export PDF professionnel',
                    'Sauvegarde de vos analyses',
                    'Structure logique garantie',
                    'Compatible avec les démarches qualité',
                ],
                'use_cases' => [
                    'Troubleshooting technique',
                    'Analyse d\'incident',
                    'Amélioration de processus',
                    'Formation à la résolution de problème',
                    'Documentation qualité',
                ],
                'keywords' => 'méthode 5 pourquoi, 5 why, cause racine, résolution problème, analyse qualité, lean, six sigma',
                'schema_type' => 'SoftwareApplication',
            ],
        ]);
    }

    #[Route('/qqoqccp', name: 'app_tool_seo_qqoqccp')]
    public function qqoqccp(): Response
    {
        return $this->render('tool_seo/qqoqccp.html.twig', [
            'tool' => [
                'name' => 'Méthode QQOQCCP',
                'slug' => 'qqoqccp',
                'description' => 'Utilisez la méthode QQOQCCP (Qui, Quoi, Où, Quand, Comment, Combien, Pourquoi) pour structurer vos analyses et investigations.',
                'long_description' => 'La méthode QQOQCCP, aussi appelée "5W2H" en anglais, est un outil de questionnement systématique qui permet de couvrir tous les aspects d\'une situation. Elle est particulièrement utile pour les audits, les investigations et la collecte d\'informations structurées.',
                'features' => [
                    'Questionnaire structuré',
                    'Export PDF et Excel',
                    'Sauvegarde de vos analyses',
                    'Modèles pré-remplis',
                    'Compatible avec les audits ISO',
                ],
                'use_cases' => [
                    'Audit qualité',
                    'Investigation d\'incident',
                    'Analyse de processus',
                    'Collecte d\'informations',
                    'Préparation de réunion',
                ],
                'keywords' => 'qqoqccp, 5w2h, méthode questionnement, audit qualité, investigation, ISO 9001',
                'schema_type' => 'SoftwareApplication',
            ],
        ]);
    }

    #[Route('/amdec', name: 'app_tool_seo_amdec')]
    public function amdec(): Response
    {
        return $this->render('tool_seo/amdec.html.twig', [
            'tool' => [
                'name' => 'Analyse AMDEC',
                'slug' => 'amdec',
                'description' => 'Réalisez des analyses AMDEC (Analyse des Modes de Défaillance, de leurs Effets et de leur Criticité) pour prévenir les risques.',
                'long_description' => 'L\'AMDEC est une méthode d\'analyse préventive qui permet d\'identifier et d\'évaluer les risques potentiels d\'un processus, d\'un produit ou d\'un système. Elle aide à prioriser les actions correctives et préventives.',
                'features' => [
                    'Calcul automatique de criticité',
                    'Matrice de criticité visuelle',
                    'Export PDF professionnel',
                    'Sauvegarde de vos analyses',
                    'Conforme aux normes qualité',
                ],
                'use_cases' => [
                    'Analyse de risques processus',
                    'Conception de produits',
                    'Maintenance préventive',
                    'Amélioration continue',
                    'Documentation qualité',
                ],
                'keywords' => 'amdec, fmea, analyse risques, modes de défaillance, criticité, prévention, qualité',
                'schema_type' => 'SoftwareApplication',
            ],
        ]);
    }

    #[Route('/pareto', name: 'app_tool_seo_pareto')]
    public function pareto(): Response
    {
        return $this->render('tool_seo/pareto.html.twig', [
            'tool' => [
                'name' => 'Diagramme de Pareto',
                'slug' => 'pareto',
                'description' => 'Créez des diagrammes de Pareto pour identifier les 20% de causes responsables de 80% des problèmes (principe de Pareto).',
                'long_description' => 'Le diagramme de Pareto est un outil graphique qui permet de visualiser la distribution des causes d\'un problème. Il aide à prioriser les actions en se concentrant sur les causes les plus importantes.',
                'features' => [
                    'Graphique automatique',
                    'Calcul des pourcentages cumulés',
                    'Export PDF et image',
                    'Sauvegarde de vos analyses',
                    'Analyse visuelle intuitive',
                ],
                'use_cases' => [
                    'Priorisation des actions',
                    'Analyse de non-conformités',
                    'Amélioration continue',
                    'Présentation managériale',
                    'Décision stratégique',
                ],
                'keywords' => 'diagramme pareto, principe pareto, 80/20, priorisation, analyse qualité, lean',
                'schema_type' => 'SoftwareApplication',
            ],
        ]);
    }

    #[Route('/8d', name: 'app_tool_seo_8d')]
    public function eightD(): Response
    {
        return $this->render('tool_seo/8d.html.twig', [
            'tool' => [
                'name' => 'Méthode 8D',
                'slug' => '8d',
                'description' => 'Appliquez la méthode 8D (8 Disciplines) pour résoudre les problèmes de manière structurée et documentée.',
                'long_description' => 'La méthode 8D est un processus de résolution de problème en 8 étapes qui permet de traiter les problèmes de manière systématique, de la détection à la prévention de la récurrence.',
                'features' => [
                    'Processus guidé en 8 étapes',
                    'Export PDF complet',
                    'Sauvegarde de vos analyses',
                    'Suivi des actions correctives',
                    'Documentation complète',
                ],
                'use_cases' => [
                    'Résolution de problème complexe',
                    'Gestion de réclamation client',
                    'Amélioration continue',
                    'Formation équipe',
                    'Documentation qualité',
                ],
                'keywords' => 'méthode 8d, 8 disciplines, résolution problème, action corrective, qualité, ISO 9001',
                'schema_type' => 'SoftwareApplication',
            ],
        ]);
    }
}
