# Guide de tests - Symfony Application

Ce document décrit la stratégie de tests pour l'application Symfony, incluant les tests unitaires, fonctionnels et d'intégration.

## 📋 Table des matières

- [Structure des tests](#structure-des-tests)
- [Types de tests](#types-de-tests)
- [Configuration](#configuration)
- [Exécution des tests](#exécution-des-tests)
- [Bonnes pratiques](#bonnes-pratiques)
- [CI/CD](#cicd)

## 📁 Structure des tests

```
tests/
├── Unit/              # Tests unitaires (rapides, isolés, sans DB)
│   ├── Entity/       # Tests d'entités
│   ├── Form/         # Tests de formulaires
│   └── Service/      # Tests de services
├── Functional/       # Tests fonctionnels (contrôleurs, routes, formulaires)
│   └── Controller/   # Tests des contrôleurs
├── Integration/      # Tests d'intégration (base de données, API)
├── Fixtures/         # Données de test (Doctrine Fixtures)
├── TestCase/         # Classes de base pour les tests
│   └── WebTestCaseWithDatabase.php  # TestCase avec base de données
└── bootstrap.php     # Bootstrap PHPUnit
```

## 🧪 Types de tests

### Tests unitaires (`tests/Unit/`)

**Caractéristiques :**
- **Rapides** : Exécution en millisecondes
- **Isolés** : Pas de dépendances externes (DB, réseau)
- **Mockables** : Les dépendances sont mockées
- **Objectif** : Tester une classe/méthode isolément

**Exemples :**
- Tests d'entités (getters, setters, validations)
- Tests de formulaires (validation, soumission)
- Tests de services avec dépendances mockées

**Exécution :**
```bash
php bin/phpunit --testsuite=unit
```

### Tests fonctionnels (`tests/Functional/`)

**Caractéristiques :**
- **WebTestCase** : Utilise le client HTTP de Symfony
- **Contrôleurs** : Teste les routes, réponses HTTP
- **Formulaires** : Teste la soumission et validation
- **Objectif** : Vérifier que les fonctionnalités utilisateur fonctionnent

**Exemples :**
- Accès aux pages publiques
- Authentification utilisateur
- Soumission de formulaires
- Redirections et messages flash

**Exécution :**
```bash
php bin/phpunit --testsuite=functional
```

### Tests d'intégration (`tests/Integration/`)

**Caractéristiques :**
- **Base de données** : Utilise SQLite en mémoire (`sqlite:///:memory:`)
- **Entités** : Teste les interactions Doctrine ORM
- **API** : Teste les endpoints API avec authentification
- **Objectif** : Vérifier que plusieurs composants fonctionnent ensemble

**Exemples :**
- CRUD sur entités (Create, Read, Update, Delete)
- Relations entre entités
- Requêtes complexes avec QueryBuilder
- API REST avec authentification

**Exécution :**
```bash
php bin/phpunit --testsuite=integration
```

## ⚙️ Configuration

### phpunit.xml.dist

Le fichier `phpunit.xml.dist` configure :
- **Environnement de test** : `APP_ENV=test`
- **Base de données** : SQLite (fichier `var/cache/test/test.db`)
- **TestSuites** : `unit`, `functional`, `integration`
- **Rigueur** : `failOnRisky="true"` et `failOnWarning="true"` pour CI
- **Couverture** : non activée par défaut (évite l'exigence d'Xdebug en local)

> 💡 **Couverture**  
> Active-la uniquement lorsque tu disposes d’un driver (Xdebug, PCOV).  
> Exemple : `XDEBUG_MODE=coverage php bin/phpunit --coverage-text`.

### WebTestCaseWithDatabase

Classe de base pour les tests nécessitant une base de données :

```php
use App\Tests\TestCase\WebTestCaseWithDatabase;

class MyTest extends WebTestCaseWithDatabase
{
    public function testSomething(): void
    {
        $user = $this->createTestUser('test@example.com');
        // Utiliser $this->entityManager pour les opérations DB
    }
}
```

### Fixtures

Les fixtures permettent de créer des données de test reproductibles :

```php
use App\Tests\Fixtures\UserFixtures;

// Dans un test d'intégration
$fixtures = new UserFixtures($passwordHasher);
$fixtures->load($this->entityManager);
$user = $this->getReference('user');
```

## 🚀 Exécution des tests

### Localement

**Tous les tests :**
```bash
php bin/phpunit
# ou
php bin/phpunit --testsuite=all
```

**Tests unitaires uniquement :**
```bash
php bin/phpunit --testsuite=unit
```

**Tests fonctionnels uniquement :**
```bash
php bin/phpunit --testsuite=functional
```

**Tests d'intégration uniquement :**
```bash
php bin/phpunit --testsuite=integration
```

**Avec couverture de code :**
```bash
# nécessite Xdebug ou PCOV
XDEBUG_MODE=coverage php bin/phpunit --coverage-text
XDEBUG_MODE=coverage php bin/phpunit --coverage-html coverage/
```

**Avec testdox (sortie lisible) :**
```bash
php bin/phpunit --testdox
```

**Un test spécifique :**
```bash
php bin/phpunit tests/Unit/Entity/UserTest.php
php bin/phpunit tests/Unit/Entity/UserTest.php::testUserCanBeCreated
```

### Dans CI/CD

Les tests sont exécutés automatiquement :
- **Sur chaque push** : Tests complets
- **Sur chaque PR** : Validation + tests
- **Avant déploiement** : Blocage si tests échouent

Voir `.github/workflows/ci-tests.yml` et `.github/workflows/deploy-symfony-staging.yml`

## ✅ Bonnes pratiques

### 1. Nommage des tests

**Format :** `testMethodDoesXWhenY`

**Exemples :**
```php
public function testUserCanBeCreated(): void
public function testLoginFailsWithInvalidCredentials(): void
public function testApiRequiresAuthentication(): void
public function testRecordCanBeUpdated(): void
```

### 2. Structure AAA (Arrange, Act, Assert)

```php
public function testSomething(): void
{
    // Arrange : Préparer les données
    $user = $this->createTestUser();
    
    // Act : Exécuter l'action
    $result = $service->doSomething($user);
    
    // Assert : Vérifier le résultat
    $this->assertTrue($result);
}
```

### 3. Tests isolés

- Chaque test doit être indépendant
- Utiliser `setUp()` et `tearDown()` pour la préparation/nettoyage
- Utiliser des transactions pour isoler les tests DB (via `WebTestCaseWithDatabase`)

### 4. Mocks et stubs

**Pour les tests unitaires :**
```php
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

$mockService = $this->createMock(SomeService::class);
$mockService->expects($this->once())
    ->method('doSomething')
    ->willReturn('expected_result');
```

### 5. Fixtures réutilisables

Créer des fixtures dans `tests/Fixtures/` :
```php
use App\Tests\Fixtures\UserFixtures;

$fixtures = new UserFixtures($passwordHasher);
$fixtures->load($this->entityManager);
```

### 6. Tests de routes

**Hard-coder les URLs ou utiliser les noms de routes :**
```php
// Option 1 : URL hard-codée
$client->request('GET', '/contact/');

// Option 2 : Nom de route (meilleur)
$client->request('GET', $this->urlGenerator->generate('app_contact_index'));
```

### 7. Couverture de code

**Objectif :** Maintenir une couverture > 70%

**Vérifier :**
```bash
php bin/phpunit --coverage-text
```

**Exclure :**
- Entités (getters/setters simples)
- Migrations
- Kernel.php
- Configurations

## 🔄 CI/CD

### Workflow GitHub Actions

#### 1. CI Tests (`ci-tests.yml`)

Exécuté sur chaque push/PR :
- Tests unitaires, fonctionnels, d'intégration
- Validation du code (composer, console, lint)
- Génération de rapports de couverture

#### 2. Déploiement Staging (`deploy-symfony-staging.yml`)

Exécuté uniquement si les tests passent :
1. **Tests** : Exécute tous les tests
2. **Build** : Installation des dépendances (prod)
3. **Deploy** : Déploiement vers Azure Staging

**Blocage du déploiement :**
Le déploiement est automatiquement bloqué si :
- Les tests échouent
- La validation échoue
- Le workflow CI échoue

### Notifications

**À implémenter :**
- Slack notifications sur échec de tests
- Email notifications pour les déploiements
- Dashboard Azure pour voir l'historique

## 📝 Ajouter un nouveau test

### 1. Test unitaire

```php
<?php
// tests/Unit/Service/MyServiceTest.php

namespace App\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;

class MyServiceTest extends TestCase
{
    public function testServiceDoesSomething(): void
    {
        // Arrange
        $service = new MyService();
        
        // Act
        $result = $service->doSomething();
        
        // Assert
        $this->assertNotNull($result);
    }
}
```

### 2. Test fonctionnel

```php
<?php
// tests/Functional/Controller/MyControllerTest.php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MyControllerTest extends WebTestCase
{
    public function testRouteIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/my-route/');
        
        $this->assertResponseIsSuccessful();
    }
}
```

### 3. Test d'intégration

```php
<?php
// tests/Integration/DatabaseTest.php

namespace App\Tests\Integration;

use App\Tests\TestCase\WebTestCaseWithDatabase;

class DatabaseTest extends WebTestCaseWithDatabase
{
    public function testEntityCanBePersisted(): void
    {
        $entity = new MyEntity();
        $entity->setName('Test');
        
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        
        $this->assertNotNull($entity->getId());
    }
}
```

## 🐛 Débogage des tests

### Problèmes courants

**1. "Could not find driver"**
```bash
# Vérifier que pdo_sqlite est installé
php -m | grep pdo_sqlite
```

**2. Tests qui échouent en CI mais passent localement**
- Vérifier les variables d'environnement
- Vérifier les secrets GitHub Actions
- Vérifier les permissions de fichiers

**3. Base de données non nettoyée**
- Utiliser `WebTestCaseWithDatabase` qui gère les transactions
- Vérifier que `tearDown()` nettoie correctement

**4. Tests lents**
- Séparer les tests unitaires (rapides) des tests d'intégration
- Utiliser des mocks au lieu de vraies dépendances

## 📚 Ressources

- [PHPUnit Documentation](https://docs.phpunit.de/)
- [Symfony Testing](https://symfony.com/doc/current/testing.html)
- [Doctrine Test Bundle](https://github.com/dmaister/doctrine-test-bundle)

## ✅ Checklist de bonnes pratiques

Voir le fichier `TESTING_CHECKLIST.md` pour une checklist complète.

---

**Dernière mise à jour :** 2024
**Mainteneur :** Équipe DDWin Solutions

