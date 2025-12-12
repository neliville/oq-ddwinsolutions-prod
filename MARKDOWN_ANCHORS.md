# Améliorations Markdown - Gestion des Ancres

## 📋 Résumé des changements

Les ancres dans le contenu Markdown des articles fonctionnent désormais correctement grâce aux extensions CommonMark.

## ✅ Fonctionnalités ajoutées

### 1. **Génération automatique d'IDs pour les titres**
- Chaque titre (h1, h2, h3, etc.) génère automatiquement un ID unique
- Les IDs sont basés sur le texte du titre (slug automatique)
- Exemple : `## Introduction à la qualité` → `<h2 id="introduction-a-la-qualite">`

### 2. **Liens d'ancrage cliquables**
- Un symbole `#` apparaît au survol de chaque titre
- Permet de copier facilement le lien vers une section spécifique
- Style personnalisé avec effet de hover

### 3. **Navigation par ancres fonctionnelle**
- Les liens internes type `[texte](#ancre)` fonctionnent correctement
- Scroll automatique vers la section ciblée
- Mise en évidence temporaire de la section ciblée (animation)

### 4. **Attributs personnalisés**
- Possibilité d'ajouter des attributs HTML via la syntaxe `{#id .class}`
- Exemple : `## Mon titre {#custom-id .ma-classe}`

## 🎨 Styles CSS ajoutés

Dans `assets/styles/pages/article-prose.scss` :

- **Scroll margin** : Compense la hauteur du header fixe (80px)
- **Liens de titres** : Symbole `#` visible au hover
- **Animation de cible** : Fond jaune qui s'estompe quand on arrive via une ancre
- **Responsive** : Adaptation pour mobile

## 📝 Utilisation dans les articles

### Créer une ancre manuellement
```markdown
## Mon titre de section

Référencer cette section : [voir la section](#mon-titre-de-section)
```

### Avec ID personnalisé
```markdown
## Introduction {#intro}

Plus tard : [Retour à l'intro](#intro)
```

### Table des matières
```markdown
## 📚 Sommaire

- [Introduction](#introduction)
- [Méthodologie](#methodologie)
  - [Étape 1](#etape-1)
  - [Étape 2](#etape-2)
- [Conclusion](#conclusion)
```

## 🔧 Configuration technique

### Extensions CommonMark activées

1. **CommonMarkCoreExtension** : Support Markdown de base
2. **GithubFlavoredMarkdownExtension** : Tableaux, listes de tâches, etc.
3. **HeadingPermalinkExtension** : Génération automatique des ancres
4. **AttributesExtension** : Attributs HTML personnalisés

### Configuration des permaliens

```php
'heading_permalink' => [
    'html_class' => 'heading-permalink',
    'id_prefix' => '',
    'fragment_prefix' => '',
    'insert' => 'before',
    'title' => 'Lien permanent',
    'symbol' => '#',
    'aria_hidden' => true,
]
```

## ✨ Exemples d'utilisation

### Exemple 1 : Article avec sommaire

```markdown
# Guide complet Ishikawa

## 📋 Sommaire

- [Qu'est-ce que c'est ?](#quest-ce-que-cest)
- [Méthodologie](#methodologie)
- [Cas d'usage](#cas-dusage)
- [Conclusion](#conclusion)

---

## Qu'est-ce que c'est ?

Le diagramme d'Ishikawa...

[Retour au sommaire](#sommaire)
```

### Exemple 2 : Références croisées

```markdown
## Diagnostic initial

Voir aussi la [phase d'analyse](#phase-danalyse) et les [recommandations](#recommandations).

## Phase d'analyse

...

## Recommandations

Référez-vous au [diagnostic initial](#diagnostic-initial).
```

## 🚀 Bénéfices

1. **Navigation améliorée** : Les lecteurs peuvent naviguer facilement dans les longs articles
2. **Partage précis** : Possibilité de partager un lien direct vers une section
3. **SEO amélioré** : Les moteurs de recherche peuvent indexer les sections
4. **Expérience utilisateur** : Effet visuel au clic sur l'ancre
5. **Accessibilité** : Les liens d'ancrage sont accessibles au clavier

## 🎯 Prochaines améliorations possibles

- [ ] Générer automatiquement une table des matières
- [ ] Ajouter un bouton "Retour en haut" flottant
- [ ] Mettre en évidence la section active pendant le scroll
- [ ] Ajouter des liens "Copier le lien de cette section"

## 📚 Ressources

- [Documentation League CommonMark](https://commonmark.thephpleague.com/)
- [Extension HeadingPermalink](https://commonmark.thephpleague.com/2.0/extensions/heading-permalinks/)
- [Extension Attributes](https://commonmark.thephpleague.com/2.0/extensions/attributes/)
