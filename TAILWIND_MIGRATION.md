# Plan de migration — Bootstrap → TailwindCSS + Symfony UX shadcn

> Guide de référence. À lire intégralement avant chaque phase de travail.

---

## Contexte du projet

| Élément | Valeur actuelle | Cible |
|---|---|---|
| Framework CSS | Bootstrap 5.3.8 | TailwindCSS v4 + shadcn kit |
| Préprocesseur | SCSS (symfonycasts/sass-bundle) | CSS natif (supprimé) |
| Pipeline assets | AssetMapper (Symfony 7.3) | AssetMapper (inchangé) |
| Bundle Tailwind | — | symfonycasts/tailwind-bundle |
| Templates | 138 Twig, 39 composants | idem, classes migrées |
| Symfony UX | ux-twig-component, ux-turbo, ux-live-component | + UX Toolkit shadcn |

---

## Contraintes critiques

1. **AssetMapper, pas Webpack Encore** — Le guide officiel Tailwind pour Symfony utilise Webpack Encore ; **ne pas le suivre**. Utiliser uniquement le TailwindBundle de SymfonyCasts.
2. **TailwindBundle télécharge un binaire standalone** (`var/tailwind/`) — aucun Node.js requis.
3. **Tailwind v4** (version par défaut du bundle) — pas de `tailwind.config.js`, configuration 100 % CSS avec `@theme inline`.
4. **Coexistence obligatoire pendant la migration** — Bootstrap et Tailwind tourneront en parallèle jusqu'à la Phase 6.
5. **Ordre de build en production** — toujours `tailwind:build --minify` **avant** `asset-map:compile`.
6. **shadcn kit = Tailwind v4 requis** — installer Tailwind v4 dès la Phase 1 (pas v3).

---

## Vue d'ensemble des phases

```
Phase 1  →  Installer TailwindBundle + fichier app.css parallèle
Phase 2  →  Configurer le thème Tailwind (variables CSS shadcn-ready)
Phase 3  →  Migrer les composants partagés (navbar, footer, sidebar, flash)
Phase 4  →  Migrer les templates de layout (base.html.twig, base_with_sidebar)
Phase 5  →  Migrer les pages et outils (bottom-up, page par page)
Phase 6  →  Supprimer Bootstrap et le Sass Bundle
Phase 7  →  Installer Symfony UX Toolkit — shadcn kit
Phase 8  →  Remplacer les composants custom par les composants shadcn
```

---

## Phase 1 — Installation du TailwindBundle

### 1.1 Installer le bundle

```bash
composer require symfonycasts/tailwind-bundle
php bin/console tailwind:init
```

`tailwind:init` en v4 crée uniquement `assets/styles/app.css` avec :
```css
@import "tailwindcss";
```

### 1.2 Fichier CSS d'entrée parallèle

Pendant la migration, on garde `app.scss` (Bootstrap) et on crée `app.css` (Tailwind).
Le `tailwind:init` cible `assets/styles/app.css` par défaut.

Vérifier/créer `config/packages/symfonycasts_tailwind.yaml` :
```yaml
symfonycasts_tailwind:
    input_css: 'assets/styles/app.css'
```

### 1.3 Inclure les deux CSS dans base.html.twig (mode coexistence)

Remplacer la ligne CSS actuelle dans `templates/base.html.twig` :
```twig
{# Avant #}
<link href="{{ asset('styles/app.scss') }}" rel="stylesheet">

{# Après (coexistence) #}
<link href="{{ asset('styles/app.css') }}" rel="stylesheet">
<link href="{{ asset('styles/app.scss') }}" rel="stylesheet">
```

> Tailwind en premier : ses resets (Preflight) sont appliqués, puis Bootstrap surcharge.
> Cette coexistence est temporaire.

### 1.4 Tester le build

```bash
php bin/console tailwind:build
php bin/console sass:build
```

Vérifier qu'aucune erreur n'apparaît. Le site doit rester visuellement identique.

### 1.5 Configurer le watcher (développement)

Ajouter dans `.symfony.local.yaml` (créer si absent) :
```yaml
workers:
    tailwind:
        cmd: ['symfony', 'console', 'tailwind:build', '--watch']
```

Ou lancer manuellement :
```bash
php bin/console tailwind:build --watch
```

---

## Phase 2 — Configurer le thème Tailwind (variables shadcn-ready)

C'est la phase de configuration du design system. Le but est d'aligner les variables Tailwind avec celles attendues par le kit shadcn.

### 2.1 Contenu complet de `assets/styles/app.css`

```css
@import "tailwindcss";

/* ─── Design tokens — compatibles shadcn ─────────────────────────── */
:root {
    /* Couleurs sémantiques (HSL pour shadcn) */
    --background:         0 0% 100%;
    --foreground:         224 71.4% 4.1%;
    --card:               0 0% 100%;
    --card-foreground:    224 71.4% 4.1%;
    --popover:            0 0% 100%;
    --popover-foreground: 224 71.4% 4.1%;
    --primary:            220.9 39.3% 11%;
    --primary-foreground: 210 20% 98%;
    --secondary:          220 14.3% 95.9%;
    --secondary-foreground: 220.9 39.3% 11%;
    --muted:              220 14.3% 95.9%;
    --muted-foreground:   220 8.9% 46.1%;
    --accent:             220 14.3% 95.9%;
    --accent-foreground:  220.9 39.3% 11%;
    --destructive:        0 84.2% 60.2%;
    --destructive-foreground: 210 20% 98%;
    --border:             220 13% 91%;
    --input:              220 13% 91%;
    --ring:               224 71.4% 4.1%;
    --radius:             0.5rem;
}

.dark {
    --background:         224 71.4% 4.1%;
    --foreground:         210 20% 98%;
    --card:               224 71.4% 4.1%;
    --card-foreground:    210 20% 98%;
    --popover:            224 71.4% 4.1%;
    --popover-foreground: 210 20% 98%;
    --primary:            210 20% 98%;
    --primary-foreground: 220.9 39.3% 11%;
    --secondary:          215 27.9% 16.9%;
    --secondary-foreground: 210 20% 98%;
    --muted:              215 27.9% 16.9%;
    --muted-foreground:   217.9 10.6% 64.9%;
    --accent:             215 27.9% 16.9%;
    --accent-foreground:  210 20% 98%;
    --destructive:        0 62.8% 30.6%;
    --destructive-foreground: 210 20% 98%;
    --border:             215 27.9% 16.9%;
    --input:              215 27.9% 16.9%;
    --ring:               216 12.2% 83.9%;
}

/* ─── Mapper les variables aux utilitaires Tailwind ───────────────── */
@theme inline {
    --color-background:          hsl(var(--background));
    --color-foreground:          hsl(var(--foreground));
    --color-card:                hsl(var(--card));
    --color-card-foreground:     hsl(var(--card-foreground));
    --color-popover:             hsl(var(--popover));
    --color-popover-foreground:  hsl(var(--popover-foreground));
    --color-primary:             hsl(var(--primary));
    --color-primary-foreground:  hsl(var(--primary-foreground));
    --color-secondary:           hsl(var(--secondary));
    --color-secondary-foreground: hsl(var(--secondary-foreground));
    --color-muted:               hsl(var(--muted));
    --color-muted-foreground:    hsl(var(--muted-foreground));
    --color-accent:              hsl(var(--accent));
    --color-accent-foreground:   hsl(var(--accent-foreground));
    --color-destructive:         hsl(var(--destructive));
    --color-destructive-foreground: hsl(var(--destructive-foreground));
    --color-border:              hsl(var(--border));
    --color-input:               hsl(var(--input));
    --color-ring:                hsl(var(--ring));
    --radius-sm:                 calc(var(--radius) - 4px);
    --radius-md:                 calc(var(--radius) - 2px);
    --radius-lg:                 var(--radius);
    --radius-xl:                 calc(var(--radius) + 4px);
    /* Typographie */
    --font-sans:  'Inter', ui-sans-serif, system-ui, sans-serif;
}

/* ─── Source scanning (AssetMapper — pas de node_modules à exclure) ─ */
@source "../../templates/**/*.html.twig";
@source "../../assets/**/*.js";
@source "../../src/**/*.php";
```

> **`@source`** : Tailwind v4 scanne automatiquement les fichiers adjacents, mais les templates Twig se trouvant hors du dossier `assets/`, il faut les déclarer explicitement.

### 2.2 Désactiver Preflight (option pendant la coexistence Bootstrap)

Si Bootstrap produit des conflits visuels avec le reset Tailwind (Preflight), ajouter :
```css
@import "tailwindcss" layer(utilities);
```
au lieu de `@import "tailwindcss"`. Cela importe uniquement les utilitaires sans le reset.
**Réactiver le Preflight complet en Phase 6** (quand Bootstrap est retiré).

---

## Phase 3 — Migrer les composants partagés

Ordre recommandé (du plus simple au plus complexe) :

| # | Fichier | Classes Bootstrap à remplacer principalement |
|---|---|---|
| 1 | `components/flash_messages.html.twig` | `alert`, `alert-success`, `alert-danger` |
| 2 | `components/breadcrumb.html.twig` | `breadcrumb`, `breadcrumb-item` |
| 3 | `components/footer.html.twig` | `container`, `row`, `col-*`, `text-*` |
| 4 | `components/navbar.html.twig` | `navbar`, `nav-link`, `dropdown` |
| 5 | `components/admin-sidebar.html.twig` | `sidebar`, layout classes |
| 6 | `components/dashboard_topbar.html.twig` | `d-flex`, `gap-*`, `btn-*` |
| 7 | Modals (9 fichiers) | `modal`, `modal-dialog`, `modal-content` |
| 8 | `components/form/` | `form-control`, `form-label`, `invalid-feedback` |

### Règles de migration Bootstrap → Tailwind

| Bootstrap | Tailwind v4 |
|---|---|
| `container` | `max-w-7xl mx-auto px-4` |
| `row` | `flex flex-wrap -mx-4` ou `grid grid-cols-12 gap-4` |
| `col-md-6` | `w-full md:w-1/2 px-4` |
| `d-flex` | `flex` |
| `d-none` | `hidden` |
| `d-block` | `block` |
| `gap-2` | `gap-2` (identique) |
| `mt-3` | `mt-3` (identique — Tailwind utilise la même échelle) |
| `text-muted` | `text-muted-foreground` |
| `text-center` | `text-center` (identique) |
| `fw-bold` | `font-bold` |
| `btn btn-primary` | `inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-md` |
| `btn btn-outline-secondary` | `border border-input bg-background hover:bg-accent px-4 py-2 rounded-md` |
| `card` | `rounded-lg border bg-card shadow-sm` |
| `card-body` | `p-6` |
| `alert alert-success` | `rounded-md bg-green-50 p-4 text-green-800 border border-green-200` |
| `badge bg-primary` | `inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold bg-primary text-primary-foreground` |
| `visually-hidden` | `sr-only` |
| `visually-hidden-focusable` | `sr-only focus:not-sr-only` |

---

## Phase 4 — Migrer les templates de layout

### 4.1 `templates/base.html.twig`

- Remplacer les classes Bootstrap de structure (`container`, layout helpers)
- Retirer le lien vers `app.scss` — garder uniquement `app.css`
- Vérifier que `{{ importmap('app') }}` reste en place (pas touché)

### 4.2 `templates/base_with_sidebar.html.twig`

- Migrer le layout sidebar + main content avec Tailwind (flexbox ou grid)

---

## Phase 5 — Migrer les pages et outils

Ordre suggéré :

1. **Pages d'auth** (`security/`, `registration/`, `reset_password/`) — simples, peu de classes
2. **Pages légales** (`legal/`) — contenu statique, peu d'interactivité
3. **Pages publiques** (`home/`, `contact/`, `outils/`, `blog/`)
4. **Dashboard** (`dashboard/`, `profile/`)
5. **Outils qualité** (`ishikawa/`, `five_why/`, `amdec/`, `pareto/`, `method8d/`, `qqoqccp/`, `creations/`)
6. **Admin** (`admin/`)

### Gestion des styles SCSS spécifiques aux outils

Les fichiers `assets/styles/tools/*.scss` contiennent des styles complexes (canvas Ishikawa, drag-and-drop, etc.). Ne pas migrer ceux-là en dernier — créer des fichiers CSS Tailwind équivalents dans `assets/styles/tools/` et les importer via `@source` ou `@import` dans `app.css`.

---

## Phase 6 — Supprimer Bootstrap et le Sass Bundle

**Ne faire cette phase qu'une fois tous les templates migrés et validés.**

### 6.1 Retirer les dépendances

```bash
composer remove twbs/bootstrap symfonycasts/sass-bundle
```

### 6.2 Nettoyer les fichiers

- Supprimer `assets/styles/app.scss` et tous les fichiers `*.scss`
- Supprimer `config/packages/symfonycasts_sass.yaml` (si présent)

### 6.3 Retirer Bootstrap du `base.html.twig`

Supprimer la ligne `app.scss` si elle est encore présente.

### 6.4 Réactiver Preflight complet dans `app.css`

Revenir à `@import "tailwindcss"` (sans `layer(utilities)`).

### 6.5 Retirer `importmap` Bootstrap (si applicable)

Vérifier `config/routes/importmap.php` : retirer `bootstrap` si présent.

### 6.6 Mettre à jour `composer.json` scripts

```json
"compile-assets": [
    "php bin/console tailwind:build --minify --no-interaction",
    "php bin/console asset-map:compile --no-interaction"
]
```

---

## Phase 7 — Installer Symfony UX Toolkit (shadcn kit)

### 7.1 Prérequis

- Tailwind v4 configuré avec les variables CSS shadcn (Phase 2 déjà faite ✓)
- `symfony/ux-twig-component` installé (déjà présent ✓)

### 7.2 Installer le kit

```bash
composer require symfony/ux-toolkit
php bin/console ux:toolkit:install shadcn
```

> Cette commande copie les composants Twig dans `templates/components/ui/` et les classes PHP dans `src/Twig/Components/Ui/`.

### 7.3 Vérifier l'import CSS

La commande d'installation met à jour `assets/styles/app.css` — vérifier qu'elle n'a pas écrasé les variables définies en Phase 2.

### 7.4 Composants disponibles (40+)

```
Accordion, Alert, AlertDialog, Avatar, Badge, Breadcrumb, Button,
Card, Checkbox, Dialog, Empty, Field, Input, Label, Pagination,
Progress, RadioGroup, Select, Skeleton, Spinner, Switch, Table,
Tabs, Textarea, Toggle, Tooltip, Typography, …
```

---

## Phase 8 — Remplacer les composants custom par shadcn

Mapper les composants existants vers leurs équivalents shadcn :

| Composant actuel | Composant shadcn |
|---|---|
| `components/flash_messages.html.twig` | `<twig:Ui:Alert>` |
| `components/breadcrumb.html.twig` | `<twig:Ui:Breadcrumb>` |
| Boutons | `<twig:Ui:Button>` |
| Cards | `<twig:Ui:Card>` |
| Modals | `<twig:Ui:Dialog>` |
| Inputs/forms | `<twig:Ui:Input>`, `<twig:Ui:Field>` |
| Badges | `<twig:Ui:Badge>` |
| Pagination | `<twig:Ui:Pagination>` |
| Tables | `<twig:Ui:Table>` |
| Tabs | `<twig:Ui:Tabs>` |

---

## Commandes de référence

```bash
# Développement
php bin/console tailwind:build --watch

# Production
php bin/console tailwind:build --minify
php bin/console asset-map:compile

# Dump de la config
php bin/console config:dump symfonycasts_tailwind

# Symfony CLI (lance watcher automatiquement)
symfony server:start
symfony server:log
```

---

## Checklist de fin de migration

- [ ] Phase 1 : TailwindBundle installé, `app.css` compilé sans erreur
- [ ] Phase 2 : Variables shadcn définies, `@theme inline` configuré, `@source` templates pointé
- [ ] Phase 3 : 39 composants partagés migrés (Bootstrap classes → Tailwind)
- [ ] Phase 4 : `base.html.twig` et `base_with_sidebar.html.twig` migrés
- [ ] Phase 5 : 138 templates de pages migrés (auth, légal, public, dashboard, outils, admin)
- [ ] Phase 6 : `twbs/bootstrap` et `symfonycasts/sass-bundle` supprimés, SCSS supprimé
- [ ] Phase 7 : `symfony/ux-toolkit` installé, kit shadcn initialisé
- [ ] Phase 8 : Composants custom remplacés par les composants shadcn
- [ ] Validation finale : build prod sans erreur, tests visuels sur tous les outils qualité

---

## Pièges à éviter

| Piège | Solution |
|---|---|
| Utiliser le guide Tailwind officiel Symfony (Webpack Encore) | Utiliser **uniquement** TailwindBundle |
| Lancer `asset-map:compile` avant `tailwind:build` | Respecter l'ordre : Tailwind d'abord |
| Oublier `@source` pour les templates Twig | Les templates sont hors du dossier `assets/` — déclarer explicitement |
| Créer un `tailwind.config.js` en v4 | v4 = configuration 100 % CSS, pas de config JS |
| Mélanger Bootstrap et Tailwind Preflight | Utiliser `layer(utilities)` pendant la coexistence |
| Supprimer Bootstrap avant d'avoir migré tous les templates | Migration bottom-up, Bootstrap reste jusqu'à la Phase 6 |
