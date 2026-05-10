# Rapport d’audit UI — alignement Shadcn / Symfony UX Toolkit

**Périmètre** : templates Twig, composants UX Toolkit déjà présents, cohabitation modales, cockpit QSE, auth, admin custom (pas EasyAdmin).  
**Méthode** : inventaire par recherche dans `templates/` + revue ciblée des écrans prioritaires (mai 2026).

## Synthèse exécutive

| Catégorie | Constat | Gravité |
|-----------|---------|---------|
| Cohérence cartes | Forte utilisation de `rounded-lg border bg-card` **sans** `twig:Card` (dashboard, QSE, admin) | Moyenne |
| Boutons | Mélange `<a class="bg-primary…">`, `<button class="px-3…">` et `twig:Button` (modales, flash) | Moyenne |
| Modales | **BootstrapModal** (`app-modal`) + **Dialog** (`ux-dialog`) + modales outils | Élevée (det technique) |
| Formulaires | Login encore `form-control` / `form-label` ; CAPA show déjà proche Tailwind mais sans `twig:Input` | Moyenne |
| Tables QSE | Listes `<ul>` / cartes cliquables, pas `twig:Table` | Faible à moyenne (lisibilité SaaS) |
| États vides | Texte `<p class="text-muted-foreground">` sans `twig:Empty` | Faible |
| AOS / KPI | `data-aos` sur grille KPI dashboard (impact LCP perçu) | Faible |
| Toolkit | `symfony/ux-toolkit` + composants projet miroir ([`templates/components/Card.html.twig`](templates/components/Card.html.twig) aligné vendor Shadcn) | — |

## Inventaire chiffré (indicatif)

- Fichiers `templates/**/*.twig` contenant **`bg-card`** : ~40 fichiers (dont [`templates/dashboard/index.html.twig`](templates/dashboard/index.html.twig) très dense, [`templates/admin/dashboard/index.html.twig`](templates/admin/dashboard/index.html.twig), modules QSE).
- Fichiers avec **`twig:`** (composants toolkit / surcharges projet) : ~20 fichiers sous `templates/components/` (Button, Alert, Dialog, Pagination, Breadcrumb, etc.).
- Fichiers avec **`BootstrapModal` / `app-modal` / `ux-dialog`** : une vingtaine de correspondances (outils + composants partagés).

## Zone par zone

### Layout & navigation

| Fichier | Constat | Remplacement cible |
|---------|---------|-------------------|
| [`base_with_sidebar.html.twig`](templates/base_with_sidebar.html.twig), [`navbar.html.twig`](templates/components/navbar.html.twig) | CTA en classes utilitaires | `twig:Button` (`as="a"`) pour actions primaires |
| [`breadcrumb.html.twig`](templates/components/breadcrumb.html.twig) | Déjà structuré UX / SEO | Conserver ; harmoniser classes si besoin |

### Dashboard utilisateur

| Fichier | Constat | Remplacement cible |
|---------|---------|-------------------|
| [`dashboard/index.html.twig`](templates/dashboard/index.html.twig) | 6 blocs cockpit + KPI : sections `article` / `section` manuelles | `twig:Card` + `Card:Header` / `Card:Content` ; `twig:Badge` pour compteurs ; retirer `data-aos` sur la grille KPI ; CTA header en `twig:Button` |
| Placeholder IA | `div` dashed | `twig:Card` variant bordure ou `Empty` stylisé |

### QSE — CAPA / Audit / Risques / PDCA

| Fichier | Constat | Remplacement cible |
|---------|---------|-------------------|
| [`qse/capa/index.html.twig`](templates/qse/capa/index.html.twig) | Liste liens | `twig:Table` + `twig:Badge` + `twig:Empty` |
| [`qse/capa/show.html.twig`](templates/qse/capa/show.html.twig) | Champs + workflow boutons bruts | `twig:Card` sections, `Label`/`Input`/`Textarea`/`Select`, `twig:Button` par sémantique |
| [`qse/audit/index.html.twig`](templates/qse/audit/index.html.twig) | Idem | Idem |
| [`qse/risk/index.html.twig`](templates/qse/risk/index.html.twig) | Idem | Idem |
| [`qse/pdca/index.html.twig`](templates/qse/pdca/index.html.twig) | Petites cartes border | `twig:Card` (vague 2–4) |

### Modales & confirmations

| Fichier | Constat | Stratégie |
|---------|---------|-----------|
| [`BootstrapModal.html.twig`](templates/components/BootstrapModal.html.twig) | Shell + `twig:Button` déjà | Migrer le contenu des includes vers pattern Dialog **par vagues** ; garder `data-turbo="false"` où nécessaire |
| [`DeleteConfirmationModal.html.twig`](templates/components/DeleteConfirmationModal.html.twig) | Mix | `AlertDialog` toolkit si pattern validé, sinon contenu 100 % `twig:Button` |
| Modales *Analyses* (Ishikawa, etc.) | Couplage JS | **Reporter** ou micro-PRs ; risque régression élevé |

### Auth & compte

| Fichier | Constat | Remplacement cible |
|---------|---------|-------------------|
| [`security/login.html.twig`](templates/security/login.html.twig) | `form-control`, alerts custom | `twig:Input`, `twig:Label`, `twig:Checkbox`, `twig:Button`, `twig:Alert` |
| [`registration/register.html.twig`](templates/registration/register.html.twig), [`profile/index.html.twig`](templates/profile/index.html.twig) | À traiter en même vague | Idem |

### Flash / feedback

| Fichier | Constat | Décision |
|---------|---------|----------|
| [`flash_messages.html.twig`](templates/components/flash_messages.html.twig) | Déjà `twig:Alert` | **Source unique** pour messages page ; toasts globaux = évolution ultérieure (documenter, éviter double affichage) |

### Admin (Twig custom)

| Fichier | Constat | Remplacement cible |
|---------|---------|-------------------|
| [`admin/list_view_shell.html.twig`](templates/components/admin/list_view_shell.html.twig) | `bg-white` en tête de shell | Tokens `bg-card` / `bg-muted` ; toggles en `twig:Button` |
| [`admin/dashboard/index.html.twig`](templates/admin/dashboard/index.html.twig) | Nombreuses cartes manuelles | `twig:Card` par blocs (progressif) |

### Outils d’analyse (Ishikawa, 5 Why, …)

| Constat | Recommandation |
|---------|----------------|
| Gros JS, canvas, modales liste | **Dernier** dans la roadmap ; uniquement harmonisation couleurs / boutons de bord sans toucher au cœur |

---

## Ce qui peut rester temporairement

- **BootstrapModal** pour modales « mes analyses » tant que le JS `app-modal` n’est pas réécrit.
- **Classes Tailwind** sur blocs très spécifiques (graphiques) si aucun équivalent Card sans casser le layout.
- **Pagination** manuelle tant que la volumétrie ne justifie pas `twig:Pagination`.

## Quick wins (déjà partiellement couverts par la migration vague 1–3)

1. Remplacer les CTA primaires dashboard par `twig:Button as="a" variant="default"`.
2. Retirer `data-aos` de la grille KPI (chemin critique).
3. Unifier les états vides QSE avec `twig:Empty` + CTA.

## Composants Shadcn prioritaires (par zone)

1. **Card** (+ Header / Content / Footer) — dashboard, QSE, admin.  
2. **Button** — partout où CTA métier.  
3. **Badge** — statuts CAPA, audit, risques.  
4. **Table** — index CAPA / audit / risques.  
5. **Empty** — listes vides.  
6. **Input / Label / Textarea / Select / Checkbox** — login, CAPA show, profil.  
7. **Alert** — erreurs login (alignement sur flash).  
8. **Skeleton** — réservé aux futures zones async (pas obligatoire SSR).

## Dette UI actuelle (ordre de gravité)

1. **Trois familles de modales** (dette comportementale + accessibilité).  
2. **Formulaires auth** hors design system.  
3. **Listes QSE** peu « SaaS table ».  
4. **Admin** : `bg-white` et cartes non alignées au front cockpit.

## Ordre d’implémentation recommandé

1. Dashboard → 2. CAPA → 3. Audit → 4. Risques → 5. Tables / pagination mesurée → 6. Modales → 7. Forms → 8. Toasts (décision unique) → 9. Admin → 10. Dark prep (variables, suppression couleurs en dur).

## Impact business attendu

- **Confiance** : interface homogène (cards, tables, badges) renforce la crédibilité « plateforme » vs « page isolée ».  
- **Conversion** : parcours login / inscription plus « produit » → friction réduite.  
- **Rétention** : cockpit dashboard lisible → retour utilisateur sur les actions urgentes.  
- **Monétisation** : perception premium alignée sur un pricing futur sans changer la logique métier.

## Risques techniques

- Régressions **Turbo** (formulaires `data-turbo="false"` à conserver sur login).  
- Tests fonctionnels dépendant du libellé bouton (`Se connecter`) — conserver le texte visible.  
- **Live Components** : bundle présent ; toute évolution Twig doit préserver les attributs `data-controller` / `data-action` existants sur les zones concernées.

---

## Implémentation réalisée (lot courant)

- **Dashboard** : `twig:Card`, `twig:Badge`, `twig:Button` sur le cockpit et les KPI ; suppression des `data-aos` sur la grille KPI ; slot IA en `Card`.
- **CAPA / Audit / Risques** : listes en `twig:Table` dans `Card` ; états vides `twig:Empty` ; fiche CAPA en `Card` + champs `Input` / `Label` / `Select` / `Textarea` / `Button`.
- **Login / profil** : champs et alertes alignés toolkit ; `flash_messages` documenté comme **source unique** (pas de double toast).
- **Admin** : shell liste (`list_view_shell`) — en-tête tokenisé (`bg-muted/40`) ; bascule tableau/cartes conservée en `<button>` natif pour compatibilité avec `admin_list_view_controller.js`.
- **Performance** : aucune entrée ajoutée à `importmap.php` pour ce lot (composants Twig uniquement).

### Impact business (rappel)

Perception « plateforme » renforcée sur les parcours **dashboard → listes QSE → détail CAPA** et **connexion**, sans modifier la logique métier ni les routes.
