# Rapport — Stabilisation noyau QHSE + dashboard manager

Document produit après implémentation du plan de stabilisation (voir aussi [`qse-noyau-verification-matrice.md`](qse-noyau-verification-matrice.md)).

## Déjà bon

- **Workflow CAPA** : clôture protégée par `CapaWorkflowValidator` (statut `en_attente_de_verification` + texte de vérification d’efficacité). Couvert par des tests unitaires dédiés.
- **Audit multi-référentiel** : périmètre déjà en place dans le code et les tests fonctionnels existants ; non régressé par ce lot.
- **Sécurité de base** : contrôleurs QSE sous `ROLE_USER` ; PDCA avec garde `instanceof User`.
- **Connexion post-login** : redirection vers le tableau de bord utilisateur conservée.

## Manque (résiduel / hors MVP)

- **Lien Doctrine direct Audit ↔ Risque** : absent par conception actuelle ; le brief « lien audit » n’est pas modélisé sur `RiskMatrixEntry`. Le chaînage reste **indirect** (ex. NC d’audit → CAPA via `sourceAuditEvaluation` → risques liés aux CAPA).
- **Checklist sécurité route par route** : non exhaustive dans ce livrable ; à poursuivre si exigence conformité stricte.
- **Parcours manuels outils → CAPA** : la matrice indique une validation manuelle par outil ; non remplacée par de l’automatisé ici.

## Correctifs réalisés (lot code)

1. **`CockpitMetricsRepository`** : compteurs supplémentaires (CAPA en retard, CAPA en attente de vérification, brouillons d’audit anciens, CAPA sans responsable, CAPA en vérification sans commentaire, conformité moyenne des audits), requêtes pour chapitres faibles (agrégat NC/partiel par chapitre), top risques, risques avec révision sous 14 jours, regroupement des CAPA ouvertes par statut.
2. **`DashboardController`** : passage par `buildManagerDashboard()` pour alimenter le template.
3. **`templates/dashboard/index.html.twig`** : six blocs orientés « attention aujourd’hui », KPI outils conservés en dessous ; zone IA avec `id="dashboard-ai-suggestions-slot"` stable.
4. **`QsePdcaController` + template PDCA** : injection des métriques cockpit et liens actionnables vers CAPA, audits, risques et outils d’analyse.
5. **Tests** : `CapaWorkflowValidatorTest`, assertions dashboard, `QsePdcaControllerTest`.

## Priorités (suite possible)

1. Parcours manuels formalisés (checklist par outil) ou tests fonctionnels CAPA « prefill » par outil.
2. Si le métier exige un **filtrage serveur** des listes (CAPA en retard uniquement), ajouter des paramètres de requête sur `app_qse_capa_index` plutôt que des liens génériques.
3. Évaluer une **FK optionnelle** audit → risque uniquement si un cas d’usage documenté l’impose (migration + rétro-remplissage).

## Quick wins

- Réutiliser `getMetrics()` dans d’autres écrans (exports, alertes mail) sans dupliquer la logique.
- Étendre le bloc « Décisions » avec des liens profonds une fois les filtres de liste disponibles.

## Risques techniques

- **Requêtes agrégées** : le dashboard exécute plusieurs requêtes ; à surveiller sous forte volumétrie (cache ou agrégats planifiés si besoin).
- **Cache Symfony** : après modification de constructeurs ou de templates, un `cache:clear` (ou CI qui nettoie `var/cache/test`) évite des faux négatifs en tests.

## Recommandations

- Garder **une seule source** pour les compteurs cockpit (`CockpitMetricsRepository` / `buildManagerDashboard`) et l’étendre plutôt que recopier des DQL dans les contrôleurs.
- Documenter côté produit le **lien indirect audit–risque** pour éviter les attentes erronées sur la matrice des risques seule.
- Maintenir **au moins un test fonctionnel** sur `/dashboard` à chaque évolution des blocs « attention ».
