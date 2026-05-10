# Rapport — Préférences utilisateur QHSE

## Ce qui existait déjà

- **Entité [`User`](src/Entity/User.php)** : email, mot de passe, rôles, plan, quotas d’export, dates d’activité — pas de champs « profil étendu ».
- **Profil** : [`ProfileController`](src/Controller/ProfileController.php) + [`ProfileFormType`](src/Form/ProfileFormType.php) — modification **email uniquement** ; [`ResetPasswordController`](src/Controller/ResetPasswordController.php) pour le mot de passe.
- **Dashboard** : [`DashboardController`](src/Controller/DashboardController.php) + [`CockpitMetricsRepository`](src/Repository/Qse/CockpitMetricsRepository.php) + template volumineux — aucune personnalisation utilisateur.
- **Exports** : `trackExport` dans [`public/js/main.js`](public/js/main.js) + `POST /analytics/track-export` — métadonnées libres côté client (jsPDF dans les outils).
- **Emails** : [`MailerService`](src/Service/MailerService.php) — pas de digest QHSE paramétrable avant ce chantier.
- **UX** : composants [`Tabs`](templates/components/Tabs.html.twig) (Stimulus) + Shadcn Twig ; bundle Live Component enregistré mais **sans** composants PHP Live repérés dans le périmètre outils/dashboard.

## Ce qui a été réutilisé

- **User** inchangé pour email / sécurité / quotas (pas de duplication de la logique email).
- **Lien « Modifier l’email »** vers `app_profile_index` depuis l’onglet Profil des préférences.
- **Onglet Sécurité** : liens vers la réinitialisation de mot de passe existante (`app_forgot_password_request`).
- **Navigation** : extension de la sidebar connectée et du menu profil (topbar).
- **Tracking / analytics** : non modifiés dans ce livrable.

## Ce qui a été ajouté

- **Entité `UserPreferences`** (One-to-One avec `User`, FK côté préférences, cascade) + migration Doctrine.
- **Enums** dans `App\UserPreferences\` pour listes fermées (taille d’entreprise, activité, référentiel ISO, priorité, pilotage, fréquence notifications).
- **`UserPreferencesRepository::getOrCreateForUser()`** pour création paresseuse et absence de doublons.
- **`PreferencesController`** + formulaires Symfony **par onglet** (soumissions distinguées par nom de formulaire).
- **Page `/preferences`** avec onglets Shadcn (Profil pro, QHSE, Notifications, Exports, Dashboard, Sécurité).
- **Intégration dashboard** : chargement des préférences, affichage du prénom si renseigné, **visibilité** des blocs cockpit / KPI selon JSON `dashboardVisibility` (défaut : tout visible).
- **`GET /api/user/export-branding`** : JSON pour le client (footer, noms d’affichage export) — réservé aux utilisateurs connectés.
- **Commande** `app:user:qhse-digest --dry-run` : liste les utilisateurs éligibles aux récapis sans envoyer d’email.
- **Méthode documentée** `MailerService::sendWeeklyQhseDigest()` : corps minimal / extension future.

## Quick wins

- Espace « Préférences » visible dans le menu (rétention, sentiment « espace QHSE personnel »).
- Champs export réutilisables côté JS via une seule API GET.
- Dashboard masquable par blocs sans refonte des requêtes métier.

## Améliorations futures possibles

- **Upload logo** export (validation MIME, stockage sécurisé, URL signée).
- **Réordonnancement** fin des cartes cockpit selon `qhsePriority` (au-delà du sous-texte d’accueil).
- **Filtres DQL** sur audits/risques « sécurité » si le modèle de données le permet explicitement.
- **Envoi réel** du digest hebdo (cron + templates + désinscription).
- **Live Components** pour une sauvegarde tab par tab sans rechargement complet (si ROI validé).

## Risques techniques et mitigation

| Risque | Mitigation |
|--------|------------|
| Régression dashboard | `dashboardVisibility` null = tout visible ; tests fonctionnels sur masquage |
| Fuite de données | Aucun `id` utilisateur dans l’URL des préférences ; API branding sans email |
| XSS dans pied PDF | Longueurs limitées ; échappement côté consommateur JS |
| Multi-formulaires | Un seul `handleRequest` par POST selon clé de formulaire |

---

*Livrable généré dans le cadre du plan « Préférences utilisateur QHSE ».*
