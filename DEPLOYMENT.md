# Guide de Déploiement - Police Routière

## 📦 Déploiement avec Docker (Recommandé)

### Prérequis
- Docker Engine 20+
- Docker Compose 2+
- 2GB RAM minimum
- 500MB disque disponible

### Étape 1: Préparer l'environnement

```bash
# Cloner le projet
git clone <repository> police-routiere-web
cd police-routiere-web

# Copier les fichiers d'environnement
cp .env.example .env

# Générer une clé secrète sécurisée
php -r "echo bin2hex(random_bytes(32));"
# Remplacer APP_SECRET dans .env
```

### Étape 2: Lancer les services

```bash
# Construire les images
docker-compose build

# Démarrer les services
docker-compose up -d

# Vérifier le statut
docker-compose ps
```

### Étape 3: Initialiser la base de données

```bash
# Accéder au container PHP
docker-compose exec php bash

# À l'intérieur du container :
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load --no-interaction

# Vérifier la santé
php bin/console debug:router | head -20
```

### Étape 4: Configuration final

```bash
# Fixer les permissions
docker-compose exec php chown -R www-data:www-data /var/www/html/var

# Nettoyer le cache
docker-compose exec php php bin/console cache:clear --env=prod
docker-compose exec php php bin/console cache:warm

# Compiler les assets
docker-compose exec php php bin/console asset-map:compile
```

### Accès aux services

| Service | URL | Credentials |
|---------|-----|-------------|
| Application | http://localhost | admin@police.gn / Admin@123456 |
| PHPMyAdmin | http://localhost:8081 | root / alpho224 |
| Redis | localhost:6379 | - |

---

## 🚀 Déploiement sur Linux (Production)

### Prérequis
- Ubuntu 20.04+ ou AlmaLinux 8+
- PHP 8.2+
- MySQL 8.0+
- Nginx
- Certbot (SSL)

### Installation système

```bash
# 1. Mise à jour
sudo apt update && sudo apt upgrade -y

# 2. Installer PHP 8.2
sudo apt install php8.2-{cli,fpm,mysql,zip,gd,curl,intl,xml,mbstring} -y

# 3. Installer MySQL
sudo apt install mysql-server -y
sudo mysql_secure_installation

# 4. Installer Nginx
sudo apt install nginx -y

# 5. Installer Composer
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
```

### Configuration du projet

```bash
# 1. Créer le répertoire
sudo mkdir -p /var/www/police-routiere
cd /var/www/police-routiere

# 2. Cloner le projet (avec droits sudo ou git ssh)
sudo git clone <repository> .

# 3. Installer les dépendances
sudo composer install --no-dev --optimize-autoloader

# 4. Configurer l'environnement
sudo cp .env.example .env
sudo nano .env  # Éditer DATABASE_URL et APP_SECRET

# 5. Permissions
sudo chown -R www-data:www-data .
sudo chmod -R 775 var/cache var/logs var/uploads
```

### Configuration Nginx

```bash
# Copier la configuration
sudo cp docker/nginx/conf.d/default.conf /etc/nginx/sites-available/police-routiere
sudo ln -s /etc/nginx/sites-available/police-routiere /etc/nginx/sites-enabled/

# Éditer et adapter le domaine
sudo nano /etc/nginx/sites-available/police-routiere

# Valider et redémarrer
sudo nginx -t
sudo systemctl restart nginx
```

### Configuration SSL (Let's Encrypt)

```bash
# Installer Certbot
sudo apt install certbot python3-certbot-nginx -y

# Générer le certificat
sudo certbot --nginx -d police-routiere.gn -d www.police-routiere.gn

# Configuration automatique du renouvellement
sudo systemctl enable certbot.timer
sudo systemctl start certbot.timer
```

### Configuration PHP-FPM

```bash
# Éditer la configuration
sudo nano /etc/php/8.2/fpm/pool.d/www.conf

# Paramètres importants:
# user = www-data
# group = www-data
# listen = /run/php/php8.2-fpm.sock
# pm = dynamic
# pm.max_children = 50
# pm.start_servers = 10
# pm.min_spare_servers = 5
# pm.max_spare_servers = 20

# Redémarrer
sudo systemctl restart php8.2-fpm
```

### Initialiser la base de données

```bash
# Créer la BD
mysql -u root -p -e "CREATE DATABASE police_routiere_BD CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE USER 'police_user'@'localhost' IDENTIFIED BY 'secure_password';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON police_routiere_BD.* TO 'police_user'@'localhost';"
mysql -u root -p -e "FLUSH PRIVILEGES;"

# Mettre à jour DATABASE_URL dans .env
DATABASE_URL="mysql://police_user:secure_password@127.0.0.1:3306/police_routiere_BD"

# Migrer
cd /var/www/police-routiere
sudo -u www-data php bin/console doctrine:migrations:migrate --no-interaction
sudo -u www-data php bin/console doctrine:fixtures:load --no-interaction
```

### Configuration des services systémiques

```bash
# Cron pour tâches planifiées
sudo crontab -e -u www-data

# Ajouter les tâches:
0 2 * * * /var/www/police-routiere/bin/console app:daily-report
0 * * * * /var/www/police-routiere/bin/console app:stats-cache
```

---

## 🔒 Sécurité en Production

### 1. Hardening Nginx

```nginx
# Dans la configuration server {}
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "no-referrer-when-downgrade" always;
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';" always;
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
```

### 2. Firewall

```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# Rate limiting
sudo apt install fail2ban -y
```

### 3. Certificat SSL Auto-renew

```bash
# Vérifier
sudo certbot renew --dry-run

# Historique
sudo certbot certificates
```

### 4. Audit et Logs

```bash
# Logs nginx
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log

# Logs application
tail -f /var/www/police-routiere/var/log/prod.log

# Logs MySQL
tail -f /var/log/mysql/error.log
```

### 5. Backups automatiques

```bash
#!/bin/bash
# /usr/local/bin/backup-police-routiere.sh
BACKUP_DIR="/backups/police-routiere"
DATE=$(date +%Y%m%d_%H%M%S)

mysqldump -u police_user -p"secure_password" police_routiere_BD > $BACKUP_DIR/db_$DATE.sql
tar -czf $BACKUP_DIR/app_$DATE.tar.gz /var/www/police-routiere

# Cron job
0 3 * * * /usr/local/bin/backup-police-routiere.sh
```

---

## 📊 Monitoring

### Installation Prometheus + Grafana (Optionnel)

```bash
# Services de monitoring
docker run -d --name prometheus prom/prometheus
docker run -d --name grafana grafana/grafana

# Ajouter des dashboards pour Symfony
# Documentation: https://grafana.com/
```

---

## ✅ Checklist de déploiement

- [ ] DATABASE_URL configurée correctement
- [ ] APP_SECRET changée de la valeur par défaut
- [ ] SSL certificate installé
- [ ] Permissions fichiers correctes (var/cache, var/logs)
- [ ] Cache Laravel/Symfony compilé
- [ ] Logs configurés et analysés
- [ ] Backups en place
- [ ] Firewall activé
- [ ] Monitoring installé
- [ ] Users de test créés
- [ ] Documentation métier partagée
- [ ] Contact admin répertorié

---

## 🆘 Troubleshooting

### La BD ne démarre pas
```bash
docker logs police_routiere_db
docker-compose down -v  # Réinitialiser complètement
```

### Erreur 500 persistent
```bash
php bin/console cache:clear --env=prod
php bin/console cache:warm
```

### Permissions refusées
```bash
sudo chown -R www-data:www-data /var/www/police-routiere
sudo chmod -R 755 var
```

---

**Dernière mise à jour:** 2024
**Support:** admin@police-routiere.gn
