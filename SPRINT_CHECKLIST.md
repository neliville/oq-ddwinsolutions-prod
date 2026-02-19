# 📋 Checklist de Sprints - Outils-Qualité.com

## SPRINT 1.1 - Refactoring Controllers (Semaine 1-2)

### Jour 1-2 : AbstractToolController

- [ ] **Créer le fichier** `src/Controller/Tool/AbstractToolController.php`
- [ ] **Implémenter** les méthodes :
  - [ ] `createLeadFromToolUsage()`
  - [ ] `trackUsage()`
  - [ ] `validateData()`
  - [ ] `getToolName()` (abstract)
- [ ] **Injecter** les dépendances :
  - [ ] `LeadService`
  - [ ] `AnalyticsService`
  - [ ] `LoggerInterface`
- [ ] **Tests** :
  - [ ] Test création lead
  - [ ] Test tracking analytics
  - [ ] Test gestion erreurs

### Jour 3 : Migration IshikawaController

- [ ] **Modifier** `src/Controller/Tool/IshikawaController.php`
- [ ] Étendre `AbstractToolController`
- [ ] Supprimer méthodes dupliquées
- [ ] Implémenter `getToolName()` → return 'ishikawa'
- [ ] **Tests** :
  - [ ] Vérifier endpoints fonctionnent
  - [ ] Test save
  - [ ] Test list
  - [ ] Test delete

### Jour 4 : Migration FiveWhyController + Tools/Api/*

- [ ] **Modifier** `src/Controller/Tool/FiveWhyController.php`
- [ ] **Modifier** `src/Tools/Api/IshikawaController.php`
- [ ] **Modifier** `src/Tools/Api/FiveWhyController.php`
- [ ] **Modifier** `src/Tools/Api/AmdecController.php`
- [ ] **Modifier** `src/Tools/Api/ParetoController.php`
- [ ] **Modifier** `src/Tools/Api/QqoqccpController.php`
- [ ] **Modifier** `src/Tools/Api/EightDController.php`
- [ ] **Tests** : Tous les endpoints

### Jour 5-6 : Optimisation Dashboard

- [ ] **Créer** `src/Repository/AnalyticsRepository.php`
- [ ] **Implémenter** `getUserToolCounts()`
- [ ] Requête UNION pour agréger
- [ ] **Modifier** `src/Controller/DashboardController.php`
- [ ] Remplacer 12 requêtes par 1
- [ ] **Tests** :
  - [ ] Benchmark avant/après
  - [ ] Vérifier données correctes
  - [ ] Test avec 0, 10, 100 analyses

### Jour 7 : Sécuriser Logging

- [ ] **Rechercher** tous les `error_log()` :
  ```bash
  grep -rn "error_log" src/
  ```
- [ ] **Remplacer** par `$this->logger->error()`
- [ ] Créer exceptions custom si nécessaire
- [ ] **Tests** :
  - [ ] Vérifier logs dans `var/log/dev.log`
  - [ ] Tester que exceptions ne exposent pas de détails

### Jour 8-9 : Buffer & Documentation

- [ ] **Documenter** les changements
- [ ] **Code review** avec l'équipe
- [ ] **Corriger** les bugs trouvés
- [ ] **Déployer** en pré-production

---

## SPRINT 1.2 - Validation JSON & Sécurité (Semaine 3-4)

### Jour 10-12 : Validation JSON

- [ ] **Créer** `src/Validator/Constraints/ValidToolData.php`
- [ ] **Créer** `src/Validator/Constraints/ValidToolDataValidator.php`
- [ ] **Implémenter** validation pour chaque outil :
  - [ ] Ishikawa
  - [ ] FiveWhy
  - [ ] QQOQCCP
  - [ ] AMDEC
  - [ ] Pareto
  - [ ] 8D
- [ ] **Modifier** tous les controllers pour utiliser `JSON_THROW_ON_ERROR`
- [ ] **Tests** :
  - [ ] JSON valide → OK
  - [ ] JSON malformé → 400
  - [ ] Données manquantes → 400

### Jour 13 : Rate Limiting

- [ ] **Installer** :
  ```bash
  composer require symfony/rate-limiter
  ```
- [ ] **Configurer** `config/packages/rate_limiter.yaml`
- [ ] **Appliquer** sur endpoints publics
- [ ] **Tests** :
  - [ ] 100 req/min → OK
  - [ ] 101e req → 429
  - [ ] Après 1 min → Reset

### Jour 14 : Review & Deploy

- [ ] **Code review**
- [ ] **Tests end-to-end**
- [ ] **Déployer** Sprint 1 complet
- [ ] **Monitoring** : Vérifier métriques

---

## SPRINT 2.1 - Services & Tests (Semaine 5-6)

### Jour 15-17 : BlogController Refactoring

- [ ] **Créer** `src/Service/Blog/BlogMediaService.php`
  - [ ] `uploadImage()`
  - [ ] `processImage()`
- [ ] **Créer** `src/Service/Blog/BlogMetadataService.php`
  - [ ] `generateSlug()`
  - [ ] `handleTags()`
- [ ] **Créer** `src/Service/Blog/BlogPublishingService.php`
  - [ ] `publish()`
- [ ] **Refactoriser** `src/Controller/Admin/BlogController.php`
- [ ] **Tests** :
  - [ ] Tests unitaires services
  - [ ] Tests controller

### Jour 18-24 : Tests Unitaires

- [ ] **Configurer** PHPUnit coverage
- [ ] **Tests** `LeadService` :
  - [ ] calculateScore()
  - [ ] determineType()
  - [ ] persist()
- [ ] **Tests** `AbstractToolController` :
  - [ ] createLeadFromToolUsage()
  - [ ] trackUsage()
- [ ] **Tests** `AnalyticsRepository` :
  - [ ] getUserToolCounts()
- [ ] **Tests** `ValidToolDataValidator` :
  - [ ] Validation de chaque outil
- [ ] **CI/CD** :
  - [ ] Setup GitHub Actions
  - [ ] Coverage minimum 70%

---

## SPRINT 2.2 - Cache & Optimisations (Semaine 7-8)

### Jour 25-27 : Redis Cache

- [ ] **Installer** Redis sur serveur
- [ ] **Configurer** `config/packages/cache.yaml`
- [ ] **Modifier** `AnalyticsRepository` :
  - [ ] Ajouter cache pour `getUserToolCounts()`
  - [ ] Méthode `invalidateUserCache()`
- [ ] **Appliquer** cache sur :
  - [ ] Counts utilisateur
  - [ ] Pages populaires
  - [ ] Tags/catégories
- [ ] **Tests** :
  - [ ] Données cached
  - [ ] Invalidation après modif
  - [ ] Performance avant/après

### Jour 28-29 : Optimisation JSON Storage

- [ ] **Analyser** structures JSON :
  ```bash
  php bin/console app:analyze-json-structures
  ```
- [ ] **Décider** : Normaliser ou JSON columns
- [ ] **Créer** migration si nécessaire
- [ ] **Tester** avec données existantes

### Jour 30 : Buffer & Review

---

## SPRINT 3.1 - Architecture DDD (Semaine 9-10)

### Jour 31-34 : Value Objects & Domain Enrichment

- [ ] **Créer** Value Objects :
  - [ ] `Email`
  - [ ] `LeadScore`
  - [ ] `ToolName`
- [ ] **Enrichir** `Domain/Lead/Lead.php`
- [ ] **Séparer** logique persistance
- [ ] **Tests** Value Objects

### Jour 35-37 : Documentation

- [ ] **Créer** `docs/ARCHITECTURE.md`
- [ ] **Créer** `docs/CONTRIBUTING.md`
- [ ] **Créer** ADRs :
  - [ ] `docs/adr/001-abstract-tool-controller.md`
  - [ ] `docs/adr/002-redis-cache.md`
  - [ ] `docs/adr/003-json-validation.md`

---

## SPRINT 4.1 - Monitoring (Semaine 11-12)

### Jour 38-40 : Setup Monitoring

- [ ] **Configurer** `config/packages/prod/monolog.yaml`
- [ ] **Installer** Sentry (optionnel)
- [ ] **Définir** métriques :
  - [ ] Temps de réponse
  - [ ] Taux d'erreur
  - [ ] SQL lentes
- [ ] **Alerting** sur erreurs critiques

---

## SPRINT 4.2 - Production (Semaine 13-14)

### Jour 41-42 : Load Testing

- [ ] **Installer** k6 ou JMeter
- [ ] **Créer** scénarios de test
- [ ] **Exécuter** tests :
  - [ ] Dashboard 100 users concurrents
  - [ ] API 500 req/min
- [ ] **Analyser** résultats
- [ ] **Optimiser** si bottlenecks

### Jour 43-44 : Déploiement Production

- [ ] **Checklist pré-prod** :
  - [ ] Tests passent
  - [ ] Coverage > 70%
  - [ ] Logs configurés
  - [ ] Rate limiting OK
  - [ ] Redis opérationnel
  - [ ] Monitoring en place
  - [ ] SSL/HTTPS OK
- [ ] **Déployer** selon procédure
- [ ] **Vérifier** health check
- [ ] **Monitorer** 24-48h

---

## 📊 SUIVI QUOTIDIEN

### Template Daily Standup

**Hier** :
- Tâches complétées : ...
- Problèmes rencontrés : ...

**Aujourd'hui** :
- Tâches prévues : ...
- Objectif : ...

**Blocages** :
- Aucun / Liste des blocages

---

## ✅ CRITÈRES DE COMPLÉTION

Une tâche est **TERMINÉE** seulement si :
1. ✅ Code écrit et testé
2. ✅ Tests passent (unit + functional)
3. ✅ Code review approuvé
4. ✅ Documentation à jour
5. ✅ Déployé en pré-production
6. ✅ Validation fonctionnelle OK

---

**Mise à jour** : Cocher les cases au fur et à mesure
**Review** : Fin de chaque sprint
