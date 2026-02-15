# 🎯 Refonte Architecture - Machine à Leads

## 📋 Vue d'ensemble

Refonte complète du projet pour transformer **outils-qualite.com** en une **machine à leads performante** tout en restant **B2C/solo friendly** et **SaaS-ready** (sans être SaaS).

---

## 🏗️ Architecture mise en place

### Structure Application/Domain/Infrastructure

```
src/
├── Application/          # Use cases et services applicatifs
│   ├── Lead/            # Gestion des leads
│   ├── Notification/     # Notifications (emails, webhooks)
│   └── Analytics/        # Tracking et analytics
├── Domain/              # Entités métier (DDD)
│   ├── Lead/            # Lead domain model
│   └── Analytics/       # Events métier (ToolUsed, LeadConverted)
├── Infrastructure/      # Implémentations techniques
│   ├── Mail/            # Messages Messenger
│   ├── Persistence/     # Doctrine (via Entity/)
│   └── Tracking/        # Implémentation tracking
└── Controller/
    ├── Public/          # Contrôleurs publics (leads, newsletter)
    ├── Tool/            # Contrôleurs outils (ishikawa, fivewhy...)
    └── Admin/           # Administration
```

---

## ✅ Implémentations réalisées (P0)

### 1. Système de Leads

#### Entités créées
- **`App\Entity\Lead`** : Entité Doctrine pour persister les leads
- **`App\Domain\Lead\Lead`** : Modèle métier (DDD)

#### Services créés
- **`App\Application\Lead\LeadService`** :
  - Calcul du score (0-100) basé sur :
    - Email fourni : +20
    - Nom fourni : +10
    - Outil utilisé : +30
    - Source (newsletter: +15, contact: +25, demo-request: +40)
    - UTM campaign : +10
    - Consentement RGPD : +5
  - Détermination du type (B2B/B2C) selon le domaine email
  - Persistance en base de données

- **`App\Application\Lead\CreateLead`** : Use case pour créer un lead

#### Contrôleur
- **`App\Controller\Public\LeadController`** :
  - `POST /api/lead` : Création de lead depuis formulaires/outils
  - Support UTM parameters
  - Création automatique de leads lors de l'utilisation d'outils

---

### 2. Notification Service

#### Messages Messenger
- **`App\Infrastructure\Mail\LeadCreatedMessage`** : Message async pour notifier la création d'un lead
- **`App\Infrastructure\Mail\LeadCreatedMessageHandler`** : Handler pour traiter les notifications

#### Service
- **`App\Application\Notification\NotificationService`** :
  - `notifyLeadCreated()` : Notifie la création d'un lead (async)
  - `sendUserConfirmationEmail()` : Email de confirmation utilisateur
  - `notifyAdminNewLead()` : Notification admin pour leads qualifiés

#### Configuration Messenger
- Transport `async` configuré avec retry strategy
- Routing automatique des `LeadCreatedMessage` vers async

---

### 3. Tracking Service

#### Events métier
- **`App\Domain\Analytics\ToolUsedEvent`** : Event lorsqu'un outil est utilisé
- **`App\Domain\Analytics\LeadConvertedEvent`** : Event lors de la conversion d'un lead

#### Service
- **`App\Application\Analytics\TrackingService`** :
  - `trackToolUsed()` : Enregistre l'utilisation d'un outil
  - `trackLeadConverted()` : Enregistre la conversion
  - `trackPageView()` : Enregistre les pages vues

---

### 4. Utilisation sans compte

#### Contrôleurs outils refactorisés
- **`App\Controller\Tool\IshikawaController`** :
  - `/api/ishikawa/save` : Accessible sans compte
  - Si invité : retourne données pour localStorage
  - Si connecté : sauvegarde en DB
  - Création automatique de lead

- **`App\Controller\Tool\FiveWhyController`** :
  - Même logique que Ishikawa
  - Support invité + utilisateur connecté

#### Sécurité
- Routes `/api/ishikawa/save`, `/api/fivewhy/save` accessibles publiquement
- Routes `/api/ishikawa/list`, `/api/ishikawa/{id}` nécessitent authentification
- Création automatique de leads lors de l'utilisation d'outils

---

## 📊 Base de données

### Migration créée
- **`migrations/Version20260115142959.php`** :
  - Table `lead` avec tous les champs nécessaires
  - Index sur `email`, `created_at`, `source`, `tool`, `type`
  - Relation ManyToOne avec `user` (nullable)

---

## 🔄 Workflow de conversion

### Utilisation d'un outil (invité)
1. Utilisateur utilise l'outil (ex: Ishikawa)
2. Sauvegarde → données retournées pour localStorage
3. **Lead créé automatiquement** avec :
   - Source : `tool`
   - Tool : `ishikawa`
   - Session ID
   - IP, User-Agent
   - UTM parameters si présents
4. **Notification async** envoyée
5. **Tracking** de l'utilisation

### Utilisation d'un outil (connecté)
1. Utilisateur connecté utilise l'outil
2. Sauvegarde en base de données
3. **Lead créé** (si première utilisation de l'outil)
4. **Notification async** envoyée
5. **Tracking** avec userId

---

## 🚀 Prochaines étapes (P1)

### À implémenter
1. **Pages SEO dédiées** : `/outil/ishikawa`, `/outil/5-pourquoi`, etc.
2. **Hero amélioré** : Problème → Solution avec CTA visibles
3. **Dashboard admin** : Visualisation des leads et analytics
4. **Capture email après valeur** : Modal après utilisation d'outil
5. **FAQ dynamique** : SEO + Schema.org
6. **Preuve sociale** : Témoignages, statistiques

---

## 📝 Notes techniques

### Messenger
- Transport async configuré (nécessite `MESSENGER_TRANSPORT_DSN` dans `.env`)
- Par défaut : `sync://` (synchronisé) si DSN non configuré
- Pour activer async : `MESSENGER_TRANSPORT_DSN=doctrine://default`

### LocalStorage (côté client)
- Les invités peuvent utiliser les outils
- Données sauvegardées en localStorage
- Message d'encouragement à créer un compte pour sauvegarder définitivement

### Scoring des leads
- Score calculé automatiquement (0-100)
- Type déterminé selon le domaine email
- Leads qualifiés (score > 50) notifiés à l'admin

---

## 🔐 Sécurité

- CSRF activé sur tous les formulaires
- Rate limiting à prévoir (à implémenter)
- Consentement RGPD géré
- Logs des actions sensibles (via AdminLog existant)

---

## 📦 Dépendances ajoutées

- `symfony/messenger` : Pour les notifications async

---

## ✅ Critères d'acceptation

- [x] Utilisation d'un outil sans compte possible
- [x] Lead créé automatiquement après usage
- [x] Email de confirmation envoyé (via Messenger)
- [x] Export PDF fonctionnel (déjà existant)
- [ ] Dashboard admin simple (à créer)
- [x] Code prêt à dockeriser sans refonte

---

## 🎯 Objectifs atteints

✅ **Machine à leads** : Création automatique de leads  
✅ **B2C & solo friendly** : Utilisation sans compte  
✅ **Architecture propre** : Application/Domain/Infrastructure  
✅ **SaaS-ready** : Scalable mais non SaaS  
✅ **Simple & évolutif** : Code clair et modulaire

