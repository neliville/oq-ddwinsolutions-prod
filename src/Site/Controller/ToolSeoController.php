<?php

namespace App\Site\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Pages éditoriales/SEO dédiées aux outils (/outil/{slug}).
 * Chaque action expose :
 *   - tool.faq          → rendu HTML + FAQPage JSON-LD
 *   - tool.related_tools → section maillage interne
 */
#[Route('/outil')]
final class ToolSeoController extends AbstractController
{
    private const TOOLS = [
        'ishikawa'  => ['icon' => 'git-branch',      'color' => 'primary',   'route' => 'app_tool_seo_ishikawa',  'name' => 'Diagramme Ishikawa'],
        '5-pourquoi'=> ['icon' => 'help-circle',     'color' => 'info',      'route' => 'app_tool_seo_5pourquoi', 'name' => 'Méthode 5 Pourquoi'],
        'qqoqccp'   => ['icon' => 'list-checks',     'color' => 'secondary', 'route' => 'app_tool_seo_qqoqccp',  'name' => 'Analyse QQOQCCP'],
        'amdec'     => ['icon' => 'alert-triangle',  'color' => 'danger',    'route' => 'app_tool_seo_amdec',     'name' => 'Analyse AMDEC'],
        'pareto'    => ['icon' => 'bar-chart-2',     'color' => 'info',      'route' => 'app_tool_seo_pareto',    'name' => 'Diagramme de Pareto'],
        '8d'        => ['icon' => 'layers',          'color' => 'warning',   'route' => 'app_tool_seo_8d',        'name' => 'Méthode 8D'],
    ];

    private function relatedTools(string ...$slugs): array
    {
        return array_values(array_filter(
            array_map(fn (string $s) => isset(self::TOOLS[$s]) ? array_merge(self::TOOLS[$s], ['slug' => $s]) : null, $slugs)
        ));
    }

    #[Route('/ishikawa', name: 'app_tool_seo_ishikawa')]
    public function ishikawa(): Response
    {
        return $this->render('tool_seo/ishikawa.html.twig', [
            'tool' => [
                'name'             => 'Diagramme Ishikawa',
                'slug'             => 'ishikawa',
                'description'      => 'Créez des diagrammes de causes à effets (Ishikawa) professionnels pour identifier les causes racines de vos problèmes qualité.',
                'long_description' => 'Le diagramme Ishikawa, aussi appelé diagramme de causes à effets ou diagramme en arête de poisson, est un outil essentiel de la qualité. Il permet d\'identifier visuellement toutes les causes possibles d\'un problème en les organisant par catégories (Méthodes, Matériel, Main-d\'œuvre, Milieu, Matière, Machine).',
                'keywords'         => 'diagramme ishikawa, causes à effets, arête de poisson, analyse qualité, ISO 9001, résolution problème, lean manufacturing',
                'features'         => ['Interface intuitive avec drag & drop', 'Export PDF et JPEG haute qualité', 'Sauvegarde automatique', 'Conforme aux standards ISO 9001'],
                'use_cases'        => ['Analyse de non-conformité', 'Résolution de problème qualité', 'Audit interne et externe', 'Amélioration continue (PDCA)', 'Formation équipe qualité'],
                'when_use'         => ['Analyse de non-conformité ou de réclamation client', 'Recherche des causes racines avant actions correctives', 'Revue de processus, audit interne ou préparation certification'],
                'related_tools'    => $this->relatedTools('5-pourquoi', 'qqoqccp', '8d'),
                'faq'              => [
                    ['q' => 'Qu\'est-ce qu\'un diagramme Ishikawa ?',
                     'a' => 'Le diagramme Ishikawa, aussi appelé diagramme de causes à effets ou diagramme en arête de poisson, est un outil visuel qui permet d\'identifier et d\'organiser toutes les causes possibles d\'un problème. Il a été créé par Kaoru Ishikawa dans les années 1960.'],
                    ['q' => 'Comment utiliser le diagramme Ishikawa ?',
                     'a' => 'Définissez d\'abord le problème à analyser, puis ajoutez les catégories principales (Méthodes, Matériel, Main-d\'œuvre, Milieu, Matière, Machine). Identifiez ensuite les causes pour chaque catégorie et exportez votre diagramme en PDF ou JPEG.'],
                    ['q' => 'Quelle est la différence entre un diagramme 5M et 6M ?',
                     'a' => 'Le diagramme 5M regroupe les catégories Méthodes, Matériel, Main-d\'œuvre, Milieu et Matière. Le 6M ajoute une 6e catégorie « Management » ou « Mesure » selon le contexte. Notre outil supporte les deux configurations.'],
                    ['q' => 'L\'outil est-il vraiment gratuit ?',
                     'a' => 'Oui, l\'outil est 100 % gratuit et sans limitation. Vous pouvez créer autant de diagrammes que vous le souhaitez, sans inscription requise pour utiliser l\'outil en ligne.'],
                    ['q' => 'Puis-je sauvegarder mes diagrammes ?',
                     'a' => 'Sans compte, les données sont conservées localement dans votre navigateur. Avec un compte gratuit, vos diagrammes sont sauvegardés en ligne et accessibles depuis n\'importe quel appareil.'],
                    ['q' => 'Les diagrammes sont-ils conformes aux normes ISO ?',
                     'a' => 'Oui, nos diagrammes respectent les standards de qualité ISO 9001 et peuvent être utilisés dans le cadre d\'audits qualité ou de certifications.'],
                ],
            ],
        ]);
    }

    #[Route('/5-pourquoi', name: 'app_tool_seo_5pourquoi')]
    public function fiveWhy(): Response
    {
        return $this->render('tool_seo/fivewhy.html.twig', [
            'tool' => [
                'name'             => 'Méthode des 5 Pourquoi',
                'slug'             => '5-pourquoi',
                'description'      => 'Appliquez la méthode des 5 Pourquoi pour identifier la cause racine de vos problèmes de manière systématique et efficace.',
                'long_description' => 'La méthode des 5 Pourquoi est une technique simple mais puissante de résolution de problème. En posant successivement la question « Pourquoi ? » cinq fois, vous remontez jusqu\'à la cause racine du problème, au-delà des symptômes apparents.',
                'keywords'         => 'méthode 5 pourquoi, 5 why, cause racine, résolution problème, analyse qualité, lean, six sigma',
                'features'         => ['Interface guidée étape par étape', 'Export PDF professionnel', 'Sauvegarde de vos analyses', 'Structure logique garantie', 'Compatible avec les démarches qualité'],
                'use_cases'        => ['Troubleshooting technique', 'Analyse d\'incident', 'Amélioration de processus', 'Formation à la résolution de problème', 'Documentation qualité'],
                'when_use'         => ['Incident, non-conformité, panne, dérive processus', 'Pour aller au-delà de la cause immédiate et éviter les récidives'],
                'related_tools'    => $this->relatedTools('ishikawa', 'qqoqccp', 'pareto'),
                'faq'              => [
                    ['q' => 'Qu\'est-ce que la méthode des 5 Pourquoi ?',
                     'a' => 'La méthode des 5 Pourquoi est une technique de résolution de problème qui consiste à poser la question « Pourquoi ? » de façon répétée (généralement 5 fois) pour remonter de la cause immédiate jusqu\'à la cause racine d\'un problème.'],
                    ['q' => 'Faut-il obligatoirement poser 5 fois la question ?',
                     'a' => 'Non, le chiffre 5 est indicatif. Selon la complexité du problème, 3 ou 4 itérations peuvent suffire. L\'objectif est d\'atteindre la cause racine, pas de respecter un nombre fixe.'],
                    ['q' => 'Quelle est la différence entre les 5 Pourquoi et l\'Ishikawa ?',
                     'a' => 'Les 5 Pourquoi suivent une ligne de causalité unique (en profondeur), tandis que l\'Ishikawa explore simultanément plusieurs familles de causes (en largeur). Les deux méthodes sont complémentaires.'],
                    ['q' => 'L\'outil est-il gratuit ?',
                     'a' => 'Oui, l\'outil est 100 % gratuit. Sans inscription, vos données sont conservées localement. Avec un compte gratuit, vous sauvegardez vos analyses en ligne.'],
                    ['q' => 'Puis-je exporter mon analyse en PDF ?',
                     'a' => 'Oui, chaque analyse 5 Pourquoi peut être exportée en PDF structuré, prêt à intégrer dans vos rapports qualité ou à partager avec votre équipe.'],
                ],
            ],
        ]);
    }

    #[Route('/qqoqccp', name: 'app_tool_seo_qqoqccp')]
    public function qqoqccp(): Response
    {
        return $this->render('tool_seo/qqoqccp.html.twig', [
            'tool' => [
                'name'             => 'Méthode QQOQCCP',
                'slug'             => 'qqoqccp',
                'description'      => 'Utilisez la méthode QQOQCCP (Qui, Quoi, Où, Quand, Comment, Combien, Pourquoi) pour structurer vos analyses et investigations.',
                'long_description' => 'La méthode QQOQCCP, aussi appelée « 5W2H » en anglais, est un outil de questionnement systématique qui permet de couvrir tous les aspects d\'une situation. Elle est particulièrement utile pour les audits, les investigations et la collecte d\'informations structurées.',
                'keywords'         => 'qqoqccp, 5w2h, méthode questionnement, audit qualité, investigation, ISO 9001',
                'features'         => ['Questionnaire structuré en 7 axes', 'Export PDF et Excel', 'Sauvegarde de vos analyses', 'Modèles pré-remplis', 'Compatible avec les audits ISO'],
                'use_cases'        => ['Audit qualité', 'Investigation d\'incident', 'Analyse de processus', 'Collecte d\'informations', 'Préparation de réunion'],
                'when_use'         => ['Audit, investigation, collecte d\'informations', 'Préparation de réunion ou rédaction de procédure'],
                'related_tools'    => $this->relatedTools('ishikawa', '5-pourquoi', 'amdec'),
                'faq'              => [
                    ['q' => 'Qu\'est-ce que la méthode QQOQCCP ?',
                     'a' => 'QQOQCCP est un acronyme pour Qui, Quoi, Où, Quand, Comment, Combien, Pourquoi. C\'est un outil de questionnement exhaustif qui permet d\'analyser une situation sous tous ses angles pour ne rien oublier.'],
                    ['q' => 'Quelle est la différence entre QQOQCCP et 5W2H ?',
                     'a' => '5W2H est la version anglaise (Who, What, Where, When, Why, How, How much). Les deux méthodes sont identiques dans leur principe : couvrir l\'ensemble des dimensions d\'une situation.'],
                    ['q' => 'Dans quels contextes utiliser le QQOQCCP ?',
                     'a' => 'La méthode s\'applique dans les audits qualité, les investigations d\'incidents, la rédaction de procédures, la préparation de réunions ou encore le cadrage de projets.'],
                    ['q' => 'L\'outil est-il gratuit ?',
                     'a' => 'Oui, l\'outil est entièrement gratuit. Créez un compte gratuit pour sauvegarder vos analyses en ligne et les retrouver depuis n\'importe quel appareil.'],
                    ['q' => 'Peut-on exporter l\'analyse en PDF ?',
                     'a' => 'Oui, une fois votre analyse complétée, vous pouvez générer un rapport PDF structuré, directement utilisable pour vos revues qualité ou vos dossiers d\'audit.'],
                ],
            ],
        ]);
    }

    #[Route('/amdec', name: 'app_tool_seo_amdec')]
    public function amdec(): Response
    {
        return $this->render('tool_seo/amdec.html.twig', [
            'tool' => [
                'name'             => 'Analyse AMDEC',
                'slug'             => 'amdec',
                'description'      => 'Réalisez des analyses AMDEC (Analyse des Modes de Défaillance, de leurs Effets et de leur Criticité) pour prévenir les risques.',
                'long_description' => 'L\'AMDEC est une méthode d\'analyse préventive qui permet d\'identifier et d\'évaluer les risques potentiels d\'un processus, d\'un produit ou d\'un système. Elle aide à prioriser les actions correctives et préventives grâce au calcul de l\'indice NPR (Gravité × Occurrence × Détection).',
                'keywords'         => 'amdec, fmea, analyse risques, modes de défaillance, criticité, NPR, prévention, qualité',
                'features'         => ['Calcul automatique de l\'indice NPR', 'Matrice de criticité visuelle', 'Export PDF professionnel', 'Sauvegarde de vos analyses', 'Conforme aux normes qualité'],
                'use_cases'        => ['Analyse de risques processus', 'Conception de produits', 'Maintenance préventive', 'Amélioration continue', 'Documentation qualité'],
                'when_use'         => ['Analyse de risques processus, conception produit, maintenance préventive', 'Exigences IATF 16949, AS9100 ou IATF Automotive'],
                'related_tools'    => $this->relatedTools('qqoqccp', 'pareto', '8d'),
                'faq'              => [
                    ['q' => 'Qu\'est-ce que l\'AMDEC ?',
                     'a' => 'L\'AMDEC (Analyse des Modes de Défaillance, de leurs Effets et de leur Criticité) est une méthode préventive qui permet d\'identifier les risques potentiels d\'un système, d\'un produit ou d\'un processus, et de les prioriser grâce au calcul de l\'indice NPR.'],
                    ['q' => 'Comment se calcule l\'indice NPR ?',
                     'a' => 'Le NPR (Nombre Prioritaire de Risque) est le produit de trois indices : Gravité (G) × Occurrence (O) × Détection (D). Plus le NPR est élevé, plus le risque est prioritaire. Notre outil calcule automatiquement le NPR pour chaque mode de défaillance.'],
                    ['q' => 'Quels sont les différents types d\'AMDEC ?',
                     'a' => 'Il existe trois types principaux : l\'AMDEC Produit (risques liés à la conception), l\'AMDEC Processus (risques liés à la fabrication) et l\'AMDEC Système (risques liés à l\'architecture globale). Notre outil s\'adapte à ces trois types.'],
                    ['q' => 'L\'outil est-il gratuit ?',
                     'a' => 'Oui, l\'outil est entièrement gratuit. Avec un compte gratuit, vous sauvegardez vos analyses AMDEC en ligne et pouvez les recharger à tout moment.'],
                    ['q' => 'L\'AMDEC est-elle obligatoire en IATF ?',
                     'a' => 'L\'AMDEC est exigée par les référentiels IATF 16949 (automobile) et recommandée par l\'AS9100 (aéronautique). Notre outil facilite la documentation et la traçabilité requises par ces normes.'],
                ],
            ],
        ]);
    }

    #[Route('/pareto', name: 'app_tool_seo_pareto')]
    public function pareto(): Response
    {
        return $this->render('tool_seo/pareto.html.twig', [
            'tool' => [
                'name'             => 'Diagramme de Pareto',
                'slug'             => 'pareto',
                'description'      => 'Créez des diagrammes de Pareto pour identifier les 20 % de causes responsables de 80 % des problèmes (principe de Pareto).',
                'long_description' => 'Le diagramme de Pareto est un outil graphique qui permet de visualiser la distribution des causes d\'un problème et d\'identifier les plus impactantes. Basé sur le principe 80/20 de Vilfredo Pareto, il aide à concentrer les efforts d\'amélioration là où ils ont le plus d\'impact.',
                'keywords'         => 'diagramme pareto, principe pareto, 80/20, priorisation, analyse qualité, lean',
                'features'         => ['Graphique automatique avec courbe cumulative', 'Calcul des pourcentages cumulés', 'Export PDF et image', 'Sauvegarde de vos analyses', 'Analyse visuelle intuitive'],
                'use_cases'        => ['Priorisation des actions', 'Analyse de non-conformités', 'Amélioration continue', 'Présentation managériale', 'Décision stratégique'],
                'when_use'         => ['Priorisation des actions, analyse de non-conformités', 'Présentation managériale ou revue de direction'],
                'related_tools'    => $this->relatedTools('ishikawa', 'amdec', '5-pourquoi'),
                'faq'              => [
                    ['q' => 'Qu\'est-ce que le principe de Pareto ?',
                     'a' => 'Le principe de Pareto (ou loi 80/20) stipule que 80 % des effets sont produits par 20 % des causes. En qualité, cela signifie que l\'élimination d\'un petit nombre de causes majeurs supprime la grande majorité des défauts.'],
                    ['q' => 'Comment créer un diagramme de Pareto ?',
                     'a' => 'Collectez vos données (types de défauts, fréquences), classez-les par ordre décroissant, calculez les fréquences cumulées et tracez les barres + courbe cumulative. Notre outil fait tout cela automatiquement à partir de vos données.'],
                    ['q' => 'Quelle est la différence entre Pareto et histogramme ?',
                     'a' => 'Un histogramme montre la distribution des données brutes. Un diagramme de Pareto trie les données par fréquence décroissante et ajoute une courbe cumulative, ce qui permet de visualiser immédiatement les causes prioritaires.'],
                    ['q' => 'L\'outil est-il gratuit ?',
                     'a' => 'Oui, entièrement gratuit. Créez un compte gratuit pour sauvegarder vos diagrammes de Pareto et les retrouver à tout moment.'],
                    ['q' => 'Peut-on combiner Pareto et Ishikawa ?',
                     'a' => 'Absolument. Le Pareto identifie les causes prioritaires (les 20 % les plus impactants), puis l\'Ishikawa approfondit l\'analyse de chaque cause retenue. C\'est une combinaison très efficace en amélioration continue.'],
                ],
            ],
        ]);
    }

    #[Route('/8d', name: 'app_tool_seo_8d')]
    public function eightD(): Response
    {
        return $this->render('tool_seo/8d.html.twig', [
            'tool' => [
                'name'             => 'Méthode 8D',
                'slug'             => '8d',
                'description'      => 'Appliquez la méthode 8D (8 Disciplines) pour résoudre les problèmes de manière structurée et documentée.',
                'long_description' => 'La méthode 8D est un processus de résolution de problème en 8 étapes qui permet de traiter les problèmes de manière systématique, de la détection jusqu\'à la prévention de la récurrence. Très utilisée dans l\'industrie automobile et aéronautique, elle garantit une documentation rigoureuse et une communication claire avec les parties prenantes.',
                'keywords'         => 'méthode 8d, 8 disciplines, résolution problème, action corrective, qualité, ISO 9001, IATF',
                'features'         => ['Processus guidé en 8 étapes', 'Export PDF complet', 'Sauvegarde de vos analyses', 'Suivi des actions correctives', 'Documentation conforme IATF'],
                'use_cases'        => ['Résolution de problème complexe', 'Gestion de réclamation client', 'Amélioration continue', 'Formation équipe', 'Documentation qualité'],
                'when_use'         => ['Réclamation client, non-conformité récurrente, incident majeur', 'Demande de résolution structurée (IATF, aéronautique)'],
                'related_tools'    => $this->relatedTools('ishikawa', '5-pourquoi', 'amdec'),
                'faq'              => [
                    ['q' => 'Qu\'est-ce que la méthode 8D ?',
                     'a' => 'La méthode 8D (8 Disciplines) est un processus structuré de résolution de problème en 8 étapes, de la détection du problème à la prévention de sa récurrence. Elle est largement utilisée dans l\'automobile, l\'aéronautique et l\'industrie en général.'],
                    ['q' => 'Quelles sont les 8 étapes de la méthode 8D ?',
                     'a' => 'D1 : Constituer l\'équipe. D2 : Décrire le problème. D3 : Actions de confinement. D4 : Identifier la cause racine. D5 : Définir les actions correctives. D6 : Mettre en œuvre les actions. D7 : Prévenir la récurrence. D8 : Féliciter l\'équipe.'],
                    ['q' => 'Quand utiliser le 8D plutôt que les 5 Pourquoi ?',
                     'a' => 'Les 5 Pourquoi sont adaptés aux problèmes simples à cause unique. Le 8D est préférable pour les problèmes complexes nécessitant une équipe pluridisciplinaire, des actions de confinement et une documentation formelle (réclamations client, IATF).'],
                    ['q' => 'Le 8D est-il requis par les normes automobiles ?',
                     'a' => 'Oui, le format 8D est exigé ou fortement recommandé par l\'IATF 16949 (automobile) et ses donneurs d\'ordre (PSA, Stellantis, Volkswagen Group, Ford…) pour la résolution des réclamations qualité.'],
                    ['q' => 'L\'outil est-il gratuit ?',
                     'a' => 'Oui, l\'outil 8D est entièrement gratuit. Sauvegardez et exportez vos rapports 8D en PDF avec un compte gratuit.'],
                ],
            ],
        ]);
    }
}
