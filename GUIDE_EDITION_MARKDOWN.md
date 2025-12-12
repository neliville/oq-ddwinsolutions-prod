# 📝 Guide d'édition Markdown avec ancres automatiques

## 🎯 Vue d'ensemble

L'éditeur de blog intègre un système d'ancres automatiques qui :
- ✅ Génère automatiquement des IDs pour tous les titres
- ✅ Normalise les IDs (sans accents, minuscules, tirets)
- ✅ Crée des liens cliquables sur chaque titre
- ✅ Permet le scroll fluide vers les sections
- ✅ Génère automatiquement un sommaire

## 🚀 Méthode recommandée : Sommaire automatique

### Option 1 : Génération automatique avec [TOC]

Au lieu d'écrire manuellement votre sommaire, utilisez le placeholder `[TOC]` :

```markdown
# Mon Article Super

[TOC]

## Introduction

Contenu de l'introduction...

## Première partie

### Sous-section 1.1

Contenu...

### Sous-section 1.2

Contenu...

## Conclusion

Contenu de la conclusion...
```

**Résultat** : Le `[TOC]` sera automatiquement remplacé par :

```markdown
## 📑 Sommaire

- [Introduction](#introduction)
- [Première partie](#premiere-partie)
  - [Sous-section 1.1](#sous-section-1-1)
  - [Sous-section 1.2](#sous-section-1-2)
- [Conclusion](#conclusion)

---
```

### Avantages
- ✅ Aucune erreur de correspondance
- ✅ Mise à jour automatique si vous modifiez les titres
- ✅ IDs toujours corrects
- ✅ Gain de temps considérable

## 📖 Méthode manuelle : Écrire le sommaire

Si vous préférez écrire manuellement votre sommaire, suivez ces règles :

### Règle 1 : Comprendre la génération des IDs

Les titres sont transformés en IDs selon ces règles :

| Titre Markdown | ID généré |
|----------------|-----------|
| `## Introduction` | `#introduction` |
| `### Étape 1 : Diagnostic` | `#etape-1-diagnostic` |
| `## Les 5 Pourquoi` | `#les-5-pourquoi` |
| `### C'est quoi ?` | `#c-est-quoi` |
| `## Plan d'action 2025` | `#plan-d-action-2025` |

**Transformations appliquées :**
1. Conversion en minuscules
2. Suppression des accents (é→e, à→a, ç→c, etc.)
3. Remplacement des espaces et caractères spéciaux par des tirets `-`
4. Suppression des tirets multiples et en début/fin

### Règle 2 : Structure du sommaire manuel

```markdown
## 📑 Sommaire

- [Introduction](#introduction)
- [Section 1](#section-1)
  - [Sous-section 1.1](#sous-section-1-1)
  - [Sous-section 1.2](#sous-section-1-2)
- [Section 2](#section-2)
- [Conclusion](#conclusion)

---

## Introduction

Contenu...

## Section 1

### Sous-section 1.1

### Sous-section 1.2

## Section 2

## Conclusion
```

### ⚠️ Pièges à éviter

**❌ Mauvais :**
```markdown
## 📑 Sommaire

- [Étape 1](#étape-1)  ← Mauvais : garde les accents
- [Section 2](#section_2)  ← Mauvais : underscore au lieu de tiret
- [C'est quoi ?](#c'est-quoi-?)  ← Mauvais : garde les apostrophes et ?
```

**✅ Bon :**
```markdown
## 📑 Sommaire

- [Étape 1](#etape-1)  ← Bon : sans accent
- [Section 2](#section-2)  ← Bon : tiret
- [C'est quoi ?](#c-est-quoi)  ← Bon : apostrophe et ? remplacés
```

## 🔧 Syntaxe Markdown de base

### Titres

```markdown
# Titre niveau 1 (h1) - À éviter dans les articles
## Titre niveau 2 (h2) - Principal
### Titre niveau 3 (h3) - Sous-section
#### Titre niveau 4 (h4) - Sous-sous-section
```

### Texte

```markdown
**Texte en gras**
*Texte en italique*
***Texte gras et italique***
`code inline`
~~Texte barré~~
```

### Listes

```markdown
- Élément 1
- Élément 2
  - Sous-élément 2.1
  - Sous-élément 2.2
- Élément 3

1. Premier
2. Deuxième
3. Troisième
```

### Liens et ancres

```markdown
[Texte du lien](https://example.com)
[Lien interne](#section-1)
[Lien vers titre](#etape-1-diagnostic)
```

### Images

```markdown
![Texte alternatif](url-de-l-image.jpg)
```

### Citations

```markdown
> Ceci est une citation
> Sur plusieurs lignes
```

### Code

````markdown
```php
<?php
echo "Bloc de code avec coloration syntaxique";
```
````

### Tableaux

```markdown
| Colonne 1 | Colonne 2 | Colonne 3 |
|-----------|-----------|-----------|
| Données 1 | Données 2 | Données 3 |
| Ligne 2   | Ligne 2   | Ligne 2   |
```

### Séparateurs

```markdown
---
```

## 💡 Bonnes pratiques

### 1. Structure d'article recommandée

```markdown
# Titre Principal (généré automatiquement depuis le champ "Titre")

**Keywords:** mot-clé1, mot-clé2, mot-clé3

**Extrait :** Résumé de l'article en 2-3 phrases.

---

[TOC]

---

## Introduction

Paragraphe d'introduction...

## Section 1 : Contexte

### Sous-section 1.1

Contenu...

### Sous-section 1.2

Contenu...

## Section 2 : Méthodologie

### Étape 1

### Étape 2

## Conclusion

Résumé final...

---

*Cet article vous a été utile ? Partagez-le !*
```

### 2. Nommage des titres pour de bonnes ancres

**✅ Recommandé :**
- `## Introduction`
- `### Étape 1 : Diagnostic initial`
- `## Les 5 étapes clés`
- `### Qu'est-ce que le PDCA ?`

**❌ À éviter :**
- `## Introduction !!!` (trop de ponctuation)
- `### Étape #1` (le # sera converti en tiret)
- `## Section...` (points de suspension créent des tirets multiples)

### 3. Tester vos ancres

Après publication, testez vos liens :
1. Cliquez sur le lien d'ancrage dans votre sommaire
2. Vérifiez que la page scrolle vers la bonne section
3. L'URL doit contenir `#votre-ancre`
4. La section doit être légèrement en-dessous du header (scroll compensé)

## 🎓 Exemples complets

### Exemple 1 : Article simple avec TOC automatique

```markdown
**Keywords:** guide, tutoriel, méthode

**Extrait :** Découvrez notre guide complet pour maîtriser cette méthode.

---

[TOC]

---

## Introduction

Bienvenue dans ce guide...

## Contexte et enjeux

### Définition

La méthode consiste à...

### Historique

Créée en 1960...

## Mise en pratique

### Étape 1 : Préparation

Commencez par...

### Étape 2 : Exécution

Ensuite...

### Étape 3 : Validation

Finalement...

## Conclusion

En résumé...
```

### Exemple 2 : Article avec sommaire manuel

```markdown
## 📑 Sommaire

- [Introduction](#introduction)
- [La méthode](#la-methode)
  - [Principes](#principes)
  - [Avantages](#avantages)
- [Mise en œuvre](#mise-en-oeuvre)
- [Conclusion](#conclusion)

---

## Introduction

...

## La méthode

### Principes

### Avantages

## Mise en œuvre

## Conclusion
```

## 🐛 Dépannage

### Problème : Le lien ne fonctionne pas

**Cause probable :** L'ID dans le lien ne correspond pas à l'ID généré

**Solution :**
1. Utilisez `[TOC]` pour générer automatiquement
2. Ou vérifiez que votre ancre respecte les règles de transformation

### Problème : L'ancre pointe au mauvais endroit

**Cause :** Le titre référencé n'existe pas ou l'ID est mal écrit

**Solution :**
1. Vérifiez que le titre existe bien dans le document
2. Vérifiez l'orthographe de l'ancre
3. Utilisez `[TOC]` pour éviter les erreurs

### Problème : Le titre est caché sous le header

**Cause :** Impossible normalement, le scroll est compensé

**Solution :** Signaler le bug (normalement 100px de compensation)

## 🔗 Ressources

- [Documentation Markdown officielle](https://www.markdownguide.org/)
- [GitHub Flavored Markdown](https://github.github.com/gfm/)
- [Éditeur Markdown en ligne](https://dillinger.io/)

---

**Date de mise à jour :** 12 décembre 2025
