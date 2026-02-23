# Police Routière - Système de Gestion

Système de gestion pour la Police Routière du Ministère de la Sécurité de Guinée.

## 📋 Prérequis

- **PHP**: 8.2+
- **MySQL**: 8.0+
- **Composer**: 2.0+
- **Node.js**: 18+ (optionnel, pour assets)

## 🚀 Installation rapide

### 1. Cloner et configurer

```bash
cd police-routiere-web
composer install
cp .env.example .env
```

### 2. Configurer la base de données

Éditer `.env`:
```env
DATABASE_URL="mysql://root:alpho224@127.0.0.1:3306/police-routiere_BD"
APP_SECRET="votre_clé_secrète_ici"
```

### 3. Créer la base de données et migrer

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

### 4. Démarrer le serveur

```bash
symfony server:start
# ou
php -S localhost:8000 -t public/
```

Le système est accessible sur: `http://localhost:8000`

## 👥 Comptes de test

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Admin | admin@police.gn | Admin@123456 |
| Direction Générale | dg@police.gn | DG@123456 |
| Direction Régionale | dr@police.gn | DR@123456 |
| Chef de Brigade | chef@police.gn | Chef@123456 |
| Agent | agent@police.gn | Agent@123456 |

## 📁 Structure du projet

```
police-routiere-web/
├── bin/               # Fichiers exécutables (console Symfony)
├── config/            # Configuration (bundles, services, routes)
├── migrations/        # Migrations Doctrine
├── public/            # Point d'entrée (index.php)
├── src/
│   ├── Command/       # Commandes CLI
│   ├── Controller/    # Contrôleurs (Admin, DG, DR, Brigade)
│   ├── Entity/        # Entités Doctrine
│   ├── Form/          # Form types
│   ├── Repository/    # Repositories Doctrine
│   ├── Security/      # Authentification & autorisation
│   ├── Service/       # Services (Statistics, Audit, Report, Export, etc)
│   ├── Util/          # Constantes et utilitaires
│   └── Kernel.php     # Kernel Symfony
├── templates/         # Templates Twig
├── tests/             # Tests PHPUnit
├── assets/            # Assets (JS, CSS)
├── translations/      # Fichiers de traduction
├── composer.json      # Dépendances PHP
└── .env              # Configuration d'environnement
```

## 🎯 Principales fonctionnalités

### 🔐 Authentification & Autorisation
- Système de rôles hiérarchiques (5 niveaux)
- Protection CSRF sur tous les formulaires
- Gestion des sessions

### 📊 Tableau de bord
- Statistiques en temps réel
- Graphiques de tendances
- Rapports mensuels/régionaux

### 🚔 Gestion des contrôles
- Enregistrement des contrôles routiers
- Historique complet
- Ligues aux infractions et amendes

### 📋 Gestion des infractions
- Catalogue de codes d'infraction
- Classification par catégorie
- Montants standards

### 💰 Gestion des amendes
- Création automatique/manuelle
- Suivi des paiements
- Statistiques de recouvrement

### 📈 Rapports et export
- Rapports mensuels
- Statistiques régionales
- Export CSV/Excel/PDF
- Audit logging

### 🛡️ Administration
- Gestion des utilisateurs
- Gestion des régions et brigades
- Audit trail complet
- Gestion des rôles

## 🔧 Commandes principales

```bash
# Afficher les routes enregistrées
php bin/console debug:router

# Voir les services disponibles
php bin/console debug:container

# Créer les utilisateurs de test
php bin/console app:create-test-users

# Corriger les rôles (si besoin)
php bin/console app:fix-admin-role
php bin/console app:fix-all-roles

# Tester l'authentification
php bin/phpunit bin/test-auth.php

# Exécuter les tests
php bin/phpunit

# Linter
php bin/console lint:twig templates/
php bin/console lint:yaml config/
```

## 📝 API REST

L'API REST est documentée dans `API_DOCUMENTATION.md`

**Endpoints principaux:**
- `/api/v1/controls` - Contrôles
- `/api/v1/infractions` - Infractions
- `/api/v1/amendes` - Amendes
- `/api/v1/statistics` - Statistiques
- `/api/v1/reports` - Rapports

## ✅ Tests

```bash
# Lancer tous les tests
php bin/phpunit

# Tests d'une classe spécifique
php bin/phpunit tests/Controller/AdminControllerTest.php

# Avec rapportage de couverture
php bin/phpunit --coverage-html=coverage/
```

## 🐳 Docker (Optionnel)

```bash
# Construire l'image
docker-compose build

# Démarrer les services
docker-compose up -d

# Migrer la BD
docker-compose exec php php bin/console doctrine:migrations:migrate

# Arrêter
docker-compose down
```

## 📚 Documentation supplémentaire

- `AUTH_GUIDE.md` - Guide d'authentification détaillé
- `IMPLEMENTATION_STATUS.md` - État d'implémentation
- `admin.md` - Guide administrateur
- `API_DOCUMENTATION.md` - Documentation API complète

## 🤝 Support

Pour les problèmes ou suggestions:
1. Vérifier le fichier de log: `var/log/dev.log`
2. Consulter la documentation spécifique
3. Vérifier les erreurs dans le navigateur (F12)

## 📄 Licence

Projet pour le Ministère de la Sécurité de Guinée

## 🔄 Mise à jour

```bash
# Mettre à jour les dépendances
composer update

# Exécuter les nouvelles migrations
php bin/console doctrine:migrations:migrate
```

---

**Version:** 1.0.0  
**Dernière mise à jour:** 2024
**Framework:** Symfony 7.3
