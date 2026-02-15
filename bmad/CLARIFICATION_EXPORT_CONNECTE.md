# Clarification BMAD : Téléchargement des créations réservé aux utilisateurs connectés

## B – Valeur business

- **Objectif :** Les utilisateurs non connectés ne peuvent plus télécharger (exporter) les créations ; ils doivent se connecter (ou s’inscrire) pour accéder à l’export (PDF, PNG, JPEG, JSON).
- **Valeur :** Augmentation des inscriptions et des connexions, meilleure captation de leads, cohérence avec un modèle où la valeur (export) est liée au compte. Pas de monétisation immédiate, mais renforcement du parcours « essai → inscription → usage ».

## M – Modèle métier

- **Avant :** Tout visiteur (y compris anonyme) pouvait utiliser les outils et exporter/télécharger les créations (PDF, etc.) sans compte.
- **Après :** L’**export / téléchargement** des créations est réservé aux **utilisateurs authentifiés**. L’usage des outils (consultation, saisie) peut rester possible en anonyme ; seul l’acte de téléchargement exige une connexion.
- **Règle métier :** « Télécharger une création ⇒ utilisateur connecté. » Aucune nouvelle entité ; utilisation du `User` existant et du pare-feu de sécurité.

## A – Impact architecture

- **Sécurité :** Exiger `ROLE_USER` sur l’endpoint `POST /analytics/track-export` (suivi des exports). Les appels anonymes renvoient une redirection vers la page de connexion.
- **Templates :** Sur les pages d’outils (Ishikawa, 5 Pourquoi, AMDEC, Pareto, QQOQCCP, Méthode 8D), afficher le bloc « Exporter » uniquement si `app.user` est défini ; sinon afficher un message du type « Connectez-vous pour télécharger vos créations » avec lien vers la connexion (et l’inscription).
- **Pas de nouveau service ni nouveau flux.** Les exports restent générés côté client (JS) ; seul l’accès à la fonctionnalité et au tracking est conditionné par l’authentification.

## D – Faisabilité delivery

- **Charge :** Faible. Modification de `security.yaml` (une règle d’accès) et adaptation de 6 à 7 templates (condition `app.user`, message + lien de connexion).
- **Risque :** Faible. Les tests existants qui appellent `track-export` en anonyme devront être adaptés (connexion utilisateur ou attente 302).
- **Dépendances :** Aucune. Pas de migration de données.
