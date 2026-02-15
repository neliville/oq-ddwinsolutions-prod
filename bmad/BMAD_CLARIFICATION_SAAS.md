# Re-clarification BMAD — Option SaaS payant

**Positionnement réel :** outils-qualite.com est un **SaaS "soft"** à montée en puissance : gratuit aujourd’hui, freemium demain, payant à terme si la valeur est démontrée.

---

## B – Business (mise à jour clé)

### Ce que le site N’EST PAS

- Un simple blog
- Un outil gratuit éternel
- Un SaaS enterprise complexe

### Ce que le site EST

- Un **produit d’appel expert QSE**
- Un **laboratoire d’outils métiers**
- Un **futur SaaS modulaire**

### Objectifs business (hiérarchisés)

| Horizon | Objectifs |
| ------- | --------- |
| **Court terme** | Trafic qualifié, leads, crédibilité |
| **Moyen terme** | Comptes utilisateurs actifs, récurrence d’usage, différenciation par la valeur |
| **Long terme** | Monétisation ciblée : fonctionnalités avancées, export, historique, IA, multi-projets |

**Principe BMAD :** préparer la monétisation dans le **modèle**, pas dans le pricing.

---

## M – Model (point clé pour la monétisation)

Pour pouvoir facturer plus tard, le modèle métier distingue **3 niveaux**, même si tout est gratuit aujourd’hui.

### 1. L’outil (Tool)

- Ishikawa, 5 Why, QQOQCCP, AMDEC, Pareto, 8D
- Générateurs, checklists
- **Toujours accessible** (freemium / découverte)

### 2. L’usage (Usage / Session)

- Nombre de créations
- Historique
- Sauvegarde, duplication
- Export (PDF, Word, etc.)
- **C’est ici que se trouve la valeur monétisable**

### 3. L’utilisateur (User)

- Anonyme
- Compte gratuit
- Compte premium (plus tard)
- Le modèle doit **déjà** prévoir le premium, même sans paiement activé

### Décision BMAD structurante

> On ne facture jamais l’outil, on facture **l’usage** et le **confort**.

Exemples futurs de valeur facturée :

- Historique illimité
- Exports PDF/Word
- IA d’assistance
- Multi-espaces
- Sauvegarde cloud

---

## A – Architecture (préparer sans complexifier)

### Ce qu’il faut faire maintenant (léger)

1. **Séparer "outil" et "persistance"**
   - Utiliser les outils sans compte possible
   - Limiter (même largement) : sauvegarde, export, historique
   - Aucun paiement requis pour l’instant

2. **Introduire la notion de quota (sans facturation)**
   - Quota très large au début
   - Exemples : 5 exports / mois, 3 projets sauvegardés, IA limitée
   - Le jour où le paiement est activé, tout est prêt

3. **Centraliser les règles d’accès**
   - Couche unique : **FeatureAccessService**
   - Méthodes : `canExport()`, `canSave()`, `canUseAI()`
   - Aujourd’hui : `return true;`
   - Demain : `return $user->hasPlan('premium');`
   - **Zéro refactor** au moment de la monétisation

---

## D – Delivery (roadmap BMAD réaliste)

| Phase | Contenu |
| ----- | ------- |
| **Phase 1 – Maintenant** (gratuit, trafic) | Outils accessibles, UX fluide, pas de friction, collecte d’emails |
| **Phase 2 – Pré-freemium** | Compte utilisateur, historique limité, exports bridés, message "débloquer" |
| **Phase 3 – Activation payante** (quand trafic OK) | Une offre simple, pas d’usine à gaz, Stripe / autre plus tard |

**Principe BMAD :** la monétisation est une **conséquence**, pas un objectif initial.

### Ce que BMAD évite

- Ajouter Stripe trop tôt
- Créer 5 plans compliqués
- Brider l’utilisateur trop vite
- Refaire l’architecture plus tard

On avance progressivement, **sans dette stratégique**.

---

## Recommandations concrètes (actionnables maintenant)

### Priorité 1 – Modèle métier

Créer dès maintenant les concepts : **Tool**, **Usage**, **UserCapability** (même si tout est gratuit).

### Priorité 2 – UX honnête

Toujours afficher pour les fonctions à monétiser plus tard :

> « Fonctionnalité avancée – bientôt disponible »

Pour préparer psychologiquement l’utilisateur.

### Priorité 3 – Code "future-proof"

Chaque fonctionnalité sensible (export, sauvegarde, IA) doit passer par :

- Un **service**
- Une **règle d’accès** (FeatureAccessService)
- **Jamais** un `if ($user)` en dur dans un contrôleur

---

## Résumé

| Aujourd’hui | Demain |
| ----------- | ------ |
| Gratuit | Freemium |
| Outils | Usages |
| Visiteurs | Utilisateurs |
| Valeur perçue | Valeur facturée |

Voir aussi : [Vue d’ensemble](./project-overview.md), [Règles métier](./business-rules.md), [FeatureAccessService](../src/Service/FeatureAccessService.php).
