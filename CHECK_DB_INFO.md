# 📋 Vérification des informations MySQL o2switch

## Informations actuelles configurées :
- **Hôte** : `localhost` (à changer selon le contexte)
- **Port** : `3306`
- **Base de données** : `iwob6566_outils-qualite`
- **Utilisateur** : `iwob6566_adminOQ`
- **Mot de passe** : `EuZ*ev+(7Q1Q`

## 📍 À vérifier dans votre cPanel o2switch :

### 1. Bases de données MySQL
Allez dans **"Bases de données MySQL"** et vérifiez :
- ✅ Le nom COMPLET de votre base de données (format: `votre_identifiant_cpanel_nomBase`)
- ✅ Le nom COMPLET de votre utilisateur MySQL (format: `votre_identifiant_cpanel_nomUser`)
- ✅ Le mot de passe de l'utilisateur

### 2. Informations de connexion
Cherchez dans cPanel une section **"Informations de connexion"** ou **"Connection Strings"** qui affiche :
- **Hostname** : Généralement `yellow.o2switch.net` (pour connexion distante)
  OU `localhost` (pour connexion depuis le serveur o2switch lui-même)
- **Port** : Généralement `3306`

### 3. MySQL distant
Vérifiez que votre IP (`93.22.132.232`) est bien ajoutée dans **"MySQL distant"**

## 🔧 Configuration selon le contexte :

### Pour connexion DISTANTE (depuis votre machine locale) :
```
DB_HOST=yellow.o2switch.net
DB_PORT=3306
DB_NAME=iwob6566_outils-qualite
DB_USER=iwob6566_adminOQ
```

### Pour connexion LOCALE (depuis le serveur o2switch) :
```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=iwob6566_outils-qualite
DB_USER=iwob6566_adminOQ
```

## ⚠️ Points importants :
1. Les noms de base/utilisateur ont souvent le préfixe de votre identifiant cPanel
2. Pour connexion distante : utilisez `yellow.o2switch.net` + autorisez votre IP
3. Pour connexion locale : utilisez `localhost` (plus rapide, pas besoin d'autorisation IP)
