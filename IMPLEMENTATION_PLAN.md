# Plan d'Implémentation - Outils-Qualité.com
## Version 1.0 - Février 2026

---

## 📊 Vue d'Ensemble

**Durée totale** : 12-16 semaines (3-4 mois)
**Effort estimé** : 1-2 développeurs à temps partiel
**ROI attendu** : -60% dette technique, +50% performance, production sécurisée

---

## PHASE 1 - FONDATIONS CRITIQUES (4 semaines)

### Sprint 1.1 - Refactoring Controllers (Semaine 1-2)

#### Tâche 1.1.1 : Créer AbstractToolController
**Fichier** : `src/Controller/Tool/AbstractToolController.php`
**Durée** : 2 jours
**Priorité** : 🔴 CRITIQUE

**Actions** :
1. Créer la classe abstraite avec les méthodes communes
2. Extraire la logique de création de leads
3. Extraire la logique de tracking analytics
4. Créer des méthodes protégées réutilisables

**Code à créer** :
```php
<?php
namespace App\Controller\Tool;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use App\Application\Lead\LeadService;
use App\Service\Analytics\AnalyticsService;
use Psr\Log\LoggerInterface;

abstract class AbstractToolController extends AbstractController
{
    public function __construct(
        protected readonly LeadService $leadService,
        protected readonly AnalyticsService $analyticsService,
        protected readonly LoggerInterface $logger
    ) {}

    protected function createLeadFromToolUsage(
        Request $request,
        string $tool,
        ?User $user = null
    ): void {
        try {
            $this->leadService->createFromToolUsage($request, $tool, $user);
        } catch (\Exception $e) {
            $this->logger->error('Lead creation failed', [
                'tool' => $tool,
                'exception' => $e
            ]);
        }
    }

    protected function trackUsage(string $tool, ?User $user): void {
        $this->analyticsService->trackToolUsage($tool, $user);
    }

    abstract protected function getToolName(): string;
}
```

**Fichiers à modifier** :
- ✅ `src/Controller/Tool/IshikawaController.php` → Étendre AbstractToolController
- ✅ `src/Controller/Tool/FiveWhyController.php` → Étendre AbstractToolController
- ✅ `src/Tools/Api/IshikawaController.php` → Étendre AbstractToolController
- ✅ `src/Tools/Api/FiveWhyController.php` → Étendre AbstractToolController
- ✅ `src/Tools/Api/AmdecController.php` → Étendre AbstractToolController
- ✅ `src/Tools/Api/ParetoController.php` → Étendre AbstractToolController
- ✅ `src/Tools/Api/QqoqccpController.php` → Étendre AbstractToolController
- ✅ `src/Tools/Api/EightDController.php` → Étendre AbstractToolController

**Tests** :
- [ ] Tests unitaires pour AbstractToolController
- [ ] Tests d'intégration pour chaque controller modifié
- [ ] Vérifier que tous les endpoints répondent correctement

**Résultat attendu** : -550 lignes de code, maintenance facilitée

---

#### Tâche 1.1.2 : Optimiser Dashboard Queries
**Fichiers** : `src/Controller/DashboardController.php`, `src/Repository/AnalyticsRepository.php`
**Durée** : 2 jours
**Priorité** : 🔴 CRITIQUE

**Actions** :
1. Créer une méthode repository qui agrège tous les counts
2. Utiliser une query UNION pour récupérer les données en 1 seule requête
3. Remplacer les 12 appels individuels par 1 seul

**Code à créer** :
```php
// src/Repository/AnalyticsRepository.php
public function getUserToolCounts(int $userId): array
{
    $conn = $this->getEntityManager()->getConnection();

    $sql = "
        SELECT
            'ishikawa' as tool,
            COUNT(*) as count,
            JSON_ARRAYAGG(JSON_OBJECT('id', id, 'title', title, 'created_at', created_at)) as records
        FROM ishikawa_analysis WHERE user_id = :userId
        UNION ALL
        SELECT
            'fivewhy' as tool,
            COUNT(*) as count,
            JSON_ARRAYAGG(JSON_OBJECT('id', id, 'title', title, 'created_at', created_at)) as records
        FROM five_why_analysis WHERE user_id = :userId
        -- ... répéter pour les 6 outils
    ";

    $results = $conn->executeQuery($sql, ['userId' => $userId])->fetchAllAssociative();

    return $this->formatToolCounts($results);
}
```

**Modification du DashboardController** :
```php
// src/Controller/DashboardController.php
public function index(AnalyticsRepository $analyticsRepository): Response
{
    $user = $this->getUser();

    // ❌ AVANT : 12 requêtes
    // $ishikawaCount = $this->ishikawaRepository->countByUser($user->getId());
    // ...

    // ✅ APRÈS : 1 seule requête
    $toolData = $analyticsRepository->getUserToolCounts($user->getId());

    return $this->render('dashboard/index.html.twig', [
        'toolData' => $toolData,
    ]);
}
```

**Tests** :
- [ ] Benchmark : mesurer le temps avant/après
- [ ] Test que tous les counts sont corrects
- [ ] Vérifier avec 0, 10, 100, 1000 analyses

**Résultat attendu** : Dashboard 10-20x plus rapide

---

#### Tâche 1.1.3 : Sécuriser le Logging
**Fichiers** : Tous les Controllers
**Durée** : 1 jour
**Priorité** : 🔴 CRITIQUE

**Actions** :
1. Remplacer tous les `error_log()` par `$this->logger->error()`
2. Créer des exceptions personnalisées
3. Ne jamais exposer les détails d'exception au client

**Pattern à appliquer** :
```php
// ❌ AVANT
try {
    // ...
} catch (\Exception $e) {
    error_log('Erreur : ' . $e->getMessage());
    return new JsonResponse(['error' => $e->getMessage()], 500);
}

// ✅ APRÈS
try {
    // ...
} catch (\Exception $e) {
    $this->logger->error('Operation failed', [
        'operation' => 'tool_save',
        'tool' => $this->getToolName(),
        'exception' => $e
    ]);

    throw new BadRequestHttpException('Invalid data provided');
}
```

**Script de migration** :
```bash
# Rechercher tous les error_log et les remplacer
find src/ -name "*.php" -exec sed -i 's/error_log(/\/\/ TODO: Migrate to logger - error_log(/g' {} \;
```

**Tests** :
- [ ] Vérifier que les logs apparaissent dans `var/log/dev.log`
- [ ] Tester en production que les exceptions ne exposent pas de détails

---

### Sprint 1.2 - Validation JSON & Sécurité (Semaine 3-4)

#### Tâche 1.2.1 : Valider tous les JSON
**Fichiers** : Tous les Controllers Tool
**Durée** : 2-3 jours
**Priorité** : 🔴 CRITIQUE

**Actions** :
1. Créer des contraintes de validation Symfony
2. Utiliser `JSON_THROW_ON_ERROR`
3. Valider les données avant persistance

**Code à créer** :
```php
// src/Validator/Constraints/ValidToolData.php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidToolData extends Constraint
{
    public string $message = 'The tool data is invalid';
    public string $tool;
}

// src/Validator/Constraints/ValidToolDataValidator.php
class ValidToolDataValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        // Valider selon le type d'outil
        match ($constraint->tool) {
            'ishikawa' => $this->validateIshikawa($value),
            'fivewhy' => $this->validateFiveWhy($value),
            // ...
        };
    }
}
```

**Pattern à appliquer dans les controllers** :
```php
use Symfony\Component\Validator\Validator\ValidatorInterface;

public function save(Request $request, ValidatorInterface $validator): JsonResponse
{
    try {
        $data = json_decode(
            $request->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    } catch (\JsonException $e) {
        throw new BadRequestHttpException('Invalid JSON');
    }

    $violations = $validator->validate($data, [
        new ValidToolData(tool: $this->getToolName())
    ]);

    if (count($violations) > 0) {
        throw new BadRequestHttpException('Validation failed');
    }

    // ... suite du traitement
}
```

**Tests** :
- [ ] Test avec JSON valide
- [ ] Test avec JSON malformé
- [ ] Test avec données manquantes
- [ ] Test avec types invalides

---

#### Tâche 1.2.2 : Implémenter Rate Limiting
**Fichiers** : Configuration + Annotations
**Durée** : 1 jour
**Priorité** : 🟡 IMPORTANTE

**Actions** :
1. Installer `symfony/rate-limiter`
2. Configurer les limiters
3. Appliquer sur les endpoints publics

**Installation** :
```bash
composer require symfony/rate-limiter
```

**Configuration** :
```yaml
# config/packages/rate_limiter.yaml
framework:
    rate_limiter:
        public_api:
            policy: 'sliding_window'
            limit: 100
            interval: '1 minute'

        anonymous_tool:
            policy: 'sliding_window'
            limit: 10
            interval: '1 hour'
```

**Application dans les controllers** :
```php
use Symfony\Component\RateLimiter\RateLimiterFactory;

class IshikawaController extends AbstractToolController
{
    #[Route('/api/ishikawa/save', name: 'api_ishikawa_save')]
    public function save(
        Request $request,
        RateLimiterFactory $anonymousToolLimiter
    ): JsonResponse {
        $limiter = $anonymousToolLimiter->create($request->getClientIp());

        if (!$limiter->consume(1)->isAccepted()) {
            throw new TooManyRequestsHttpException();
        }

        // ... suite du traitement
    }
}
```

**Tests** :
- [ ] Test : 100 requêtes/min OK
- [ ] Test : 101e requête → 429 Too Many Requests
- [ ] Test : Après 1 minute, compteur reset

---

## PHASE 2 - AMÉLIORATION STRUCTURELLE (4 semaines)

### Sprint 2.1 - Refactoring Services (Semaine 5-6)

#### Tâche 2.1.1 : Décomposer BlogController
**Durée** : 3 jours
**Priorité** : 🟡 IMPORTANTE

**Actions** :
1. Créer `BlogMediaService`
2. Créer `BlogMetadataService`
3. Créer `BlogPublishingService`
4. Refactoriser le controller

**Services à créer** :

```php
// src/Service/Blog/BlogMediaService.php
class BlogMediaService
{
    public function uploadImage(UploadedFile $file): string
    {
        // Logique upload
    }

    public function processImage(string $imagePath): void
    {
        // Resize, optimize, etc.
    }
}

// src/Service/Blog/BlogMetadataService.php
class BlogMetadataService
{
    public function generateSlug(string $title): string
    {
        // Génération slug unique
    }

    public function handleTags(BlogPost $post, array $tags): void
    {
        // Association tags
    }
}

// src/Service/Blog/BlogPublishingService.php
class BlogPublishingService
{
    public function publish(BlogPost $post): void
    {
        // Validation, indexation, notification
    }
}
```

**Refactoring du controller** :
```php
// src/Controller/Admin/BlogController.php
public function create(
    Request $request,
    BlogMediaService $mediaService,
    BlogMetadataService $metadataService,
    BlogPublishingService $publishingService
): Response {
    // ✅ 150 lignes au lieu de 374

    if ($form->getData()->getImage()) {
        $imagePath = $mediaService->uploadImage($form->getData()->getImage());
        $mediaService->processImage($imagePath);
    }

    $metadataService->generateSlug($post->getTitle());
    $metadataService->handleTags($post, $form->getData()->getTags());

    if ($publish) {
        $publishingService->publish($post);
    }
}
```

**Tests** :
- [ ] Tests unitaires pour chaque service
- [ ] Tests d'intégration pour le controller
- [ ] Vérifier que les uploads fonctionnent

---

#### Tâche 2.1.2 : Tests Unitaires (70%+ Coverage)
**Durée** : 5 jours
**Priorité** : 🟡 IMPORTANTE

**Actions** :
1. Configurer PHPUnit avec coverage
2. Écrire tests pour logique critique
3. CI/CD avec coverage minimum

**Priorisation des tests** :

| Composant | Priority | Tests |
|-----------|----------|-------|
| `LeadService` | 🔴 | Scoring, classification, persist |
| `AbstractToolController` | 🔴 | Lead creation, tracking |
| `AnalyticsRepository` | 🔴 | Agrégations, counts |
| `BlogMediaService` | 🟡 | Upload, process |
| `ValidToolDataValidator` | 🔴 | Validation JSON |

**Configuration PHPUnit** :
```xml
<!-- phpunit.xml.dist -->
<phpunit>
    <coverage>
        <include>
            <directory>src/Application</directory>
            <directory>src/Domain</directory>
            <directory>src/Service</directory>
        </include>
        <report>
            <html outputDirectory="var/coverage"/>
        </report>
    </coverage>
</phpunit>
```

**Exemple de test** :
```php
// tests/Application/Lead/LeadServiceTest.php
class LeadServiceTest extends KernelTestCase
{
    public function testCalculateScoreForNewsletter(): void
    {
        $lead = new DomainLead(
            email: 'test@example.com',
            source: 'newsletter',
            metadata: []
        );

        $score = $this->leadService->calculateScore($lead);

        $this->assertGreaterThanOrEqual(20, $score);
        $this->assertLessThanOrEqual(40, $score);
    }
}
```

**CI/CD** :
```yaml
# .github/workflows/tests.yml
name: Tests
on: [push, pull_request]
jobs:
  phpunit:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Run PHPUnit
        run: php bin/phpunit --coverage-text
      - name: Check coverage
        run: |
          coverage=$(php bin/phpunit --coverage-text | grep "Lines:" | awk '{print $2}' | sed 's/%//')
          if [ "$coverage" -lt 70 ]; then
            echo "Coverage $coverage% < 70%"
            exit 1
          fi
```

---

### Sprint 2.2 - Cache & Optimisations (Semaine 7-8)

#### Tâche 2.2.1 : Implémenter Redis Cache
**Durée** : 3 jours
**Priorité** : 🟢 MOYENNE

**Actions** :
1. Installer et configurer Redis
2. Cacher les counts utilisateur
3. Cacher les pages populaires
4. Invalidation intelligente

**Installation** :
```bash
composer require symfony/cache
# Installer Redis sur le serveur
```

**Configuration** :
```yaml
# config/packages/cache.yaml
framework:
    cache:
        app: cache.adapter.redis
        default_redis_provider: redis://localhost:6379

        pools:
            cache.user_analytics:
                adapter: cache.adapter.redis
                default_lifetime: 3600

            cache.popular_pages:
                adapter: cache.adapter.redis
                default_lifetime: 86400
```

**Utilisation** :
```php
// src/Repository/AnalyticsRepository.php
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class AnalyticsRepository
{
    public function __construct(
        private readonly CacheInterface $userAnalyticsCache
    ) {}

    public function getUserToolCounts(int $userId): array
    {
        return $this->userAnalyticsCache->get(
            "user_tools_{$userId}",
            function (ItemInterface $item) use ($userId) {
                $item->expiresAfter(3600);

                return $this->fetchUserToolCounts($userId);
            }
        );
    }

    public function invalidateUserCache(int $userId): void
    {
        $this->userAnalyticsCache->delete("user_tools_{$userId}");
    }
}
```

**Invalidation** :
```php
// Appeler après save/delete d'une analyse
$this->analyticsRepository->invalidateUserCache($user->getId());
```

**Tests** :
- [ ] Test : Données mises en cache
- [ ] Test : Cache invalidé après modification
- [ ] Test : Performance avant/après

---

#### Tâche 2.2.2 : Optimiser Stockage JSON
**Durée** : 2 jours
**Priorité** : 🟢 MOYENNE

**Actions** :
1. Analyser les structures JSON existantes
2. Décider : normaliser ou utiliser JSON columns
3. Migrer progressivement

**Analyse des structures** :
```php
// Script d'analyse
// bin/console app:analyze-json-structures

// Analyse de 1000 IshikawaAnalysis.data
// Structure commune trouvée :
// {
//   "title": string,
//   "categories": [
//     { "name": string, "causes": [string] }
//   ]
// }

// Décision : Utiliser JSON column MySQL
```

**Migration** :
```sql
-- Migration pour utiliser JSON columns
ALTER TABLE ishikawa_analysis
    MODIFY COLUMN data JSON;

-- Ajouter indexes sur données JSON
CREATE INDEX idx_ishikawa_title
    ON ishikawa_analysis((CAST(data->>'$.title' AS CHAR(255))));
```

**Entity update** :
```php
// src/Entity/IshikawaAnalysis.php
#[ORM\Column(type: Types::JSON)]
private array $data = [];

// Maintenant peut faire :
// WHERE JSON_EXTRACT(data, '$.title') LIKE '%search%'
```

---

## PHASE 3 - ARCHITECTURE & DOCUMENTATION (4 semaines)

### Sprint 3.1 - Architecture DDD (Semaine 9-10)

#### Tâche 3.1.1 : Enrichir la couche Domain
**Durée** : 4 jours
**Priorité** : 🟢 MOYENNE

**Actions** :
1. Créer Value Objects
2. Enrichir entités Domain
3. Séparer logique persistance

**Value Objects à créer** :
```php
// src/Domain/Shared/ValueObject/Email.php
final class Email
{
    private function __construct(private readonly string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email');
        }
    }

    public static function fromString(string $email): self
    {
        return new self($email);
    }

    public function toString(): string
    {
        return $this->value;
    }
}

// src/Domain/Lead/ValueObject/LeadScore.php
final class LeadScore
{
    private function __construct(private readonly int $value)
    {
        if ($value < 0 || $value > 100) {
            throw new \InvalidArgumentException('Score must be 0-100');
        }
    }

    public static function fromInt(int $score): self
    {
        return new self($score);
    }

    public function isQualified(): bool
    {
        return $this->value >= 50;
    }
}
```

**Enrichir Lead Domain** :
```php
// src/Domain/Lead/Lead.php
class Lead
{
    public function __construct(
        private readonly Email $email,
        private readonly LeadSource $source,
        private LeadScore $score,
        private readonly \DateTimeImmutable $createdAt
    ) {}

    public function qualify(): void
    {
        // Logique métier pure
        if (!$this->score->isQualified()) {
            throw new LeadNotQualifiedException();
        }
    }
}
```

---

#### Tâche 3.1.2 : Documentation Technique
**Durée** : 3 jours
**Priorité** : 🟢 MOYENNE

**Actions** :
1. Documenter l'architecture
2. Guide de contribution
3. ADR (Architecture Decision Records)

**Documents à créer** :

```markdown
<!-- docs/ARCHITECTURE.md -->
# Architecture Outils-Qualité.com

## Vue d'ensemble

```
┌─────────────────────────────────────┐
│         Presentation Layer          │
│  (Controllers, Twig, Forms)         │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│         Application Layer           │
│  (Use Cases, Services, DTOs)        │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│          Domain Layer               │
│  (Entities, Value Objects, Events)  │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│       Infrastructure Layer          │
│  (Repositories, External Services)  │
└─────────────────────────────────────┘
```

## Principes

- **DDD (Domain-Driven Design)** : Logique métier dans Domain
- **CQRS léger** : Séparation lectures/écritures pour analytics
- **Event-Driven** : Events pour découpler les composants
```

```markdown
<!-- docs/CONTRIBUTING.md -->
# Guide de Contribution

## Workflow

1. Créer une branche `feature/XXX` ou `fix/XXX`
2. Implémenter avec tests
3. Coverage minimum : 70%
4. Pull Request avec description
5. Review par pair
6. Merge vers `main`

## Standards

### PHP
- PSR-12 pour le code style
- PHPStan level 6 minimum
- Symfony best practices

### Tests
- Unitaires pour logique métier
- Fonctionnels pour controllers
- 70%+ coverage obligatoire

### Commits
```
feat: Add user authentication
fix: Resolve N+1 query in dashboard
refactor: Extract BlogMediaService
test: Add LeadService unit tests
docs: Update architecture diagram
```
```

```markdown
<!-- docs/adr/001-abstract-tool-controller.md -->
# ADR 001: Abstract Tool Controller

## Status
Accepted

## Context
Les 8 controllers d'outils dupliquaient 550+ lignes de code identiques.

## Decision
Créer `AbstractToolController` avec la logique commune :
- Lead creation
- Analytics tracking
- Error handling

## Consequences
**Positives** :
- -50% de code redondant
- Maintenance facilitée
- Bugs corrigés une seule fois

**Négatives** :
- Dépendance entre controllers
- Refactoring initial nécessaire
```

---

## PHASE 4 - STABILISATION & PRODUCTION (4 semaines)

### Sprint 4.1 - Monitoring & Observabilité (Semaine 11-12)

#### Tâche 4.1.1 : Implémenter Monitoring
**Durée** : 3 jours
**Priorité** : 🟡 IMPORTANTE

**Actions** :
1. Installer Symfony Profiler en production
2. Configurer logs structurés
3. Alerting sur erreurs critiques

**Configuration** :
```yaml
# config/packages/prod/monolog.yaml
monolog:
    handlers:
        main:
            type: fingers_crossed
            action_level: error
            handler: grouped

        grouped:
            type: group
            members: [file, sentry]

        file:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.log"
            level: debug
            formatter: monolog.formatter.json

        sentry:
            type: sentry
            level: error
```

**Métriques à tracker** :
- Temps de réponse par endpoint
- Taux d'erreur 500
- Requêtes SQL lentes (> 100ms)
- Utilisation cache Redis

---

### Sprint 4.2 - Performance Testing & Déploiement (Semaine 13-14)

#### Tâche 4.2.1 : Load Testing
**Durée** : 2 jours
**Priorité** : 🟡 IMPORTANTE

**Actions** :
1. Tester avec k6 ou JMeter
2. Identifier les bottlenecks
3. Optimiser si nécessaire

**Scénarios de test** :
```javascript
// k6-load-test.js
import http from 'k6/http';
import { check } from 'k6';

export let options = {
  stages: [
    { duration: '2m', target: 100 }, // Monter à 100 users
    { duration: '5m', target: 100 }, // Tenir 100 users
    { duration: '2m', target: 0 },   // Descendre à 0
  ],
};

export default function () {
  // Test dashboard
  let res = http.get('https://outils-qualite.com/dashboard');
  check(res, { 'status 200': (r) => r.status === 200 });
  check(res, { 'response time < 500ms': (r) => r.timings.duration < 500 });
}
```

**Critères de succès** :
- Dashboard < 500ms pour 100 users concurrents
- API < 200ms pour 500 req/min
- Taux d'erreur < 0.1%

---

#### Tâche 4.2.2 : Déploiement Production
**Durée** : 2 jours
**Priorité** : 🔴 CRITIQUE

**Checklist pré-production** :
- [ ] Tous les tests passent (unit, functional, load)
- [ ] Coverage > 70%
- [ ] Logs configurés pour production
- [ ] Rate limiting activé
- [ ] Cache Redis opérationnel
- [ ] Monitoring en place
- [ ] Backup DB automatisé
- [ ] SSL/HTTPS configuré
- [ ] Environnement variables sécurisées
- [ ] Documentation à jour

**Procédure de déploiement** :
```bash
# 1. Backup DB
php bin/console doctrine:backup

# 2. Mettre en maintenance
php bin/console app:maintenance on

# 3. Déployer le code
git pull origin main
composer install --no-dev --optimize-autoloader

# 4. Migrations
php bin/console doctrine:migrations:migrate --no-interaction

# 5. Compiler assets
php bin/console sass:build
php bin/console asset-map:compile

# 6. Clear cache
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# 7. Retirer maintenance
php bin/console app:maintenance off

# 8. Vérifier health check
curl https://outils-qualite.com/health
```

---

## 📊 MÉTRIQUES DE SUCCÈS

### Objectifs Mesurables

| Métrique | Avant | Objectif | Mesure |
|----------|-------|----------|--------|
| **Code dupliqué** | 550+ lignes | -50% | PHPLoc |
| **Performance Dashboard** | ~1200ms | <200ms | Blackfire |
| **Queries Dashboard** | 12 | 1-2 | Profiler |
| **Coverage Tests** | ~20% | >70% | PHPUnit |
| **Load Capacity** | ? | 100 users/s | k6 |
| **Temps réponse API** | ~300ms | <200ms | Profiler |
| **Taux erreur** | ? | <0.1% | Logs |

---

## 🗓️ CALENDRIER RÉCAPITULATIF

```
Semaines 1-2  │ ████████ │ Refactoring Controllers
Semaines 3-4  │ ████████ │ Validation JSON & Sécurité
Semaines 5-6  │ ████████ │ Services & Tests
Semaines 7-8  │ ████████ │ Cache & Optimisations
Semaines 9-10 │ ████████ │ Architecture DDD
Semaines 11-12│ ████████ │ Monitoring & Observabilité
Semaines 13-14│ ████████ │ Load Testing & Déploiement
Semaines 15-16│ ████     │ Buffer & Documentation finale
```

---

## 💰 ESTIMATION BUDGET

**Hypothèse** : 1 développeur senior à 500€/jour

| Phase | Durée | Coût |
|-------|-------|------|
| Phase 1 | 20 jours | 10 000€ |
| Phase 2 | 20 jours | 10 000€ |
| Phase 3 | 20 jours | 10 000€ |
| Phase 4 | 20 jours | 10 000€ |
| **Total** | **80 jours** | **40 000€** |

**Alternative** : 2 développeurs mi-temps = 8 semaines au lieu de 16

---

## 🚀 PROCHAINES ÉTAPES

### Immédiat (Cette semaine)
1. ✅ Valider ce plan avec l'équipe
2. ✅ Prioriser les tâches selon contexte business
3. ✅ Créer le repository de suivi (GitHub Projects / Jira)
4. ✅ Configurer environnement de test

### Semaine Prochaine
1. ✅ Créer AbstractToolController
2. ✅ Commencer tests unitaires
3. ✅ Setup CI/CD pipeline

---

## 📝 NOTES

- **Flexibilité** : Ce plan est adaptable selon les priorités business
- **Itérations** : Déploiements progressifs après chaque sprint
- **Reviews** : Points hebdomadaires pour ajuster
- **Rollback** : Toujours possible grâce aux tests

---

**Auteur** : Claude Sonnet 4.5
**Date** : Février 2026
**Version** : 1.0
**Prochaine Review** : Fin Sprint 1
