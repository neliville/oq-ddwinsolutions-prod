# Rapport — stratégie de tests QSE (complément)

Ce document complète l’audit qualité des tests automatisés : inventaire, priorités, dette résiduelle et commandes CI utiles. Il ne remplace pas la configuration PHPUnit (`phpunit.xml.dist`) ni les commentaires dans le code.

## 1. Inventaire de l’existant (référence)

| Zone | Fichiers / suites notables |
|------|---------------------------|
| Cockpit audit → CAPA | `tests/Functional/QseCockpitFunctionalTest.php` |
| Multi-référentiel audit | `tests/Functional/QseAuditMultiReferentialFunctionalTest.php` |
| Validateur CAPA (clôture) | `tests/Unit/Qse/CapaWorkflowValidatorTest.php`, `src/Qse/Service/CapaWorkflowValidator.php` |
| Collaboration (API + vues) | `tests/Functional/CollaborationFunctionalTest.php` |
| Onboarding / préférences | `tests/Functional/OnboardingControllerTest.php`, `tests/Functional/PreferencesControllerTest.php` |
| Tracking / admin | `tests/Unit/Application/Analytics/TrackingEventRecorderTest.php`, `tests/Functional/Admin/AdminPhase2TrackingTest.php`, etc. |
| Outils gratuits | Nombreux tests sous `tests/Functional/` et `tests/Integration/` |

La couverture **quantitative** n’est pas imposée en CI (pas de seuil dans `phpunit.xml.dist`). Pour un rapport HTML local :  
`./bin/phpunit --coverage-html var/coverage-html` (après configuration Xdebug / PCOV si besoin).

## 2. Gaps comblés dans cette itération

| Manque identifié | Complément ajouté |
|------------------|-------------------|
| Matrice des risques / policy CAPA | `tests/Unit/Qse/RiskCapaPolicyTest.php`, `tests/Functional/QseRiskFunctionalTest.php` |
| ISO 45001 non couvert | `tests/Functional/QseAuditIso45001FunctionalTest.php` |
| Workflow CAPA HTTP (clôture) | `tests/Functional/QseCapaWorkflowFunctionalTest.php` |
| Jeton collaboration | `tests/Unit/Collaboration/CollaborationTokenTest.php` |
| Service invitations (expiration) | `tests/Unit/Collaboration/UserInvitationServiceTest.php` |
| Partage CAPA + invitation (login / expiré) | extensions dans `tests/Functional/CollaborationFunctionalTest.php` |
| Calculateur conformité audit | `tests/Unit/Qse/AuditComplianceCalculatorTest.php` |
| Dépôts (requêtes cockpit / CAPA / tracking) | `tests/Repository/CriticalRepositoriesTest.php` |
| Suites PHPUnit ciblées | suites `repository` et `regression` dans `phpunit.xml.dist` |

## 3. Zones encore à risque (dette / quick wins)

- **Composants Live (Symfony UX)** : peu ou pas de tests dédiés ; privilégier des fumées HTTP sur les pages concernées tant que la surface PHP `AsLiveComponent` reste faible.
- **Seuil de couverture CI** : introduire un seuil **progressif** (ex. 35 % puis hausse trimestrielle) une fois une baseline `clover.xml` produite en pipeline.
- **Performance des requêtes** : `CockpitMetricsRepository` reste sensible ; surveiller les requêtes N+1 côté dashboard en profilage manuel.
- **ISO 45001** : un scénario de fumée sur chapitres isolés est en place ; enrichir avec saisie d’évaluation si un besoin métier l’exige.

## 4. Tests jugés les plus critiques pour la production

1. **Clôture CAPA sans vérification d’efficacité** — `CapaWorkflowValidator` + flux HTTP `QseCapaWorkflowFunctionalTest`.
2. **Risque critique sans CAPA liée** — `RiskCapaPolicy` + refus POST `QseRiskController`.
3. **Jetons partage / invitation** — `CollaborationToken` (hash / timing-safe) + partage invité lecture seule.
4. **Agrégats cockpit** — `CriticalRepositoriesTest` / `CockpitMetricsRepository::getMetrics` (non-régression SQL).

## 5. CI — exécution ciblée

Par défaut, `phpunit.xml.dist` définit `defaultTestSuite="unit,functional,integration,repository"` : `./bin/phpunit` exécute ces quatre suites **sans** la suite `regression` (évite les doublons de fichiers et les avertissements PHPUnit).

```bash
# Batterie par défaut (unit + functional + integration + repository)
./bin/phpunit

# Uniquement les requêtes repository
./bin/phpunit --testsuite repository

# Smoke « régression » (liste restreinte, optionnel)
./bin/phpunit --testsuite regression
```

La suite `regression` reprend un sous-ensemble à forte valeur métier ; lancer uniquement cette suite accélère une vérification manuelle ou une job CI dédiée.

## 6. Fichiers de tests ajoutés ou étendus (liste)

- `tests/Unit/Collaboration/CollaborationTokenTest.php`
- `tests/Unit/Collaboration/UserInvitationServiceTest.php`
- `tests/Unit/Collaboration/SharedAccessServiceTest.php`
- `tests/Unit/Qse/RiskCapaPolicyTest.php`
- `tests/Unit/Qse/AuditComplianceCalculatorTest.php`
- `tests/Unit/Qse/CapaWorkflowValidatorTest.php` (complété)
- `tests/Functional/QseCapaWorkflowFunctionalTest.php`
- `tests/Functional/QseRiskFunctionalTest.php`
- `tests/Functional/QseAuditIso45001FunctionalTest.php`
- `tests/Functional/CollaborationFunctionalTest.php` (étendu)
- `tests/Repository/CriticalRepositoriesTest.php`

---

*Document généré dans le cadre du plan « Stratégie de tests (complément sans casser l’existant) » — itération 1.*
