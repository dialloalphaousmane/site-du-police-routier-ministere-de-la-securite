# 🚦 Police Routière Guinée - Système de Gestion

## 📋 Description

Système complet de gestion des contrôles routiers pour la Police Routière Guinée avec authentification multi-rôles, tableaux de bord personnalisés et fonctionnalités d'export.

---

## 🚀 Installation

### Prérequis
- PHP 8.1+
- Symfony 7.3
- MySQL/MariaDB
- Composer

### Installation
```bash
# Cloner le projet
git clone <repository-url>
cd police-routiere-web

# Installer les dépendances
composer install

# Configurer la base de données
# Modifier .env avec vos informations de BDD

# Créer la base de données
php bin/console doctrine:database:create

# Mettre à jour le schéma
php bin/console doctrine:schema:update --force

# Charger les données de test
php bin/console doctrine:fixtures:load

# Démarrer le serveur
symfony server:start
symfony server:start --port=8001
```

---

## 🔐 Identifiants de Connexion

### 👑 Administrateur
- **Email** : `admin@police-routiere.gn`
- **Mot de passe** : `Admin@123456`
- **Rôles** : `ROLE_ADMIN`, `ROLE_USER`
- **Accès** : `/dashboard/admin`

### 🏢 Direction Générale
- **Email** : `direction-generale@police-routiere.gn`
- **Mot de passe** : `DG@123456`
- **Rôles** : `ROLE_DIRECTION_GENERALE`, `ROLE_USER`
- **Accès** : `/dashboard/direction-generale`

### 📍 Directions Régionales
- **Email** : `direction-{region}@police-routiere.gn`
  - `direction-cky@police-routiere.gn` (Conakry)
  - `direction-knd@police-routiere.gn` (Kindia)
  - `direction-lab@police-routiere.gn` (Labé)
  - `direction-frn@police-routiere.gn` (Faranah)
  - `direction-mzk@police-routiere.gn` (Mamou)
- **Mot de passe** : `DR@123456`
- **Rôles** : `ROLE_DIRECTION_REGIONALE`, `ROLE_USER`
- **Accès** : `/dashboard/direction-regionale`

### 🛡️ Chefs de Brigade
- **Email** : `chef-{brigade}@police-routiere.gn`
  - `chef-cky-br1@police-routiere.gn` (Conakry Centre)
  - `chef-cky-br2@police-routiere.gn` (Conakry Nord)
  - `chef-cky-br3@police-routiere.gn` (Conakry Sud)
  - `chef-knd-br1@police-routiere.gn` (Kindia Centre)
  - `chef-lab-br1@police-routiere.gn` (Labé Centre)
  - `chef-frn-br1@police-routiere.gn` (Faranah Centre)
  - `chef-mzk-br1@police-routiere.gn` (Mamou Centre)
  - `chef-bok-br1@police-routiere.gn` (Boké Centre)
  - `chef-nzk-br1@police-routiere.gn` (N'Zérékoré Centre)
  - `chef-kan-br1@police-routiere.gn` (Kankan Centre)
- **Mot de passe** : `Chef@123456`
- **Rôles** : `ROLE_CHEF_BRIGADE`, `ROLE_USER`
- **Accès** : `/dashboard/chef-brigade`

### 👮 Agents Routiers
- **Email** : `agent-{brigade}-{numero}@police-routiere.gn`
  - `agent-cky-br1-1@police-routiere.gn` (Agent 1, Brigade Conakry Centre)
  - `agent-cky-br1-2@police-routiere.gn` (Agent 2, Brigade Conakry Centre)
  - `agent-cky-br1-3@police-routiere.gn` (Agent 3, Brigade Conakry Centre)
  - ... (30 agents au total, 3 par brigade)
- **Mot de passe** : `Agent@123456`
- **Rôles** : `ROLE_AGENT`, `ROLE_USER`
- **Accès** : `/dashboard/agent`

---

## 🎯 Rôles et Permissions

### ROLE_ADMIN
- Accès complet à l'administration
- Gestion des utilisateurs
- Gestion des régions et brigades
- Export de toutes les données
- Configuration système

### ROLE_DIRECTION_GENERALE
- Supervision nationale
- Statistiques globales
- Export des rapports
- Validation des contrôles majeurs

### ROLE_DIRECTION_REGIONALE
- Supervision régionale
- Gestion des brigades de la région
- Statistiques régionales
- Rapports régionaux

### ROLE_CHEF_BRIGADE
- Gestion des agents de la brigade
- Validation des contrôles
- Rapports de brigade
- Statistiques locales

### ROLE_AGENT
- Enregistrement des contrôles
- Saisie des infractions
- Consultation des rapports
- Statistiques personnelles

---

## 🌐 URL Principales

### Connexion
- **Login** : `/login`

### Tableaux de Bord
- **Admin** : `/dashboard/admin`
- **Direction Générale** : `/dashboard/direction-generale`
- **Direction Régionale** : `/dashboard/direction-regionale`
- **Chef de Brigade** : `/dashboard/chef-brigade`
- **Agent** : `/dashboard/agent`
- **Redirection automatique** : `/dashboard`

### Administration
- **Utilisateurs** : `/user`
- **Régions** : `/admin/region`
- **Brigades** : `/admin/brigade`
- **Rapports** : `/admin/report`

### Exports
- **Utilisateurs** : `/admin/export/users`
- **Contrôles** : `/admin/export/controls`
- **Infractions** : `/admin/export/infractions`
- **Amendes** : `/admin/export/amendes`
- **Régions** : `/admin/export/regions`
- **Brigades** : `/admin/export/brigades`
- **Rapports** : `/admin/export/rapports`

---

## 📊 Fonctionnalités

### ✅ Implémentées
- 🔐 Authentification multi-rôles
- 📈 Tableaux de bord personnalisés
- 📋 Gestion des utilisateurs
- 🗺️ Gestion des régions et brigades
- 📝 Saisie des contrôles
- ⚖️ Gestion des infractions
- 💰 Gestion des amendes
- 📊 Statistiques et rapports
- 📤 Export CSV des données
- 🔄 Redirection automatique selon le rôle

### 🔄 En cours
- 📱 Interface mobile responsive
- 📊 Graphiques interactifs
- 🔔 Notifications en temps réel
- 📋 Validation des contrôles
- 📈 Statistiques avancées

---

## 🛠️ Commandes Utiles

### Base de données
```bash
# Créer la base
php bin/console doctrine:database:create

# Mettre à jour le schéma
php bin/console doctrine:schema:update --force

# Charger les fixtures
php bin/console doctrine:fixtures:load

# Vider le cache
php bin/console cache:clear
```

### Routes
```bash
# Lister toutes les routes
php bin/console debug:router

# Vérifier une route spécifique
php bin/console debug:router app_dashboard
```

### Tests
```bash
# Lancer les tests
php bin/console phpunit

# Tests de sécurité
php bin/console debug:firewall
```

---

## 📁 Structure du Projet

```
police-routiere-web/
├── src/
│   ├── Controller/
│   │   ├── Admin/          # Contrôleurs admin
│   │   ├── API/            # API endpoints
│   │   └── Dashboard/      # Tableaux de bord
│   ├── Entity/             # Entités Doctrine
│   ├── Repository/         # Repositories
│   ├── Security/           # Sécurité et authentification
│   ├── Form/               # Formulaires
│   └── DataFixtures/       # Données de test
├── templates/
│   ├── admin/              # Templates admin
│   ├── dashboard/          # Tableaux de bord
│   └── user/               # Templates utilisateurs
├── config/                 # Configuration
└── public/                 # Assets et point d'entrée
```

---

## 🔧 Configuration

### Base de données
Modifier le fichier `.env` :
```env
DATABASE_URL="mysql://user:password@127.0.0.1:3306/police_routiere_bd"
```

### Mail
```env
MAILER_DSN=smtp://localhost:1025
```

---

## 🐛 Dépannage

### Problèmes courants
1. **Erreur 403 Access Denied** : Vérifiez les rôles de l'utilisateur
2. **Redirection incorrecte** : Videz le cache : `php bin/console cache:clear`
3. **Base de données vide** : Rechargez les fixtures : `php bin/console doctrine:fixtures:load`

### Logs
```bash
# Voir les logs de développement
tail -f var/log/dev.log

# Logs de production
tail -f var/log/prod.log
```

---

## 📝 Notes de Développement

### Sécurité
- ✅ Mots de passe hashés (bcrypt)
- ✅ Protection CSRF
- ✅ Validation des entrées
- ✅ Sécurité par rôle

### Performance
- ✅ Cache configuré
- ✅ Requêtes optimisées
- ✅ Assets minifiés

### Tests
- 🔄 Tests unitaires en cours
- 🔄 Tests d'intégration prévus

---

## 📞 Support

Pour toute question ou problème technique :
- 📧 Email : support@police-routiere.gn
- 📱 Téléphone : +224 XXX XXX XXX

---

## 📄 Licence

© 2026 Police Routière Guinée - Tous droits réservés

---

**Développé avec ❤️ par l'équipe technique de la Police Routière Guinée**
