# Design System - OUTILS-QUALITÉ

Ce document décrit le système de design utilisé dans l'application Outils-Qualité. Il sert de référence pour maintenir la cohérence visuelle et l'accessibilité à travers toute l'application.

---

## 🎨 Couleurs

### Palette Principale

| Couleur | Variable CSS | Valeur | Usage |
|---------|--------------|--------|-------|
| **Primary** | `--primary` | `#4f46e5` (Indigo 600) | Couleur principale de la marque, boutons primaires, liens |
| **Primary Dark** | `--primary-dark` | `#4338ca` (Indigo 700) | États hover des éléments primaires |
| **Primary Light** | `--primary-light` | `#6366f1` (Indigo 500) | Variantes légères, backgrounds |
| **Secondary** | `--secondary` | `#64748b` (Slate 600) | Actions secondaires, textes de support |

### Couleurs d'État

| État | Variable CSS | Valeur | Usage |
|------|--------------|--------|-------|
| **Success** | `--success` | `#10b981` (Emerald 500) | Confirmations, succès, états positifs |
| **Warning** | `--warning` | `#f59e0b` (Amber 500) | Avertissements, attention |
| **Danger** | `--danger-color` | `#dc2626` (Red 600) | Erreurs, suppressions, actions destructrices |
| **Info** | `--info` | `#0891b2` (Cyan 700) | Informations, astuces |

### Palette de Gris (9 niveaux)

```scss
--gray-50:  #f9fafb;  // Le plus clair - Fonds
--gray-100: #f3f4f6;  // Très clair - Backgrounds
--gray-200: #e5e7eb;  // Clair - Bordures légères
--gray-300: #d1d5db;  // Bordures standards
--gray-400: #9ca3af;  // Texte désactivé
--gray-500: #6b7280;  // Texte secondaire
--gray-600: #4b5563;  // Texte normal
--gray-700: #374151;  // Texte important
--gray-800: #1f2937;  // Très foncé
--gray-900: #111827;  // Le plus foncé - Noir
```

### Couleurs de Texte

| Usage | Variable CSS | Valeur |
|-------|--------------|--------|
| Texte principal | `--text-primary` | `#0f172a` (Slate 900) |
| Texte secondaire | `--text-secondary` | `#475569` (Slate 600) |
| Texte atténué | `--text-muted` | `#94a3b8` (Slate 400) |

### Couleurs de Fond

| Usage | Variable CSS | Valeur |
|-------|--------------|--------|
| Fond principal | `--bg-primary` | `#ffffff` |
| Fond secondaire | `--bg-secondary` | `#f8fafc` (Slate 50) |
| Fond tertiaire | `--bg-tertiary` | `#f1f5f9` (Slate 100) |

---

## 📐 Espacement

Système basé sur 4px (0.25rem) :

```scss
--space-1:  0.25rem;  // 4px
--space-2:  0.5rem;   // 8px
--space-3:  0.75rem;  // 12px
--space-4:  1rem;     // 16px
--space-5:  1.25rem;  // 20px
--space-6:  1.5rem;   // 24px
--space-8:  2rem;     // 32px
--space-10: 2.5rem;   // 40px
--space-12: 3rem;     // 48px
--space-16: 4rem;     // 64px
--space-20: 5rem;     // 80px
```

**Utilisation :** Utiliser Bootstrap pour les marges/paddings (`mb-3`, `px-4`, etc.) pour la cohérence.

---

## ✍️ Typographie

### Police

**Famille :** [Inter](https://fonts.google.com/specimen/Inter) (Google Fonts)

**Poids disponibles :**
- 400 (Regular) - Texte normal
- 500 (Medium) - Emphase légère
- 600 (Semibold) - Sous-titres, labels
- 700 (Bold) - Titres, emphase forte

### Hiérarchie

```scss
h1 { font-size: 2.5rem; line-height: 1.2; font-weight: 700; }    // 40px
h2 { font-size: 2rem; line-height: 1.2; font-weight: 700; }      // 32px
h3 { font-size: 1.75rem; line-height: 1.3; font-weight: 600; }   // 28px
h4 { font-size: 1.5rem; line-height: 1.3; font-weight: 600; }    // 24px
h5 { font-size: 1.25rem; line-height: 1.4; font-weight: 600; }   // 20px
h6 { font-size: 1rem; line-height: 1.4; font-weight: 600; }      // 16px
body { font-size: 1rem; line-height: 1.6; font-weight: 400; }    // 16px
```

**Note :** Chaque page doit avoir un seul `<h1>`. Respecter la hiérarchie logique (pas de saut h1 → h3).

---

## 🔲 Border Radius

```scss
--border-radius-sm:  0.375rem;  // 6px  - Petits éléments (badges, pills)
--border-radius:     0.5rem;    // 8px  - Standard (boutons, inputs)
--border-radius-lg:  0.75rem;   // 12px - Cards, modales
--border-radius-xl:  1rem;      // 16px - Grandes cards
--border-radius-2xl: 1.5rem;    // 24px - Éléments spéciaux
```

---

## 🎭 Ombres

```scss
--shadow-xs: 0 1px 2px rgba(15, 23, 42, 0.05);    // Subtile
--shadow-sm: 0 1px 3px rgba(15, 23, 42, 0.12);    // Légère
--shadow-md: 0 4px 6px rgba(15, 23, 42, 0.12);    // Moyenne
--shadow-lg: 0 10px 15px rgba(15, 23, 42, 0.12);  // Importante
--shadow-xl: 0 20px 25px rgba(15, 23, 42, 0.15);  // Maximale
```

**Utilisation :** Utiliser les classes Bootstrap `.shadow`, `.shadow-sm`, `.shadow-lg` en priorité.

---

## ⚡ Transitions

```scss
--transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);  // Micro-interactions
--transition-base: 200ms cubic-bezier(0.4, 0, 0.2, 1);  // Standard
--transition-slow: 300ms cubic-bezier(0.4, 0, 0.2, 1);  // Animations complexes
```

**Exemple :**
```scss
.button {
  transition: all var(--transition-base);
}
```

---

## 📚 Z-Index

Échelle formalisée pour éviter les conflits :

```scss
--z-dropdown:       100;   // Menus déroulants
--z-sticky:         200;   // Éléments sticky
--z-fixed:          300;   // Éléments fixed
--z-sidebar:        1020;  // Sidebar admin
--z-modal-backdrop: 1040;  // Arrière-plan des modales
--z-modal:          1050;  // Modales
--z-tooltip:        1070;  // Tooltips
--z-topbar:         1200;  // Topbar dashboard
--z-navbar:         2050;  // Navbar principale
```

**Règle :** Toujours utiliser les variables CSS au lieu de valeurs hardcodées.

---

## 🧩 Composants

### Boutons

#### Variantes

| Classe | Usage |
|--------|-------|
| `.btn-primary` | Action principale (sauvegarder, créer, valider) |
| `.btn-secondary` | Action secondaire (annuler, retour) |
| `.btn-success` | Confirmation positive (publier, approuver) |
| `.btn-danger` | Action destructrice (supprimer) |
| `.btn-outline-*` | Version outline de chaque variante |

#### Tailles

| Classe | Hauteur |
|--------|---------|
| `.btn-sm` | 32px |
| `.btn` (défaut) | 40px |
| `.btn-lg` | 48px |

**Exemple :**
```html
<button type="submit" class="btn btn-primary">
    <i data-lucide="save" width="20" height="20" aria-hidden="true"></i>
    Enregistrer
</button>
```

### Badges

#### Classes disponibles

| Classe | Usage |
|--------|-------|
| `.badge-status.is-published` | Statut "Publié" (vert) |
| `.badge-status.is-draft` | Statut "Brouillon" (ambre) |
| `.badge-status.is-featured` | Statut "Mis en avant" (bleu) |
| `.badge-category` | Catégorie avec couleur dynamique (background via style inline) |
| `.badge-tag` | Tag (gris clair) |

**Exemple :**
```twig
{# Statut #}
<span class="badge badge-status {{ post.isPublished ? 'is-published' : 'is-draft' }}">
    {{ post.isPublished ? 'Publié' : 'Brouillon' }}
</span>

{# Catégorie (couleur dynamique depuis DB) #}
<span class="badge badge-category"
      style="background-color: {{ category.color }};"
      aria-label="Catégorie {{ category.name }}">
    {{ category.name }}
</span>
```

### Icônes

**Système principal :** [Lucide Icons](https://lucide.dev/) (prioritaire)

**Système legacy :** Font Awesome (en cours de migration)

#### Tailles standardisées

| Classe | Dimensions | Usage |
|--------|------------|-------|
| `.icon-sm` | 16×16px | Icônes inline, badges |
| `.icon-md` | 24×24px | Boutons, navigation |
| `.icon-lg` | 32×32px | Headers, features |
| `.icon-xl` | 40×40px | Hero sections |

**Exemple :**
```html
<i data-lucide="mail" width="24" height="24" aria-hidden="true"></i>
```

**Règles :**
- Toujours ajouter `aria-hidden="true"` (icônes décoratives)
- Spécifier `width` et `height` explicitement
- Initialiser avec `lucide.createIcons()` après chargement DOM

### Formulaires

#### Composant réutilisable

**Fichier :** `templates/components/form/field.html.twig`

**Utilisation :**
```twig
{% include 'components/form/field.html.twig' with {
    field: registrationForm.email,
    label: 'Adresse email',
    help: 'Nous ne partagerons jamais votre email.',
    icon: 'mail'  {# optionnel #}
} %}
```

**Fonctionnalités :**
- ARIA attributes automatiques (`aria-required`, `aria-invalid`, `aria-describedby`)
- Gestion des erreurs accessible (`role="alert"`, `aria-live="polite"`)
- Support optionnel d'icône (input-group)
- Texte d'aide associé au champ

### Modales

**Composants disponibles :**
- `BootstrapModal.html.twig` - Modale générique
- `ConfirmationModal.html.twig` - Modale de confirmation

**Attributs ARIA requis :**
```html
<div class="modal"
     role="dialog"
     aria-modal="true"
     aria-labelledby="modal-title"
     aria-hidden="true">
    <h5 id="modal-title">Titre de la modale</h5>
</div>
```

**Focus management :** Géré automatiquement par `bootstrap_modal_controller.js`.

### Tableaux

**Exigences d'accessibilité :**
```html
<table class="table table-hover">
    <caption class="visually-hidden">Description du tableau</caption>
    <thead>
        <tr>
            <th scope="col">Colonne 1</th>
            <th scope="col">Colonne 2</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Donnée</td>
            <td>
                <a href="#" aria-label="Modifier l'élément X">
                    <i data-lucide="edit" aria-hidden="true"></i>
                </a>
            </td>
        </tr>
    </tbody>
</table>
```

**Règles :**
- `<caption>` obligatoire (peut être `.visually-hidden`)
- `scope="col"` sur tous les `<th>` d'en-tête
- `aria-label` sur les boutons d'action avec icônes seules

### Breadcrumbs

**Fichier :** `templates/components/breadcrumb.html.twig`

**Utilisation :**
```twig
{% include 'components/breadcrumb.html.twig' with {
    items: [
        { label: 'Tableau de bord', url: path('app_dashboard') },
        { label: 'Articles', url: path('app_admin_blog_index') },
        { label: 'Modifier : ' ~ post.title }  {# Dernier élément sans URL #}
    ]
} %}
```

**Fonctionnalités :**
- Navigation accessible (`aria-label="Fil d'Ariane"`, `aria-current="page"`)
- Schema.org `BreadcrumbList` pour SEO

### Messages Flash

**Fichier :** `templates/components/flash_messages.html.twig`

**Utilisation :**
```twig
{% include 'components/flash_messages.html.twig' %}

{# Ou avec types spécifiques #}
{% include 'components/flash_messages.html.twig' with {
    types: ['success', 'error']
} %}
```

**Types supportés :** `success`, `error`, `danger`, `warning`, `info`

**Attributs ARIA :** `role="alert"`, `aria-live="polite"`, `aria-atomic="true"`

---

## ♿ Accessibilité

### Conformité

**Niveau cible :** WCAG 2.1 AA

### Contraste

| Contexte | Ratio minimum |
|----------|---------------|
| Texte normal (< 18px) | 4.5:1 |
| Texte large (≥ 18px ou ≥ 14px gras) | 3:1 |
| Éléments d'interface (boutons, bordures) | 3:1 |

**Validation :** Utiliser [WebAIM Contrast Checker](https://webaim.org/resources/contrastchecker/)

### Navigation Clavier

**Éléments requis :**
- Skip link (lien "Aller au contenu principal") sur toutes les pages
- Focus visible sur tous les éléments interactifs
- Ordre de tabulation logique
- Pas de piège clavier (focus trap uniquement dans modales)

**Exemple de skip link :**
```html
<a href="#main-content" class="visually-hidden-focusable">
    Aller au contenu principal
</a>
```

### Landmarks Sémantiques

**Structure requise :**
```html
<body>
    <a href="#main-content" class="visually-hidden-focusable">...</a>

    <nav aria-label="Navigation principale">...</nav>

    <main id="main-content" tabindex="-1">
        {% block content %}{% endblock %}
    </main>

    <footer>...</footer>
</body>
```

### ARIA

**Attributs essentiels :**
- `role="alert"` sur messages flash et erreurs
- `aria-label` sur boutons avec icônes seules
- `aria-hidden="true"` sur icônes décoratives
- `aria-live="polite"` sur contenus dynamiques
- `aria-modal="true"` et `role="dialog"` sur modales

### Formulaires

**Exigences :**
- Labels explicites sur tous les champs
- `aria-required="true"` sur champs obligatoires
- `aria-invalid="true"` sur champs en erreur
- `aria-describedby` pour lier aide/erreurs au champ
- Honeypot avec `tabindex="-1"` et `aria-hidden="true"`

---

## 📏 Classes Utilitaires

**Fichier :** `assets/styles/core/_utilities.scss`

### Visibilité

| Classe | Usage |
|--------|-------|
| `.visually-hidden` | Masquer visuellement mais garder accessible (screen readers) |
| `.visually-hidden-focusable` | Visible au focus clavier (skip links) |
| `.visually-hidden-honeypot` | Honeypot anti-spam (position absolue hors écran) |

### Icônes

| Classe | Dimensions |
|--------|------------|
| `.icon-sm` | 16×16px |
| `.icon-md` | 24×24px |
| `.icon-lg` | 32×32px |
| `.icon-xl` | 40×40px |

### Animations

| Classe | Effet |
|--------|-------|
| `.chevron-rotate` | Rotation 180° avec transition |
| `.chevron-rotate.is-open` | État ouvert (rotate(180deg)) |

---

## 🛠️ Bonnes Pratiques

### CSS

1. **Variables CSS** : Toujours préférer les variables CSS aux valeurs hardcodées
   ```scss
   // ✅ Bon
   color: var(--primary);
   z-index: var(--z-modal);

   // ❌ Mauvais
   color: #4f46e5;
   z-index: 1050;
   ```

2. **!important** : Éviter autant que possible. Préférer augmenter la spécificité.

3. **Inline Styles** : Réserver uniquement pour les styles dynamiques (couleurs depuis DB)

### HTML

1. **Sémantique** : Utiliser les balises appropriées (`<nav>`, `<main>`, `<aside>`, `<article>`)

2. **ARIA** : Ne pas utiliser ARIA quand le HTML sémantique suffit
   ```html
   <!-- ✅ Bon (HTML sémantique suffit) -->
   <button>Fermer</button>

   <!-- ❌ Mauvais (ARIA redondant) -->
   <div role="button" tabindex="0">Fermer</div>
   ```

3. **Images** : Toujours spécifier `alt`, `width`, `height`

### Twig

1. **Composants** : Réutiliser les composants existants (`breadcrumb.html.twig`, `field.html.twig`, etc.)

2. **Escaping** : Utiliser les filtres appropriés (`|escape('js')`, `|escape('html')`)

3. **Inclusion** : Préférer `{% include %}` pour les composants réutilisables

---

## 📦 Structure des Fichiers

```
assets/styles/
├── core/
│   ├── _variables.scss      # Variables CSS globales
│   └── _utilities.scss       # Classes utilitaires
├── components/
│   ├── _badges.scss          # Système de badges
│   ├── _buttons.scss         # Styles boutons
│   ├── _sidebar.scss         # Sidebar admin
│   └── ...
├── layout/
│   ├── _navbar.scss          # Navbar principale
│   └── ...
└── app.scss                  # Point d'entrée principal
```

**Templates :**
```
templates/
├── components/
│   ├── breadcrumb.html.twig
│   ├── flash_messages.html.twig
│   ├── form/
│   │   ├── field.html.twig
│   │   └── field_error.html.twig
│   ├── BootstrapModal.html.twig
│   └── ConfirmationModal.html.twig
├── base.html.twig
└── base_with_sidebar.html.twig
```

---

## 🔍 Validation et Tests

### Outils Recommandés

1. **Lighthouse** (Chrome DevTools)
   - Score Accessibility > 90

2. **axe DevTools** (Extension navigateur)
   - 0 erreurs critiques

3. **WAVE** (WebAIM)
   - 0 erreurs d'accessibilité

4. **Lecteurs d'écran**
   - NVDA (Windows)
   - VoiceOver (macOS)

### Checklist Pré-Déploiement

- [ ] Navigation clavier complète (Tab, Shift+Tab, Enter, Escape)
- [ ] Skip link fonctionnel sur toutes les pages
- [ ] Contraste AA validé (4.5:1 pour texte)
- [ ] Modales avec focus trap et ARIA
- [ ] Tableaux avec caption et scope
- [ ] Formulaires avec labels et erreurs accessibles
- [ ] Hiérarchie headings correcte (un seul h1, pas de sauts)
- [ ] Icônes avec aria-hidden="true"
- [ ] Boutons d'action avec aria-label si icône seule

---

## 📝 Changelog

### Version 1.0 (2026-02-18)

- ✅ Consolidation des variables CSS
- ✅ Ajout de l'échelle z-index formalisée
- ✅ Création des classes utilitaires
- ✅ Standardisation du système de badges
- ✅ Composants formulaire réutilisables
- ✅ Migration Font Awesome → Lucide (pages contact, registration)
- ✅ Amélioration accessibilité modales (ARIA, focus management)
- ✅ Tableaux accessibles (caption, scope)
- ✅ Skip links et landmarks sémantiques
- ✅ Composant breadcrumb avec Schema.org
- ✅ Messages flash accessibles

---

## 📚 Ressources

- [Bootstrap 5 Documentation](https://getbootstrap.com/docs/5.3/)
- [Lucide Icons](https://lucide.dev/)
- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [MDN Accessibility](https://developer.mozilla.org/en-US/docs/Web/Accessibility)
- [WebAIM Contrast Checker](https://webaim.org/resources/contrastchecker/)

---

**Maintenu par :** Équipe Outils-Qualité
**Dernière mise à jour :** 18 février 2026
