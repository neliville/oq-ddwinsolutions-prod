# Inventaire technique — cockpit QSE (mai 2026)

## Décisions figées

### Record (`App\Entity\Record`) vs entités QSE dédiées

- **Cœur métier audit / CAPA / risques** : entités Doctrine dédiées sous `App\Entity\Qse\` — pas de duplication du référentiel ISO dans `Record.content`.
- **`Record`** reste le mécanisme générique existant pour les sauvegardes transverses côté front ; aucun lien obligatoire vers `Audit` pour le MVP. Une future carte « tout mon patrimoine » pourra référencer un `externalRef` sans remplacer les tables métier.

### Ownership

- Aligné sur `RecordController` : toute requête filtre par `user` / `owner` courant.
- **`AuditRequirement`** : référentiel global (pas de `owner`) ; seed idempotent par `legacyKey` (`exig_*`).

### Sécurité

- Persistance QSE : routes sous `/dashboard/...` avec `#[IsGranted('ROLE_USER')]` — pas d’entrée `PUBLIC_ACCESS` nouvelle pour écriture.
- Outils publics et `^/api/*/save` : inchangés.

### Exports PDF existants

- Non modifiés. Exports audit ultérieurs : nouvelles routes ou export client dédié.

## Références code

- Utilisateurs : `src/Entity/User.php`
- Agrégation dashboard outils : `src/Repository/AnalyticsRepository.php`
- API sauvegardes génériques : `src/Tools/Api/RecordController.php`
- Sécurité : `config/packages/security.yaml`
- Quotas / IA future : `src/Service/FeatureAccessService.php`

## Schéma `metadata` JSON (extensions / IA)

- **`Audit.metadata`** : `{ "version": 1, "ai": { "hints": [] } }` — réservé ; vide autorisé.
- **`AuditEvaluation.metadata`** : idem, par ligne d’audit.
- **`CAPAAction.metadata`** : `_schema` (int), `source`, `legacy_requirement_key`, `audit_id` pour traçabilité ; `_legacy_origin` (chaîne) si migration depuis l’ancienne enum pour les valeurs `incident` / `other` ; champs additionnels futurs sous clé `ai`.

## Entité `CapaOrigin` (origines CAPA)

- Table `qse_capa_origin` : `name` (libellé FR), `slug` (ASCII unique global), `kind` (`system` \| `custom`), `active`, `owner_id` (nullable pour les lignes système).
- **Unicité** : slug unique sur toute la table (origines personnalisées : slug dérivé du nom + suffixe numérique en cas de collision).
- **Seed système** : commande `app:qse:seed-capa-origins` (idempotent) — 8 slugs : `ishikawa`, `cinq-pourquoi`, `amdec`, `8d`, `qqoqccp`, `pareto`, `audit-interne`, `matrice-risques`.
- **Migration depuis l’ancienne colonne `origin` (enum supprimée)** : `audit` → `audit-interne` ; `five_why` → `cinq-pourquoi` ; `eight_d` → `8d` ; `risk` → `matrice-risques` ; `incident` / `other` → `audit-interne` + `_legacy_origin` dans `metadata`.
- **Préremplissage** : `GET /dashboard/qse/capa/prefill/{tool}/{kind}` (`kind` ∈ `corrective` \| `preventive` \| `maitrise`), paramètre query optionnel `entity` = id métier ; `App\Qse\Service\CapaDraftFromToolFactory` vérifie la possession de l’analyse (Ishikawa, 5 Pourquoi, AMDEC, 8D, QQOQCCP, Pareto) ou du risque (`risk`).

## Statuts stockés (clés sans accents)

- **CAPA** (`CapaStatus`) : `brouillon`, `a_valider`, `validee`, `en_cours`, `en_attente_de_verification`, `cloturee`, `reouverte`, `annulee`.
- **Audit exécution** (`AuditExecutionStatus`) : `brouillon`, `prepare`, `en_cours`, `termine`, `valide`, `archive`.
- **Plan d’audit** (`AuditPlanStatus`) : `brouillon`, `planifie`, `programme`, `en_cours`, `termine`, `archive`.
- **Risque** (`RiskEntryStatus`) : `identifie`, `en_analyse`, `maitrise`, `sous_surveillance`, `critique`, `cloture`.
- Affichage Twig : filtre `qse_status_label(valeur, 'capa'|'audit_exec'|'audit_plan'|'risk')`.
- **`RiskMatrixEntry.metadata`** : libre versionné ; ne pas y stocker de secrets.

Événement domaine : `App\Qse\Event\AuditEvaluationSavedEvent` — écouté par `App\EventSubscriber\Qse\QseAuditEvaluationEventSubscriber` (stub).
