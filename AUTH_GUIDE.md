# 🔐 Guide d'Authentification - Police Routière Guinée

## Comptes de Test Disponibles

Tous les comptes de test utilisent le mot de passe: **`Agent@123456`** ou **`Admin@123456`**, etc. selon le type.

### 1️⃣ ADMINISTRATEUR SYSTÈME
**Rôle:** ROLE_ADMIN
- **Email:** `admin@police-routiere.gn`
- **Mot de passe:** `Admin@123456`
- **Permissions:** Accès complet au système
- **Accès géographique:** National (pas de restriction)

**Fonctionnalités:**
- Gestion des utilisateurs et rôles
- Configuration du système
- Consultation des logs d'audit
- Accès à tous les modules

---

### 2️⃣ DIRECTION GÉNÉRALE
**Rôle:** ROLE_DIRECTION_GENERALE
- **Email:** `direction-generale@police-routiere.gn`
- **Mot de passe:** `DG@123456`
- **Permissions:** Supervision nationale
- **Accès géographique:** National

**Fonctionnalités:**
- Statistiques nationales
- Rapports consolidés
- Supervision de toutes les régions
- Accès aux données agrégées

---

### 3️⃣ DIRECTIONS RÉGIONALES

#### Direction Région Kinshasa
- **Email:** `direction-kin@police-routiere.gn`
- **Mot de passe:** `DR@123456`
- **Région:** Kinshasa
- **Permissions:** Gestion régionale

#### Direction Région Kasai
- **Email:** `direction-ka@police-routiere.gn`
- **Mot de passe:** `DR@123456`
- **Région:** Kasai
- **Permissions:** Gestion régionale

#### Direction Région Katanga
- **Email:** `direction-kat@police-routiere.gn`
- **Mot de passe:** `DR@123456`
- **Région:** Katanga
- **Permissions:** Gestion régionale

**Fonctionnalités par région:**
- Gestion des agents de la région
- Consultation des contrôles régionaux
- Statistiques régionales
- Rapports régionaux

---

### 4️⃣ CHEFS DE BRIGADE

Les brigades disponibles:
- **KIN-BR1** - Brigade Kinshasa 1 (Gombe)
  - Email: `chef-kin-br1@police-routiere.gn`
  
- **KIN-BR2** - Brigade Kinshasa 2 (Limete)
  - Email: `chef-kin-br2@police-routiere.gn`
  
- **KA-BR1** - Brigade Kasai 1 (Kananga)
  - Email: `chef-ka-br1@police-routiere.gn`
  
- **KAT-BR1** - Brigade Katanga 1 (Likasi)
  - Email: `chef-kat-br1@police-routiere.gn`

**Mot de passe pour tous:** `Chef@123456`

**Fonctionnalités:**
- Gestion de l'équipe d'agents (création, modification, suppression)
- Suivi des contrôles de la brigade
- Gestion des infractions
- Rapports de la brigade

---

### 5️⃣ AGENTS ROUTIERS

Chaque brigade a 3 agents de test:
- Email pattern: `agent-{brigade-code}-{number}@police-routiere.gn`
- Exemple: `agent-kin-br1-1@police-routiere.gn`
- **Mot de passe pour tous:** `Agent@123456`

**Fonctionnalités:**
- Enregistrement de nouveaux contrôles
- Consultation de ses propres contrôles
- Ajout d'infractions aux contrôles
- Génération de PV

---

## 🔒 Hiérarchie des Rôles

```
ROLE_ADMIN
  ├─ ROLE_DIRECTION_GENERALE
  │   ├─ ROLE_DIRECTION_REGIONALE
  │   │   ├─ ROLE_CHEF_BRIGADE
  │   │   │   └─ ROLE_AGENT
  │   │   └─ ROLE_AGENT
  │   └─ ROLE_AGENT
  └─ ...
```

## 🔐 Accès Basé sur les Régions

| Rôle | Région | Brigade | Accès |
|------|--------|---------|-------|
| ADMIN | Nationale | Nationale | Tous les données |
| DIRECTION_GENERALE | Nationale | Nationale | Toutes les données |
| DIRECTION_REGIONALE | Assignée | Toutes | Données région assignée |
| CHEF_BRIGADE | Via brigade | Assignée | Données brigade assignée |
| AGENT | Via brigade | Assignée | Données propres + lecture brigade |

## 🚀 Commandes Utiles

```bash
# Afficher les utilisateurs
php bin/console doctrine:query:sql "SELECT * FROM user"

# Réinitialiser les données
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction

# Changer le mot de passe d'un utilisateur
php bin/console security:hash-password
```

## 📋 Spécifications de Sécurité

### Authentification
- ✅ Login/Logout sécurisé
- ✅ Remember-me (7 jours)
- ✅ CSRF Protection
- ✅ Session Timeout
- ✅ Password Hashing (Bcrypt)

### Autorisation
- ✅ Role-Based Access Control (RBAC)
- ✅ Voter personnalisé pour les accès régionaux/brigade
- ✅ Access Control Lists (ACL)
- ✅ Vérification des permissions

### Validation des Mots de Passe
Les mots de passe doivent contenir:
- Minimum 8 caractères
- Au moins une majuscule
- Au moins une minuscule
- Au moins un chiffre
- Au moins un caractère spécial (@$!%*?&)

Exemple: `Admin@123456`

## 🧪 Test des Accès

### Scenario 1: Agent créant un contrôle
1. Connexion en tant qu'agent
2. Naviguer vers "Nouveau Contrôle"
3. Remplir le formulaire
4. Soumettre

### Scenario 2: Chef de Brigade validant
1. Connexion en tant que chef
2. Consulter les contrôles de la brigade
3. Approuver/Modifier selon besoin

### Scenario 3: Direction Régionale supervisant
1. Connexion en tant que direction régionale
2. Consulter les statistiques de la région
3. Générer un rapport

### Scenario 4: Admin managant les rôles
1. Connexion en tant qu'admin
2. Accéder à la gestion des utilisateurs
3. Créer/Modifier/Supprimer des comptes

---

**Dernière mise à jour:** 30 Décembre 2025
**Version:** 1.0
