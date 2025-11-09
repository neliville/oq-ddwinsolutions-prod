# Rapport de nettoyage - Fichiers obsolètes

> Date : 2024-11-01

## 📋 Fichiers HTML statiques à supprimer

Les fichiers HTML suivants ont été convertis en templates Twig et peuvent être supprimés après vérification :

### Fichiers à supprimer

1. **`index.html`** → Remplacé par `templates/home/index.html.twig`
2. **`ishikawa/index.html`** → Remplacé par `templates/ishikawa/index.html.twig`
3. **`5pourquoi/index.html`** → Remplacé par `templates/five_why/index.html.twig`
4. **`outils/index.html`** → Remplacé par `templates/outils/index.html.twig`
5. **`blog/index.html`** → Remplacé par `templates/blog/index.html.twig`
6. **`contact/index.html`** → Remplacé par `templates/contact/index.html.twig`
7. **`article-template.html`** → Remplacé par `templates/blog/article.html.twig`
8. **`mentions-legales/index.html`** → Remplacé par `templates/legal/...`
9. **`politique-de-confidentialite/index.html`** → Remplacé par `templates/legal/...`

### Fichiers à conserver

- **`healthz.html`** : Utilisé pour le health check (à vérifier si utilisé par Azure)
- **`unsubscribe.html`** : Utilisé pour la désinscription de la newsletter (à vérifier si encore utilisé)

## 📦 Assets JavaScript

### Fichiers dans `public/js/` encore utilisés

Les fichiers suivants sont encore référencés dans les templates Twig via `asset()` :

1. **`public/js/ishikawa.js`** → Utilisé dans `templates/ishikawa/index.html.twig`
2. **`public/js/fivewhy.js`** → Utilisé dans `templates/five_why/index.html.twig`
3. **`public/js/main.js`** → Utilisé dans plusieurs templates
4. **`public/js/blog-markdown.js`** → À vérifier si utilisé

**Note** : Ces fichiers utilisent correctement `asset()` pour le chargement. Ils pourraient être migrés vers Stimulus dans le futur (Priorité 4 - Intégration Stimulus), mais ne sont **pas obsolètes** pour le moment.

## ✅ Vérification des assets

Tous les chemins d'assets dans les templates utilisent correctement :
- `asset()` pour les fichiers statiques (CSS, JS, images)
- `importmap()` pour les modules JavaScript modernes (AssetMapper)

## 🗑️ Commandes de nettoyage

```bash
# Supprimer les fichiers HTML convertis (après vérification)
rm index.html
rm -rf ishikawa/
rm -rf 5pourquoi/
rm -rf outils/
rm -rf blog/
rm -rf contact/
rm article-template.html
rm -rf mentions-legales/
rm -rf politique-de-confidentialite/

# Vérifier que tout fonctionne après suppression
php bin/console cache:clear
symfony server:start
# Tester toutes les routes manuellement
```

## ⚠️ Précautions

1. **Ne supprimer les fichiers qu'après vérification complète** que tous les templates Twig fonctionnent correctement
2. **Tester toutes les routes** après suppression pour s'assurer qu'il n'y a pas de références manquantes
3. **Vérifier les health checks** si `healthz.html` est utilisé par Azure App Service
4. **Vérifier la désinscription** si `unsubscribe.html` est encore utilisé pour la newsletter

## 📝 Prochaines étapes

- [ ] Tester toutes les routes après suppression des fichiers HTML
- [ ] Vérifier que `healthz.html` et `unsubscribe.html` sont toujours nécessaires
- [ ] Migrer les scripts JavaScript vers Stimulus (Priorité 4 - Intégration Stimulus)
- [ ] Optimiser les assets (minification, lazy loading) (Priorité 5 - Optimisations finales)

