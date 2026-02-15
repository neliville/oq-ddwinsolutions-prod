# Clarification BMAD — La suite du projet

**Date :** 2026-01-28  
**Méthode :** Avant toute proposition, clarifier valeur business, modèle métier, impact architecture, faisabilité delivery.

---

## État des lieux (déjà fait)

| Domaine | Réalisé |
|--------|--------|
| **Brevo** | Intégration, BrevoSyncService, SyncLeadToBrevoMessage, emails (confirmation, notification admin) |
| **Messenger** | LeadScoringMessage, LeadQualificationMessage, LeadEnrichmentMessage + handlers, routing async |
| **Freemium** | User (plan, premiumUntil, exportCountThisMonth, lastExportReset), QuotaService, FeatureAccessService (canExport, canSave, canUseAI) |
| **Export connecté** | Téléchargement/export réservé aux utilisateurs connectés ; message + bouton « Se connecter » uniforme sur toutes les pages de création |
| **Admin** | Routes ^/admin protégées ROLE_ADMIN ; ROLE_USER redirigé vers /dashboard |
| **IA (préparation)** | ToolAnalysisInterface, DTOs par outil, endpoint POST /api/tools/{tool}/suggest (stub) |
| **Documentation** | architecture.md, migration-guide.md, integration-points.md, CLARIFICATION_EXPORT_CONNECTE.md à jour |

**Restant du plan BMAD technique :** Phase 1 (namespaces PHP), Phase 2 (JS centralisation), Phase 3 (SCSS restructuration), Phase 6 (doc finale).

---

## Option A — Poursuivre le plan technique (Phase 1, 2, 3, 6)

### 1. Valeur business

- **Indirecte :** Pas de nouvelle fonctionnalité visible pour l’utilisateur.
- **Bénéfices :** Code plus lisible, frontières Site / Tools / Lead explicites, maintenance et onboarding simplifiés, base propre pour les prochaines évolutions (IA, freemium UX).
- **Risque si on ne le fait pas :** Dette technique ; ajouts futurs (nouveaux outils, nouveaux parcours) restent éparpillés dans `Controller/`.

### 2. Modèle métier

- **Aucun changement** sur le modèle métier (Tool, Usage, User, Lead).
- Les phases 1/2/3 ne touchent pas aux règles métier ni aux quotas ; elles réorganisent uniquement le code (namespaces, dossiers JS/SCSS).

### 3. Impact architecture

- **Phase 1 (namespaces) :** Déplacement de contrôleurs vers `Site/Controller/`, `Tools/Controller/`, `Lead/Controller/` (ou équivalent) ; routes inchangées ; `config/services.yaml` / autowiring à adapter.
- **Phase 2 (JS) :** Déplacement de `public/js/` vers `assets/js/` par domaine (site, tools, lead) ; éventuellement Stimulus par outil ; pas de changement de comportement fonctionnel.
- **Phase 3 (SCSS) :** Déplacement des SCSS de `pages/` vers `site/`, `tools/`, `lead/` ; imports à mettre à jour.
- **Phase 6 (doc) :** Mise à jour de README, index BMAD, vérification de cohérence des docs.

Impact : **structure uniquement**, pas de nouveau service ni de changement de flux métier.

### 4. Faisabilité delivery

- **Estimation (plan actuel) :** Phase 1 (2–3 h), Phase 2 (2–3 h), Phase 3 (1–2 h), Phase 6 (≈30 min) → **6–9 h**.
- **Risque :** Régression si les routes ou les chemins d’assets sont mal mis à jour ; **mitigation :** tests fonctionnels et manuels après chaque phase.
- **Faisable en continu** sans blocage externe (pas d’API, pas de nouveau déploiement spécifique).

---

## Option B — Renforcer le produit (valeur visible)

Exemples (à prioriser selon objectifs) :

- **IA réelle :** Brancher un fournisseur (OpenAI, autre) sur `/api/tools/{tool}/suggest` pour des suggestions concrètes par outil.
- **UX freemium :** Afficher les quotas (ex. « 3 exports restants ce mois »), messages « Passez en premium pour débloquer », sans activer le paiement.
- **Nouveaux parcours / outils :** Nouvel outil ou nouvelle page SEO (décision produit).

### 1. Valeur business

- **Directe :** Différenciation (IA), clarté des limites (UX quotas), trafic ou rétention (nouveaux parcours).
- **Alignée avec la vision :** « Produit d’appel expert QSE », « facturer l’usage et le confort » plus tard.

### 2. Modèle métier

- **IA :** Reste dans le cadre Tool / Usage ; pas de nouveau niveau utilisateur, mais usage d’un « service IA » potentiellement quotaé plus tard (canUseAI déjà prévu).
- **UX quotas :** Expose le modèle existant (QuotaService, FeatureAccessService) ; pas de nouvelle règle métier, seulement affichage et messages.

### 3. Impact architecture

- **IA :** Nouveau service (ex. `ToolSuggestService`) appelant une API externe ; DTOs et endpoint déjà en place ; configuration (clé API, etc.) à documenter dans integration-points.
- **UX quotas :** Léger : passage des infos quota (ex. `exportCountThisMonth`, limite) aux templates ou au front ; pas de refonte.

### 4. Faisabilité delivery

- **IA :** Dépend d’un choix de fournisseur et d’une clé API ; 1–3 j selon complexité des prompts et du format de réponse.
- **UX quotas :** 0,5–1 j si limité à l’affichage des quotas et messages « débloquer ».

---

## Recommandation (principe BMAD)

- **Court terme :**  
  - Soit **Option A (Phase 1 puis 3)** pour stabiliser la structure sans toucher au métier, avec peu de risque.  
  - Soit **Option B (UX quotas)** si la priorité est de rendre le freemium lisible pour l’utilisateur tout de suite.
- **Moyen terme :** Option B (IA réelle) dès que la valeur est jugée prioritaire et qu’un fournisseur est choisi.
- **Principe :** Avant toute implémentation, valider la priorité (technique vs valeur visible) avec le product owner / décideur, puis avancer une phase à la fois en gardant les quatre clarifications (valeur business, modèle métier, impact architecture, faisabilité delivery) à jour pour la prochaine étape.

---

## Résumé

| Critère | Option A (Plan technique) | Option B (Produit / IA ou UX) |
|--------|----------------------------|--------------------------------|
| **Valeur business** | Indirecte (maintenabilité, base propre) | Directe (IA, clarté freemium) |
| **Modèle métier** | Inchangé | Inchangé ou exposition des quotas |
| **Impact architecture** | Structure (namespaces, JS, SCSS, doc) | Léger à modéré (service IA ou templates) |
| **Faisabilité delivery** | 6–9 h, faible risque | 0,5–3 j selon scope |

La « suite » peut être soit **Phase 1 (namespaces)** pour enchaîner le plan BMAD, soit **UX quotas** ou **IA réelle** pour maximiser la valeur perçue en premier ; le choix dépend de la priorité : stabilité technique vs valeur utilisateur immédiate.
