# 🔐 Rôles et Permissions - Structure Complète

**Document de référence pour toutes les différenciations par rôle dans Police Routière**

---
connexion a msql workbench: police_pass_2026

## 📋 Vue d'ensemble

Cinq rôles hiérarchiques sont implémentés avec permissions en cascade :

```
ROLE_ADMIN (Tous les droits)
    ├── ROLE_DIRECTION_GENERALE (Supervision nationale)
    │   ├── ROLE_DIRECTION_REGIONALE (Supervision régionale)
    │   │   ├── ROLE_CHEF_BRIGADE (Gestion brigade locale)
    │   │   │   └── ROLE_AGENT (Opérations terrain)
```

---

## 🔒 1. ROLE_ADMIN

**Localisation:** `config/packages/security.yaml`, `src/Controller/Admin/*`

### Permissions Complètes
- ✅ Gestion complète des utilisateurs → `UserController` (#[IsGranted('ROLE_ADMIN')])
- ✅ Gestion des régions → `Admin/AdminRegionController` (CRUD complet)
- ✅ Gestion des brigades → `Admin/AdminBrigadeController` (CRUD complet)
- ✅ Export de TOUTES les données → `Admin/AdminExportController`
  - Utilisateurs (CSV)
  - Contrôles (CSV)
  - Infractions (CSV)
  - Amendes (CSV)
  - Régions (CSV)
  - Brigades (CSV)
  - Rapports (CSV)
- ✅ Audit logging complet → `Admin/AdminAuditController`
- ✅ Vue d'ensemble de la configuration système

### Routes
```
/admin/user/*              → UserController (ROLE_ADMIN)
/admin/region/*            → AdminRegionController (ROLE_ADMIN)
/admin/brigade/*           → AdminBrigadeController (ROLE_ADMIN)
/admin/export/*            → AdminExportController (ROLE_ADMIN)
/admin/audit/*             → AdminAuditController (ROLE_ADMIN)
```

### Contrôles/Infractions/Amendes
- `findAll()` → Voir TOUS les éléments sans restriction

---

## 🏛️ 2. ROLE_DIRECTION_GENERALE

**Localisation:** `src/Controller/DirectionGenerale/DirectionGeneraleController`

### Permissions
- ✅ Supervision NATIONALE
- ✅ Voir les statistiques GLOBALES
- ✅ Voir TOUS les contrôles / infractions / amendes
- ✅ Générer des rapports nationaux (mensuels, régionaux, conformité, revenus)
- ✅ **Valider les contrôles majeurs** → `POST /direction-generale/controls/{id}/validate`

### Routes
```
/direction-generale/dashboard      → Statistiques globales (6 KPI)
/direction-generale/reports        → Rapports mensuels/régionaux
/direction-generale/statistics     → Statistiques nationales détaillées
/direction-generale/controls       → Tous les contrôles (pagination)
/direction-generale/infractions    → Toutes les infractions
/direction-generale/amendes        → Toutes les amendes (avec filtrage statut)
/direction-generale/controls/{id}/validate (POST) → Valider contrôle
```

### Données Visibles
- Contrôles : `findAll()` via `InfractionController::index()`
- Infractions : `findAll()` via `InfractionController::index()`
- Amendes : `findAll()` via `AmendeController::index()`

### Validation des Contrôles
```php
// Entité Controle enrichie avec :
private ?string $statut = null;           // VALIDE, EN_ATTENTE...
private ?User $validatedBy = null;        // Qui a validé
private ?\DateTimeImmutable $dateValidation = null;  // Quand

// Action POST : app_direction_generale_control_validate
// - CSRF protection
// - Audit logging via AuditService
// - Status change to 'VALIDE'
```

---

## 🗺️ 3. ROLE_DIRECTION_REGIONALE

**Localisation:** `src/Controller/DirectionRegionaleController`

### Permissions
- ✅ Supervision DE SA RÉGION SEULEMENT
- ✅ Gestion des brigades assignées
- ✅ Voir les contrôles / infractions / amendes de SA RÉGION
- ✅ Générer les rapports régionaux

### Routes
```
/direction-regionale/dashboard      → Stats régionales (brigades, contrôles)
/direction-regionale/brigades       → Liste brigades de la région
/direction-regionale/controls       → Contrôles de la région (pagination)
/direction-regionale/infractions    → Infractions de la région
/direction-regionale/amendes        → Amendes de la région (filtrables par statut)
```

### Filtrage des Données
```php
// Tous les QueryBuilder incluent :
$qb->where('b.region = :region')->setParameter('region', $user->getRegion());

// Methods utilisées dans les repositories :
// - InfractionRepository::findByRegion($region)
// - AmendeRepository::findByRegion($region)
// - ControleRepository::findByRegion($region)
```

### Accès Bloqué
- ❌ Voir les contrôles d'une autre région
- ❌ Voir les infractions d'une autre région
- ❌ Voir les amendes d'une autre région

---

## 🚔 4. ROLE_CHEF_BRIGADE

**Localisation:** `src/Controller/Brigade/BrigadeChefController`

### Permissions
- ✅ Gestion complète de SA BRIGADE
- ✅ Voir les agents de la brigade
- ✅ Voir les contrôles / infractions / amendes de la brigade
- ✅ Dashboard avec stats locales

### Routes
```
/brigade/dashboard          → Stats brigade (agents, contrôles, infractions, amendes)
/brigade/agents             → Roster des agents
/brigade/controls           → Contrôles de la brigade (pagination)
/brigade/infractions        → Infractions de la brigade (pagination)
/brigade/amendes            → Amendes de la brigade (pagination + filtrage statut)
```

### Filtrage des Données
```php
// ControleController::index() applique :
if (in_array('ROLE_CHEF_BRIGADE', $user->getRoles())) {
    $qb->andWhere('b.id = :userBrigade')
        ->setParameter('userBrigade', $user->getBrigade()?->getId());
}

// Methods utilisées :
// - InfractionRepository::findByBrigade($brigade)
// - AmendeRepository::findByBrigade($brigade)
// - ControleRepository::findByBrigade($brigade)
```

### Accès Bloqué
- ❌ Voir les agents d'une autre brigade
- ❌ Voir les contrôles d'une autre brigade
- ❌ Voir les infractions d'une autre brigade
- ❌ Voir les amendes d'une autre brigade

---

## 👮 5. ROLE_AGENT

**Localisation:** `src/Controller/{ControleController, InfractionController, AmendeController}`

### Permissions
- ✅ Enregistrer les contrôles (POST)
- ✅ Saisir les infractions (POST)
- ✅ Enregistrer les amendes (POST)
- ✅ Consulter ses rapports personnels
- ✅ Voir les statistiques personnelles

### Routes
```
/controle/              → Contrôles de SA BRIGADE (pagination)
/controle/new           → Enregistrer nouveau contrôle
/controle/{id}          → Voir détail contrôle
/controle/{id}/edit     → Modifier contrôle
/controle/stats         → Statistiques personnelles (NOUVEAU)

/infraction/            → Infractions de SES CONTRÔLES
/infraction/new         → Créer infraction
/infraction/{id}        → Voir détail
/infraction/{id}/edit   → Modifier
/infraction/{id}/payer  → Enregistrer paiement

/amende/                → Amendes de SES INFRACTIONS
/amende/new             → Créer amende
/amende/{id}            → Voir détail
/amende/{id}/edit       → Modifier
/amende/{id}/recu       → Reçu d'amende
/amende/stats           → Statistiques personnelles
```

### Filtrage des Données
```php
// Entité User inclut :
private ?Region $region = null;     // Région assignée
private ?Brigade $brigade = null;    // Brigade assignée

// ControleController::index() applique :
if (in_array('ROLE_AGENT', $user->getRoles())) {
    $qb->andWhere('b.id = :agentBrigade')
        ->setParameter('agentBrigade', $user->getBrigade()?->getId());
}

// InfractionController::index() utilise :
$infractions = $this->infractionRepository->findByAgentEmail($user->getEmail());

// AmendeController::index() utilise :
$amendes = $this->amendeRepository->findByAgentEmail($user->getEmail());
```

### Statistiques Personnelles
```php
// Route: /controle/stats (GET)
// Affiche :
- Nombre de contrôles enregistrés
- Nombre d'infractions détectées
- Informations de la brigade
- Email et identité
```

### Accès Bloqué
- ❌ Voir les contrôles d'une autre brigade
- ❌ Voir les infractions d'une autre brigade
- ❌ Voir les amendes d'une autre brigade
- ❌ Exporter les données
- ❌ Valider les contrôles
- ❌ Accéder à l'administration

---

## 🔄 Hiérarchie des Droits (Cascade)

La configuration Symfony en `config/packages/security.yaml` établit la hiérarchie :

```yaml
role_hierarchy:
    ROLE_ADMIN: [ROLE_DIRECTION_GENERALE, ROLE_DIRECTION_REGIONALE, ROLE_CHEF_BRIGADE, ROLE_AGENT, ROLE_USER]
    ROLE_DIRECTION_GENERALE: [ROLE_DIRECTION_REGIONALE, ROLE_CHEF_BRIGADE, ROLE_AGENT, ROLE_USER]
    ROLE_DIRECTION_REGIONALE: [ROLE_CHEF_BRIGADE, ROLE_AGENT, ROLE_USER]
    ROLE_CHEF_BRIGADE: [ROLE_AGENT, ROLE_USER]
    ROLE_AGENT: [ROLE_USER]
```

**Signification :**
- Admin = Admin + DG + DR + Chef + Agent + User
- DG = DG + DR + Chef + Agent + User
- DR = DR + Chef + Agent + User
- Chef = Chef + Agent + User
- Agent = Agent + User

---

## 🗂️ Organisation des Contrôleurs par Dossier

```
src/Controller/
├── ControleController.php #[IsGranted('ROLE_AGENT')]
│   ├── index() → Filtre par rôle (Agent/Chef/DR/Admin/DG)
│   ├── new()
│   ├── show()
│   ├── edit()
│   ├── delete()
│   ├── addInfraction()
│   └── stats() → Stats personnelles (Agent/Chef)
│
├── InfractionController.php #[IsGranted('ROLE_AGENT')]
│   ├── index() → Filtre par rôle via findByRegion/findByBrigade/findByAgentEmail
│   ├── new()
│   ├── show()
│   ├── edit()
│   ├── delete()
│   └── payer()
│
├── AmendeController.php #[IsGranted('ROLE_AGENT')]
│   ├── index() → Filtre par rôle via findByRegion/findByBrigade/findByAgentEmail
│   ├── new()
│   ├── show()
│   ├── edit()
│   ├── delete()
│   ├── recu()
│   └── stats()
│
├── Admin/
│   ├── AdminRegionController.php #[IsGranted('ROLE_ADMIN')]
│   ├── AdminBrigadeController.php #[IsGranted('ROLE_ADMIN')]
│   ├── AdminExportController.php #[IsGranted('ROLE_ADMIN')]
│   └── AdminAuditController.php #[IsGranted('ROLE_ADMIN')]
│
├── Brigade/
│   └── BrigadeChefController.php #[IsGranted('ROLE_CHEF_BRIGADE')]
│       ├── dashboard()
│       ├── agents()
│       ├── controls()
│       ├── infractions()
│       └── amendes()
│
├── DirectionGenerale/
│   └── DirectionGeneraleController.php #[IsGranted('ROLE_DIRECTION_GENERALE')]
│       ├── dashboard()
│       ├── reports()
│       ├── statistics()
│       ├── controls()
│       ├── infractions()
│       ├── amendes()
│       └── validateControl() → Validation par DG
│
└── DirectionRegionaleController.php #[IsGranted('ROLE_DIRECTION_REGIONALE')]
    ├── dashboard()
    ├── brigades()
    ├── controls()
    ├── infractions()
    └── amendes()
```

---

## 🔧 Implémentation des Filtres dans ControleController

```php
// Filtrage par rôle dans ControleController::index()
$user = $this->getUser();
if ($user) {
    // ROLE_ADMIN et ROLE_DIRECTION_GENERALE voient tout
    if (!in_array('ROLE_ADMIN', $user->getRoles()) && 
        !in_array('ROLE_DIRECTION_GENERALE', $user->getRoles())) {
        
        // ROLE_DIRECTION_REGIONALE -> restreindre par région
        if (in_array('ROLE_DIRECTION_REGIONALE', $user->getRoles())) {
            $qb->andWhere('r.id = :userRegion')
                ->setParameter('userRegion', $user->getRegion()?->getId());
        }

        // ROLE_CHEF_BRIGADE -> restreindre par brigade
        if (in_array('ROLE_CHEF_BRIGADE', $user->getRoles())) {
            $qb->andWhere('b.id = :userBrigade')
                ->setParameter('userBrigade', $user->getBrigade()?->getId());
        }

        // ROLE_AGENT -> restreindre par brigade
        if (in_array('ROLE_AGENT', $user->getRoles())) {
            $qb->andWhere('b.id = :agentBrigade')
                ->setParameter('agentBrigade', $user->getBrigade()?->getId());
        }
    }
}
```

---

## 📊 Matrice de Permissions

| Fonctionnalité | ROLE_ADMIN | ROLE_DG | ROLE_DR | ROLE_CHEF | ROLE_AGENT |
|---|:---:|:---:|:---:|:---:|:---:|
| **Enregistrer contrôles** | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Saisir infractions** | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Enregistrer amendes** | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Voir tous les contrôles** | ✅ | ✅ | ❌* | ❌* | ❌* |
| **Voir contrôles de sa région** | ✅ | ✅ | ✅ | ❌ | ❌ |
| **Voir contrôles de sa brigade** | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Valider contrôles** | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Générer rapports** | ✅ | ✅ | ✅ | ✅ | ❌ |
| **Voir statistiques** | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Exporter données** | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Gérer utilisateurs** | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Gérer régions/brigades** | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Voir audit logs** | ✅ | ❌ | ❌ | ❌ | ❌ |

*: Limité à sa région/brigade

---

## 🚀 Fonctionnalités Nouvellement Implémentées

### 1. Validation des Contrôles par Direction Générale
- **Route:** `POST /direction-generale/controls/{id}/validate`
- **Accès:** ROLE_DIRECTION_GENERALE  uniquement
- **Effet:** Marque le contrôle comme VALIDE avec audit logging
- **Champs modifiés:** 
  - `Controle.statut = 'VALIDE'`
  - `Controle.validatedBy = $user`
  - `Controle.dateValidation = now()`

### 2. Filtrage Complet des Contrôles
- **Implémentation:** `ControleController::index()` filtre par rôle
- **Dossier:** `src/Controller/`
- **Pattern:** Utilise `User->getRegion()` et `User->getBrigade()`

### 3. Statistiques Personnelles pour Agents
- **Route:** `GET /controle/stats`
- **Accès:** ROLE_AGENT, ROLE_CHEF_BRIGADE
- **Données affichées:**
  - Nombre de contrôles
  - Nombre d'infractions
  - Informations de brigade
  - (Pour Chef) Nombre d'agents

---

## 📝 Notes d'Implémentation

1. **User Entity enrichie** :
   ```php
   private ?Region $region = null;
   private ?Brigade $brigade = null;
   ```
   → Pour l'affiliation regionale/brigade des DR, Chef et Agent

2. **Controle Entity enrichie** :
   ```php
   private ?string $statut = null;           // Validation status
   private ?User $validatedBy = null;        // Qui a validé
   private ?\DateTimeImmutable $dateValidation = null;
   ```
   → Support de la validation par DG

3. **Repository Methods** :
   - `findByRegion($region)` - tous les repositories
   - `findByBrigade($brigade)` - tous les repositories
   - `findByAgentEmail($email)` - Controle/Infraction/Amende
   → Pour supporter le filtrage par rôle

4. **Security Hierarchy** :
   ```yaml
   # config/packages/security.yaml
   role_hierarchy:
       ROLE_ADMIN: [ROLE_DIRECTION_GENERALE, ...]
   ```
   → Cascade automatique des droits

---

## ✅ Tous les Contrôleurs avec Différenciation

- [x] `ControleController` - Filtrage complet par rôle
- [x] `InfractionController` - Filtrage complet par rôle  
- [x] `AmendeController` - Filtrage complet par rôle
- [x] `Admin/AdminRegionController` - ROLE_ADMIN uniquement
- [x] `Admin/AdminBrigadeController` - ROLE_ADMIN uniquement
- [x] `Admin/AdminExportController` - ROLE_ADMIN uniquement
- [x] `Admin/AdminAuditController` - ROLE_ADMIN uniquement
- [x] `Brigade/BrigadeChefController` - ROLE_CHEF_BRIGADE avec filtrage brigade
- [x] `DirectionGenerale/DirectionGeneraleController` - ROLE_DIRECTION_GENERALE avec validation
- [x] `DirectionRegionaleController` - ROLE_DIRECTION_REGIONALE avec filtrage région

---

**Dernière mise à jour:** 8 février 2026  
**Statut:** ✅ Toutes les différenciations implémentées
