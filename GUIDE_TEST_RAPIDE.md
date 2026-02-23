# 🚀 GUIDE DE TEST RAPIDE - POLICE ROUTIÈRE

**Pour tester rapidement si l'application fonctionne**

---

## 1️⃣ TESTS SANS BASE DE DONNÉES (Immédiat)

### ✅ Vérifier que Symfony répond

```bash
cd police-routiere-web

# Test 1: Voir les routes disponibles
php bin/console debug:router --format=text | head -30

# Test 2: Vérifier le conteneur
php bin/console debug:container --types | head -20

# Test 3: Vérifier les services
php bin/console debug:autowiring | head -20
```

**Résultat Attendu:** 77+ routes listées ✅

---

### ✅ Tester la Syntaxe PHP

```bash
# Vérifier tous les fichiers PHP
find src -name "*.php" -exec php -l {} \;

# Ou de manière plus simple:
php -l src/Kernel.php
php -l src/Controller/ControleController.php
php -l src/Entity/User.php
```
# lancer le server
symfony server:start --document-root=police-routiere-web\public


**Résultat Attendu:** "No syntax errors detected" pour chaque fichier ✅

---

## 2️⃣ TESTS AVEC DÉPLOIEMENT LOCAL (15 min)

### Prérequis

- PHP 8.2+
- MySQL 5.7+
- Composer
- Un terminal/PowerShell

### Étape 1: Configuration

```bash
cd police-routiere-web

# 1. Copier le .env
cp .env.example .env   # Si existe
# OU créer manuellement

# 2. Éditer .env avec vos credentials BD
DATABASE_URL="mysql://root:alpho224@127.0.0.1:3306/police_routiere"
APP_SECRET=votre_secret_aleatoire_32_characters

# 3. Installer les dépendances (si pas fait)
composer install
```

### Étape 2: Créer la Base de Données

```bash
# Créer la BD
php bin/console doctrine:database:create

# Exécuter les migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Charger des données de test (optionnel)
php bin/console doctrine:fixtures:load --no-interaction
```

**Résultat Attendu:** ✅ BD créée avec 14 tables

---

### Étape 3: Lancer le Serveur

```bash
# Méthode 1: Serveur intégré Symfony
php -S 127.0.0.1:8000 -t public

# Méthode 2: Built-in server (recommandé)
php bin/console server:run 127.0.0.1:8000
```

**Résultat Attendu:** 
```
[OK] Server running on http://127.0.0.1:8000/
```

---

## 3️⃣ TESTER L'APPLICATION DÉPLOYÉE

### ✅ Test 1: Accéder à l'Accueil

**URL:** http://127.0.0.1:8000/

**Résultat Attendu:** Page d'accueil avec bouton Login ✅

---

### ✅ Test 2: Authentification

**URL:** http://127.0.0.1:8000/login

**Tester avec des comptes:**

#### Admin
```
Email: admin@police-routiere.gn
Password: Admin@123456
```
**Après login:** Redirection vers `/admin` ✅

#### Direction Générale
```
Email: direction-generale@police-routiere.gn
Password: DG@123456
```
**Après login:** Redirection vers `/direction-generale/dashboard` ✅

#### Direction Régionale
```
Email: direction-cky@police-routiere.gn
Password: DR@123456
```
**Après login:** Redirection vers `/direction-regionale/dashboard` ✅

#### Chef de Brigade
```
Email: chef-cky-br1@police-routiere.gn
Password: Chef@123456
```
**Après login:** Redirection vers `/brigade/dashboard` ✅

#### Agent
```
Email: agent-cky-br1-1@police-routiere.gn
Password: Agent@123456
```
**Après login:** Redirection vers `/` (accueil) ✅

Notes:

- **Direction Régionale**: `direction-cky@police-routiere.gn`, `direction-knd@police-routiere.gn`, `direction-lab@police-routiere.gn`, `direction-frn@police-routiere.gn`, `direction-mzk@police-routiere.gn` (mot de passe: `DR@123456`)
- **Chef Brigade**: `chef-{codeBrigadeEnMinuscule}@police-routiere.gn` (mot de passe: `Chef@123456`)
- **Agent**: `agent-{codeBrigadeEnMinuscule}-{1|2|3}@police-routiere.gn` (mot de passe: `Agent@123456`)

---

### ✅ Test 3: Menu des Rôles (Après login)

#### Pour ADMIN
```
Menu visible:
  ✅ Gestion Utilisateurs (/admin/user)
  ✅ Gestion Régions (/admin/region)
  ✅ Gestion Brigades (/admin/brigade)
  ✅ Exports (/admin/export)
  ✅ Audit Logs (/admin/audit)
```

#### Pour DIRECTION_GENERALE
```
Menu visible:
  ✅ Dashboard (/direction-generale/dashboard)
  ✅ Contrôles (/direction-generale/controls)
  ✅ Infractions (/direction-generale/infractions)
  ✅ Amendes (/direction-generale/amendes)
  ✅ Rapports (/direction-generale/reports)
  ✅ Statistiques (/direction-generale/statistics)
```

#### Pour AGENT
```
Menu visible:
  ✅ Contrôles (/controle)
  ✅ Infractions (/infraction)
  ✅ Amendes (/amende)
  ✅ Mes Stats (/controle/stats)
```

---

## 4️⃣ TESTER LES FONCTIONNALITÉS PRINCIPALES

### 🧪 Test User Management (Admin)

```bash
# 1. Aller à /admin/user
http://127.0.0.1:8000/admin/user

# 2. Cliquer sur "Créer nouvel utilisateur"
# 3. Remplir le formulaire:
#    - Email: test@test.gu
#    - Nom: TestUser
#    - Rôle: ROLE_AGENT
#    - Région: Conakry
#    - Brigade: CKY-001
# 4. Soumettre
# Résultat Attendu: Message "Utilisateur créé" ✅
```

---

### 🧪 Test Création Contrôle (Agent)

```bash
# 1. Login comme Agent
# 2. Aller à /controle
# 3. Cliquer "Nouveau Contrôle"
# 4. Remplir:
#    - Date: Aujourd'hui
#    - Lieu: Pont du 8 Novembre
#    - Marque: Toyota
#    - Immatriculation: GN7788MM
#    - Conducteur: Jean Camara
#    - Observations: Test contrôle
# 5. Soumettre
# Résultat Attendu: Contrôle créé + redirect vers liste ✅
```

---

### 🧪 Test Validation Contrôle (Direction Générale)

```bash
# 1. Login comme DG
# 2. Aller à /direction-generale/controls
# 3. Cliquer sur un contrôle existant
# 4. Cliquer "Valider" (bouton vert)
# 5. Confirmer
# Résultat Attendu: Status = "VALIDE", validatedBy = DG user, dateValidation = now() ✅
```

---

### 🧪 Test Statistiques Personnelles (Agent)

```bash
# 1. Login comme Agent
# 2. Aller à /controle/stats
# Résultat Attendu: 6 KPI cards affichés:
#    - Nom: John Doe
#    - Email: john@police.gu
#    - Brigade: CKY-001
#    - Contrôles: 5
#    - Infractions: 3
#    - Amendes: 3
```

---

### 🧪 Test Export CSV (Admin)

```bash
# 1. Login comme Admin
# 2. Aller à /admin/export/users
# 3. Télécharger le fichier CSV
# Résultat Attendu: 
#    - Fichier utilisateurs_YYYY-MM-DD_HH-MM-SS.csv téléchargé ✅
#    - Délimiteur: ; (point-virgule)
#    - Encodage: UTF-8 BOM ✅
#    - Ouvrire dans Excel: OK sans caractères spéciaux ✅
```

---

### 🧪 Test Filtrage par Rôle

```bash
# 1. Login comme AGENT (Brigade X)
# 2. Aller à /controle
# Résultat Attendu: Voir SEULEMENT les contrôles de SA brigade ✅

# 2. Login comme DIRECTION_REGIONALE (Région Y)
# 2. Aller à /direction-regionale/controls
# Résultat Attendu: Voir SEULEMENT les contrôles de SA région ✅

# 3. Login comme ADMIN
# 2. Aller à /controle
# Résultat Attendu: Voir TOUS les contrôles ✅
```

---

## 5️⃣ TESTER LA SÉCURITÉ

### 🔒 Test 1: Accès Refusé (403)

```bash
# 1. Login comme AGENT
# 2. Essayer d'accéder à /admin/user
# Résultat Attendu: Page d'erreur 403 "Access Denied" ✅
```

### 🔒 Test 2: Protection CSRF

```bash
# 1. Créer un contrôle normalement
# 2. Éditer le formulaire HTML pour retirer le token CSRF
# 3. Soumettre
# Résultat Attendu: Erreur de validation CSRF ✅
```

### 🔒 Test 3: SQL Injection

```bash
# 1. Aller à /controle
# 2. Dans la barre de recherche, taper: " OR "1"="1
# Résultat Attendu: Recherche sécurisée (QueryBuilder paramétrisé) ✅
```

---

## 6️⃣ VÉRIFIER LES LOGS

### Audit Logs

```bash
# 1. Login comme Admin
# 2. Aller à /admin/audit
# Résultat Attendu: 
#    - Logs de login affichés
#    - Logs de création d'utilisateur
#    - IP Address visible
#    - User Agent visible
#    - Timestamps précis
```

### Vérifier les Logs Symfony

```bash
# En terminal
tail -f var/log/dev.log

# Résultat Attendu: Requêtes loggées avec détails
```

---

## 7️⃣ PERFOMANCE & QUALITY CHECKS

### ✅ Checker les Erreurs de Compilation

```bash
php bin/console lint:container
php bin/console lint:yaml config/

# Résultat Attendu: Pas d'erreurs
```

### ✅ Vérifier les Erreurs Doctrine

```bash
php bin/console doctrine:schema:validate

# Résultat Attendu: "The schema is in sync with the database."
```

### ✅ Vérifier les services Auto-wired

```bash
php bin/console debug:autowiring AuditService
php bin/console debug:autowiring StatisticsService

# Résultat Attendu: Services resolved correctly
```

---

## 8️⃣ CHECKLIST RAPIDE (5 MIN)

```
✅ Page d'accueil charge (/)
✅ Login page fonctionne (/login)
✅ Admin peut accéder /admin/user
✅ DG peut accéder /direction-generale/dashboard
✅ Agent peut accéder /controle
✅ Agent ne peut pas accéder /admin (403)
✅ Créer un contrôle fonctionne
✅ Stats page affiche les KPIs
✅ Export CSV fonctionne
✅ BDs audit logs renseignés
✅ Pas d'erreur Doctrine
✅ Pas d'erreur Symfony
```

---

## 🚨 EN CAS DE PROBLÈME

### Erreur: "CSRF token is invalid"

```bash
# Solution: Vérifier que {{ csrf_token('form_name') }} est dans le template
# ou que csrf_type est activé dans le form
```

### Erreur: "Table not found"

```bash
# Solution: 
php bin/console doctrine:migrations:migrate
```

### Erreur: "Connection refused" (BD)

```bash
# Solution: Vérifier DATABASE_URL dans .env
# php bin/console doctrine:database:create
```

### Erreur: "Class not found"

```bash
# Solution:
composer dump-autoload
```

### Erreur: 500 Internal Server

```bash
# Vérifier les logs:
tail -f var/log/dev.log

# Ou accéder au profiler Symfony:
http://127.0.0.1:8000/_profiler
```

---

## ✅ RÉSUMÉ FINAL

Si tous les tests ci-dessus passent:

- ✅ L'application est **entièrement fonctionnelle**
- ✅ La sécurité est **implémentée**
- ✅ Les rôles **fonctionnent correctement**
- ✅ Les données sont **auditées**
- ✅ Prêt pour **production**

---

**Document généré:** 8 février 2026  
**Durée tests:** ~30 minutes (complet) | ~5 minutes (rapide)  
**Status:** ✅ **TOUS LES TESTS DEVRAIT PASSER**
