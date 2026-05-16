# Design system UI — Outils-Qualité (Shadcn via Symfony UX Toolkit)

Référence projet interne. Documentation officielle du kit : [Symfony UX Toolkit — Shadcn](https://ux.symfony.com/toolkit/kits/shadcn).

## Principes

1. **Un composant toolkit** dès qu’il couvre le besoin ; pas de duplication « maison » sauf encapsulation mince.
2. **Tokens** : privilégier `bg-background`, `bg-card`, `text-foreground`, `border-border`, `text-muted-foreground`, `primary` — éviter `bg-white` / `text-gray-600` en dur (préparation dark mode).
3. **Accessibilité** : conserver les vrais titres (`h1`–`h3`) là où la hiérarchie SEO / lecteur d’écran est requise ; les `Card:Title` toolkit sont des `div` — utiliser un `h*` dans le header si besoin.
4. **Turbo** : ne pas retirer `data-turbo="false"` sur formulaires sensibles (ex. login) sans test explicite.

## Boutons (`twig:Button`)

| Intention | `variant` | `size` | Notes |
|-----------|-----------|--------|------|
| Action principale (créer, enregistrer, se connecter) | `default` | `default` ou `lg` sur mobile si besoin | `as="a"` pour liens |
| Secondaire (annuler, retour) | `outline` ou `ghost` | `default` | |
| Destruction (supprimer, annuler CAPA) | `destructive` | `default` | |
| Lien discret | `link` | `default` | |
| Icône seule (fermer modale) | `ghost` | `icon` | `aria-label` obligatoire |

## Cartes (`twig:Card`)

Structure recommandée :

```twig
<twig:Card class="border-border/80 shadow-sm">
    <twig:Card:Header class="p-4 sm:p-5 pb-2 space-y-1">
        {# h3 ou titre visuel #}
    </twig:Card:Header>
    <twig:Card:Content class="p-4 sm:p-5 pt-0">
        {# corps #}
    </twig:Card:Content>
    <twig:Card:Footer class="p-4 sm:p-5 pt-0">
        {# actions optionnelles #}
    </twig:Card:Footer>
</twig:Card>
```

Les classes `p-*` sur `Header` / `Content` permettent d’ajuster le **padding** sans doubler la bordure du kit.

## Badges (`twig:Badge`)

- Statuts neutres / compteurs secondaires : `variant="secondary"` ou `outline`.
- Alerte / retard / critique : `variant="destructive"`.
- Mise en avant positive : `variant="default"`.

## Formulaires

| Élément | Composant | Rappel |
|---------|-----------|--------|
| Texte court | `twig:Input` | Passer `type`, `name`, `id`, `value`, `autocomplete`, `required` via attributs. |
| Zone texte | `twig:Textarea` | Contenu initial dans le **block** du composant. |
| Liste | `twig:Select` | Options dans le block. |
| Case à cocher | `twig:Checkbox` | |
| Libellé | `twig:Label` | Attribut `for` aligné sur `id` du champ. |

Erreurs serveur : réutiliser [`templates/components/form/field_error.html.twig`](templates/components/form/field_error.html.twig) ou `twig:Alert variant="destructive"` sous le champ.

## Tables (`twig:Table`)

```twig
<twig:Table>
    <twig:Table:Header>
        <twig:Table:Row>
            <twig:Table:Head>Colonne</twig:Table:Head>
        </twig:Table:Row>
    </twig:Table:Header>
    <twig:Table:Body>
        <twig:Table:Row>
            <twig:Table:Cell>…</twig:Table:Cell>
        </twig:Table:Row>
    </twig:Table:Body>
</twig:Table>
```

## États vides (`twig:Empty`)

Utiliser pour toute liste vide métier (CAPA, audits, risques, résultats de recherche).

```twig
<twig:Empty>
    <twig:Empty:Header>
        <twig:Empty:Title>Titre</twig:Empty:Title>
        <twig:Empty:Description>Description courte.</twig:Empty:Description>
    </twig:Empty:Header>
    <twig:Empty:Content>
        <twig:Button as="a" href="…">Action</twig:Button>
    </twig:Empty:Content>
</twig:Empty>
```

`Empty:Media` + `ux:icon` est optionnel (dépend des icônes disponibles).

## Skeleton (`twig:Skeleton`)

Réserver aux chargements **asynchrones** ou frames Turbo différés ; éviter sur contenu SSR principal (dashboard) pour ne pas dégrader la perception de vitesse.

## Feedback / Toasts

- **Aujourd’hui** : flashes Symfony rendus via [`templates/components/flash_messages.html.twig`](templates/components/flash_messages.html.twig) (`twig:Alert`).
- **Évolution** : un seul système « toast » global pourrait s’appuyer sur le même design tokens ; **ne pas** dupliquer message flash + toast pour le même événement sans règle métier claire.

## Dark mode (préparation)

- S’appuyer sur les variables CSS du thème (déjà utilisées par les classes Shadcn du toolkit).
- Éviter les couleurs fixes non tokenisées dans les nouveaux blocs.
- Ne pas activer un toggle dark tant que les écrans prioritaires (dashboard, QSE) n’ont pas été passés en revue.

## Pagination

Utiliser `twig:Pagination` du kit **uniquement** lorsque la pagination serveur existe et que la volumétrie le justifie (voir audit).

## Aide contextuelle (`twig:ContextualHelp`)

Registre central : [`config/help/contextual_help.yaml`](../config/help/contextual_help.yaml) — clés `help.{domaine}.{sujet}`.

| Besoin | Composant | Exemple |
|--------|-----------|---------|
| 1 ligne courte | `twig:Tooltip` | Pastilles métadonnées outils |
| Titre + description | `twig:ContextualHelp` | Sidebar, KPI, filtres audit |
| Documentation longue | Accordéon `tool_help_*` | Méthodes qualité |

**Variantes** : `sidebar` (panneau sombre à droite), `inline` / `field` (panneau clair sous le déclencheur).

**Twig** : `help('help.nav.audit')`, `help_title()`, `help_description()`, `help_exists()`.

**KPI** : `helpId` sur `twig:KpiStatCard` — icône `?` + survol ; `helperVisible` pour garder un sous-texte visible si besoin.

**Mobile** : écrans tactiles → icône `?` cliquable (`contextual_help` Stimulus), pas de survol seul.

**Règle** : ne pas envelopper chaque ligne de tableau ; cibler libellés, KPI et actions sensibles uniquement.
