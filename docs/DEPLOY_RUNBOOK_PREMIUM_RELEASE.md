# Runbook — Release premium (main → o2switch)

**État Git** : `main` poussé sur `origin` (commits premium + assets recompilés localement).

**Tags de rollback** : `pre-premium-release-20260516`, `pre-premium-release-base`  
**Bundle hors-repo** : `../oq-prod-backup-20260516.bundle`

## 1. Vérifier la CI GitHub (avant SSH)

Sur https://github.com/neliville/oq-ddwinsolutions-prod/actions :

- `CI - Tests et validation` → vert
- `Précompiler public/assets et commit` → peut ajouter un commit `[skip ci]` ; si oui : `git pull origin main` sur le serveur après deploy

## 2. Sur o2switch (SSH)

```bash
cd ~/public_html   # adapter au chemin réel
git fetch origin main
git log -1 --oneline origin/main   # doit afficher docs(deploy) ou fix(admin) récent

./scripts/pre-deploy-backup-o2switch.sh
./deploy.sh 2>&1 | tee ~/backups/deploy-$(date +%Y%m%d-%H%M).log
```

## 3. Après deploy

1. **Rotater Mautic** (admin Mautic) → mettre le nouveau mot de passe dans `.env` :
   ```bash
   nano .env   # MAUTIC_PASSWORD=...
   php bin/console cache:clear --env=prod --no-debug
   ```
2. Suivre [POST_DEPLOY_SMOKE_CHECKLIST.md](POST_DEPLOY_SMOKE_CHECKLIST.md)

## 4. Rollback si KO

```bash
./scripts/rollback-production.sh
```

## 5. Monitoring J+0 → J+24h

Voir section Monitoring dans `POST_DEPLOY_SMOKE_CHECKLIST.md`.
