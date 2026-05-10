# Rapport — Refonte landing homepage (mai 2026)

## Sections modifiées ou ajoutées

| Section | Fichier | Rôle |
|--------|---------|------|
| Hero | `templates/home/sections/_hero_landing.html.twig` | Positionnement SaaS QHSE, CTA, badges, aperçu produit 3 tuiles |
| Preuve de valeur | `_value_cards.html.twig` | Ancre `#fonctionnalites`, 5 piliers (Analyse, Audit, CAPA, Risques, Dashboard) |
| Avant / Après | `_before_after.html.twig` | Cartes comparatives + flèche de transition |
| Workflow | `_workflow.html.twig` | Ancre `#workflow`, 5 étapes numérotées |
| Dashboard | `_dashboard_focus.html.twig` | Ancre `#dashboard-focus`, CTA cockpit |
| Audits ISO | `_audits_iso.html.twig` | 3 normes + CTA audit |
| CAPA | `_capa_focus.html.twig` | Argumentaire + badges critères + CTA |
| Personas | `_personas.html.twig` | 7 cartes métiers |
| Preuve sociale | `_social_proof_compact.html.twig` | Stats + témoignages (conservés, `#preuve-sociale`) |
| Outils gratuits | `_outils_gratuits.html.twig` | Ancre `#outils`, contenu SEO/outils inchangé en substance |
| CTA final | `_cta_final.html.twig` | Double CTA, classes `js-final-cta` |
| FAQ | `_faq.html.twig` | Inchangé, `#faq` |
| Newsletter | `_newsletter_section.html.twig` | `#newsletter` |
| Expertise | `_expertise_section.html.twig` | `#expertise` |
| Orchestration | `templates/home/index.html.twig` | Blocs SEO + includes |

Ancres navbar préservées : `#hero`, `#fonctionnalites`, `#outils`, `#faq`, `#expertise`, `#newsletter`.

## Composants Shadcn (Twig) utilisés

- **Card** (+ Header, Title, Description, Content) : valeur, avant/après, workflow, audits, personas, aperçu dashboard.
- **Button** : CTA principaux/secondaires (y compris `as="a"`).
- **Badge** : hero, cartes workflow, critères CAPA, tuiles hero.
- **Separator** : sous-titre avant/après, séparation dans mock dashboard.

Non utilisés sur cette page (volontairement pour limiter le JS et ne pas refondre la FAQ) : Tabs, Accordion, Dialog, Tooltip, Table, Alert, Skeleton — disponibles pour itérations futures.

## Améliorations UX

- Message produit en premier (hero) avec hiérarchie claire invité vs connecté sur les CTA sensibles.
- Parcours logique : valeur → contraste terrain → flux → cockpit → normes → CAPA → cibles → confiance → outils → conversion.
- Aperçu hero visible sur mobile (nouveau bloc `.home-hero-preview`, distinct de l’ancien mockup masqué sur petit écran).

## Impacts SEO

- **Title** et **meta description** enrichis (plateforme + audits ISO + CAPA + risques + dashboard + outils gratuits + mots-clés conservés).
- **Keywords** : ajout de requêtes « plateforme QHSE », « pilotage », « CAPA », etc., tout en gardant les termes outils existants.
- **Un seul `h1`** (titre hero). **`h2`** par section principale.
- Contenu long tail des outils et FAQ conservés pour l’indexation.

## Classes préparées pour GSAP (hooks `js-*`)

| Classe | Zone |
|--------|------|
| `js-hero-title`, `js-hero-subtitle`, `js-hero-cta`, `js-hero-preview` | Hero |
| `js-kpi-card` | Stats hero + grille valeur |
| `js-before-after`, `before-card`, `after-card`, `transition-arrow` | Avant/après |
| `js-workflow-step`, `workflow-step-1` … `workflow-step-5` | Flux |
| `js-dashboard-preview` | Focus dashboard |
| `js-final-cta`, `js-final-cta-title`, `js-final-cta-subtitle` | CTA final |

## Points à surveiller

- **FAQ / JSON-LD** : textes FAQ encore orientés « outils » ; harmonisation possible avec le discours plateforme (contenu + schema).
- **Matomo / GTM** : non modifiés (`base.html.twig`).
- **Environnement CI** : exécuter `php bin/phpunit tests/Functional/HomeControllerTest.php` lorsque `vendor` / phpunit-bridge sont complets.

## Performance

- **HomeController** : suppression des comptages `LeadRepository` / `NewsletterSubscriberRepository` non utilisés dans la vue — moins de requêtes SQL sur `/`.

## Recommandations futures

- Brancher **GSAP** sur les sélecteurs `js-*` (reveal hero, stagger cartes, avant/après).
- Captures **WebP** optionnelles pour remplacer les mockups CSS si besoin marketing.
- Enrichir la section audits avec **liens profonds** vers pages référentiels si routes publiques ajoutées.
- Envisager **Accordion** shadcn pour la FAQ (en conservant le FAQPage schema aligné sur le HTML).
