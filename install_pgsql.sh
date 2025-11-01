#!/bin/bash
# Script pour installer le pilote PostgreSQL pour PHP

echo "Installation du pilote PostgreSQL pour PHP..."
sudo apt update
sudo apt install -y php-pgsql php8.3-pgsql

echo "Vérification de l'installation..."
php -m | grep -i pgsql

echo "✅ Installation terminée ! Redémarrez votre serveur Symfony."
