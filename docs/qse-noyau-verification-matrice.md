# Matrice de vérification — Noyau QHSE (brief ↔ code ↔ preuve)

Document de travail pour la stabilisation du noyau métier (CAPA, Audit, Risques, PDCA, sécurité) et du tableau de bord manager. Statuts : **OK** | **Partiel** | **Écart**.

| Domaine | Exigence / question brief | Preuve code | Preuve auto (tests) | Preuve manuelle | Statut | Écart / note |
|--------|---------------------------|-------------|---------------------|-----------------|--------|--------------|
| CAPA | Création depuis outils (routes, `source_tool`, ownership) | `CapaDraftFromToolFactory`, `QseCapaController::prefill`, CTA `capa_soft_cta` | Parcours fonctionnel ciblé si présent | Clic par outil (Ishikawa, 5 Why, AMDEC, 8D, QQOQCCP, Pareto, risque) | Partiel | Dépend des parcours ; ownership vérifié en factory |
| CAPA | Workflow statuts + clôture | `CapaWorkflowValidator`, `QseCapaController` | `tests/Unit/Qse/CapaWorkflowValidatorTest.php` | Formulaire CAPA : `implementation_done` puis `close` | OK | Couverture unitaire sur `assertCanClose` / `closeAfterVerification` |
| CAPA | Métrique « CAPA en retard » cockpit | `CockpitMetricsRepository::getMetrics` (`overdueCapaCount`) | `DashboardControllerTest` (assertion sur libellé / donnée) | — | OK | Absent avant correctif ; ajouté |
| Audit | Multi-référentiel, scoring, suggest CAPA | `QseAuditController`, `AuditComplianceCalculator`, `AuditEvaluationCapaFactory` | `QseAuditMultiReferentialFunctionalTest.php` | ISO 9001 / 14001 / 45001 | OK | Voir tests existants |
| Audit | Lien risque ↔ audit (Doctrine) | `RiskMatrixEntry` sans FK `Audit` ; CAPA a `sourceAuditEvaluation` | — | — | Écart documenté | Lien **indirect** : NC audit → CAPA → risque / métadonnées ; pas de migration MVP |
| Risques | CRUD, scoring, CAPA liées, `reviewAt`, owner | `QseRiskController`, entité | Tests cockpit / ownership si couverts | — | OK | — |
| PDCA | Pilotage actionnable (liens listes) | `QsePdcaController`, `templates/qse/pdca/index.html.twig` | `tests/Functional/QsePdcaControllerTest.php` | — | OK | Liens vers CAPA / audits / risques |
| Dashboard | « Attention aujourd’hui », 6 blocs | `DashboardController`, `CockpitMetricsRepository::buildManagerDashboard`, `templates/dashboard/index.html.twig` | `tests/Functional/DashboardControllerTest.php` (texte + slot IA) | — | OK | KPI outils conservés sous les blocs prioritaires |
| Sécurité | Routes `/dashboard/qse/*`, `ROLE_USER`, `instanceof User` | Contrôleurs QSE | Tests fonctionnels existants + dashboard | Revue route | Partiel | Checklist exhaustive hors scope fichier unique |
| IA | Placeholder stable | `data-feature="ai-placeholder"` + `id="dashboard-ai-suggestions-slot"` | — | — | OK | Sans implémentation métier |

## Synthèse des écarts traités dans le lot

1. **CAPA en retard** : ajout du compteur et exposition dashboard / PDCA.  
2. **Dashboard** : agrégations complémentaires (brouillons audit anciens, conformité moyenne, chapitres faibles, décisions).  
3. **Risque ↔ audit** : décision produit = **documentation** (pas de FK sans besoin MVP).  
4. **PDCA** : liens vers les modules QSE filtrables par usage (listes).
