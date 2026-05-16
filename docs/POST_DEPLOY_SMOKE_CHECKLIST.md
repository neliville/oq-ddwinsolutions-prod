# Checklist post-déploiement production (outils-qualite.com)

Ordre strict après `./deploy.sh` sur o2switch.

## Prérequis serveur

1. `./scripts/pre-deploy-backup-o2switch.sh`
2. `./deploy.sh 2>&1 | tee ~/backups/deploy-$(date +%Y%m%d-%H%M).log`
3. Mettre à jour `MAUTIC_PASSWORD` dans `.env` (mot de passe rotaté) puis `php bin/console cache:clear --env=prod --no-debug`

## Smoke tests (J+0)

| # | Parcours | Critère |
|---|----------|---------|
| 1 | `curl -I https://outils-qualite.com/healthz.html` | HTTP 200 |
| 2 | `tail -f var/log/prod.log` | Pas d'erreur 500 |
| 3 | Homepage (anonyme) | Console JS vide, motion OK |
| 4 | `/inscription` | Compte test + auto-login |
| 5 | Logout + `/login` | Reconnexion OK |
| 6 | Reset password | Email Brevo + reset OK |
| 7 | `/dashboard` | KPIs, widgets, personnalisation |
| 8 | `/dashboard/qse/pdca` | Charts Apex lazy |
| 9 | `/dashboard/qse/audit` | Board, créer, évaluer verdict NC |
| 10 | Audit duplicate / archive | POST OK |
| 11 | `/dashboard/qse/capa` | Board + lien audit |
| 12 | `/dashboard/qse/risk` | Board + criticité |
| 13 | Outils (Ishikawa, AMDEC, Pareto, 5 Why, 8D, QQOQCCP) | Chargement sans erreur |
| 14 | Hover-card / aide contextuelle | Panel visible |
| 15 | Responsive 375px / 768px | Pas de scroll horizontal cassé |

## Monitoring

- **J+1h** : `tail -n 200 var/log/prod.log`
- **J+6h** : Matomo / Mautic
- **J+24h** : `php bin/console messenger:stats`

## Rollback

```bash
./scripts/rollback-production.sh
```

Voir aussi `DEPLOY_O2SWITCH.md` et tag Git `pre-premium-release-YYYYMMDD`.
