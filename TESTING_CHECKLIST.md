# Checklist de bonnes pratiques pour les tests

Cette checklist vous aidera à maintenir la qualité des tests au quotidien.

## 📋 Avant d'écrire un test

- [ ] **Comprendre le comportement attendu** : Savoir ce que le code doit faire
- [ ] **Identifier les cas limites** : Gérer les erreurs, valeurs nulles, cas extrêmes
- [ ] **Choisir le type de test approprié** : Unitaire, fonctionnel ou intégration ?
- [ ] **Vérifier qu'un test similaire n'existe pas déjà**

## ✍️ Écriture du test

### Structure et nommage

- [ ] **Nom clair et descriptif** : `testMethodDoesXWhenY`
- [ ] **Structure AAA** : Arrange, Act, Assert
- [ ] **Un seul comportement testé** par méthode de test
- [ ] **Documentation si nécessaire** : PHPDoc pour les cas complexes

### Isolation et indépendance

- [ ] **Pas de dépendances entre tests** : Chaque test doit être indépendant
- [ ] **Utiliser setUp() et tearDown()** pour préparer/nettoyer
- [ ] **Mocker les dépendances externes** (services, API, DB) dans les tests unitaires
- [ ] **Utiliser des transactions** pour isoler les tests DB

### Assertions

- [ ] **Assertions précises** : Utiliser l'assertion la plus spécifique
- [ ] **Messages d'erreur clairs** : Ajouter un message personnalisé si nécessaire
- [ ] **Vérifier tous les aspects importants** : Pas seulement le résultat, mais aussi les effets de bord

**Exemples :**
```php
// ❌ Mauvais
$this->assertTrue($result);

// ✅ Bon
$this->assertEquals('expected_value', $result, 'Le résultat devrait être expected_value');
```

## 🎯 Tests unitaires

- [ ] **Rapides** : Exécution < 100ms par test
- [ ] **Pas d'accès à la base de données** : Utiliser des mocks
- [ ] **Pas d'accès au réseau** : Mocker les appels HTTP
- [ ] **Pas d'I/O fichiers** : Utiliser des mocks ou fichiers temporaires
- [ ] **Couverture de toutes les branches** : if/else, switch, exceptions

## 🌐 Tests fonctionnels

- [ ] **Tester toutes les routes publiques** : Vérifier l'accessibilité
- [ ] **Tester les redirections** : Vérifier les codes HTTP (302, 401, etc.)
- [ ] **Tester les formulaires** : Validation, soumission, messages flash
- [ ] **Tester l'authentification** : Connexion, déconnexion, protection des routes
- [ ] **Tester les erreurs** : 404, 500, erreurs de validation

**Exemples :**
```php
// Tester une redirection
$this->assertResponseRedirects('/login');

// Tester un message flash
$this->assertSelectorTextContains('.alert-success', 'Message de succès');
```

## 🔗 Tests d'intégration

- [ ] **Tester les interactions DB** : CRUD complet (Create, Read, Update, Delete)
- [ ] **Tester les relations** : Vérifier les associations Doctrine
- [ ] **Tester les requêtes complexes** : QueryBuilder, DQL
- [ ] **Tester les transactions** : Rollback, commit
- [ ] **Nettoyer après chaque test** : Utiliser des transactions ou tearDown()

## 🧹 Maintenance

### Après chaque modification

- [ ] **Exécuter tous les tests** : `php bin/phpunit`
- [ ] **Vérifier la couverture** : Maintenir > 70%
- [ ] **Mettre à jour les tests cassés** : Ne pas les désactiver sans raison
- [ ] **Ajouter des tests pour les nouvelles fonctionnalités**

### Avant chaque commit

- [ ] **Tous les tests passent localement**
- [ ] **Pas de tests ignorés** : `@group`, `@skip` uniquement si nécessaire
- [ ] **Pas de code mort** : Supprimer les tests obsolètes
- [ ] **Commit séparé si possible** : Tests dans un commit distinct du code

### Avant chaque merge/PR

- [ ] **CI passe au vert** : Tous les tests passent sur GitHub Actions
- [ ] **Couverture maintenue ou améliorée**
- [ ] **Revue de code des tests** : Vérifier la qualité et la clarté

## 🚨 Signaux d'alarme

**Attention si :**
- [ ] **Tests qui échouent de manière aléatoire** : Problème d'isolation
- [ ] **Tests très lents** : > 1 seconde par test
- [ ] **Tests qui dépendent de l'ordre d'exécution** : Manque d'isolation
- [ ] **Tests qui échouent uniquement en CI** : Différences d'environnement
- [ ] **Couverture qui diminue** : Code non testé ajouté
- [ ] **Tests commentés ou ignorés** : Indication d'un problème à résoudre

## 📊 Métriques à surveiller

- [ ] **Nombre de tests** : Croissance régulière avec le code
- [ ] **Taux de réussite** : > 95%
- [ ] **Temps d'exécution** : < 5 minutes pour la suite complète
- [ ] **Couverture de code** : > 70% minimum
- [ ] **Ratio tests/code** : Environ 1:1 ou plus

## 🔍 Revue de code des tests

**Questions à se poser :**
- [ ] Le test est-il facile à comprendre ?
- [ ] Le test teste-t-il vraiment ce qu'il doit tester ?
- [ ] Y a-t-il des duplications qui pourraient être extraites ?
- [ ] Les noms sont-ils clairs et descriptifs ?
- [ ] Les tests sont-ils maintenables ?

## 📚 Ressources pour améliorer

- [ ] Lire la documentation PHPUnit régulièrement
- [ ] Lire les tests des projets open-source
- [ ] Participer aux code reviews des tests
- [ ] Partager les meilleures pratiques avec l'équipe

## ✅ Checklist rapide avant commit

```bash
# 1. Exécuter tous les tests
php bin/phpunit

# 2. Vérifier la couverture (nécessite Xdebug ou PCOV)
XDEBUG_MODE=coverage php bin/phpunit --coverage-text

# 3. Vérifier le lint
composer validate
php bin/console lint:container
php bin/console lint:twig templates/

# 4. Vérifier qu'il n'y a pas de code mort
# (à faire manuellement)
```

---

**Utilisez cette checklist régulièrement pour maintenir la qualité de vos tests !**

