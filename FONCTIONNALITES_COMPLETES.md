# 🎯 Fonctionnalités Complètes par Rôle - Police Routière

**Documentation exhaustive de TOUTES les fonctionnalités implémentées**  
*Mise à jour: 8 février 2026*

---

## 📊 Sommaire

1. [ROLE_ADMIN](#admin)
2. [ROLE_DIRECTION_GENERALE](#direction-generale)
3. [ROLE_DIRECTION_REGIONALE](#direction-regionale)
4. [ROLE_CHEF_BRIGADE](#chef-brigade)
5. [ROLE_AGENT](#agent)

---

<a id="admin"></a>

## 👑 ROLE_ADMIN - Administrateur Système

**Accès:** Toutes les fonctionnalités | **Dossier:** `src/Controller/Admin/`

### 1️⃣ Gestion des Utilisateurs

| Fonctionnalité | Route | Méthode | Template | Description |
|---|---|---|---|---|
| 📋 Lister tous les utilisateurs | `/admin/user/` | GET | `user/index.html.twig` | Liste paginée de tous les utilisateurs |
| ➕ Créer nouvel utilisateur | `/admin/user/new` | GET/POST | `user/new.html.twig` | Formulaire création avec rôle, région, brigade |
| 👤 Voir détail utilisateur | `/admin/user/{id}` | GET | `user/show.html.twig` | Profil complet, rôle, contacts |
| ✏️ Modifier utilisateur | `/admin/user/{id}/edit` | GET/POST | `user/edit.html.twig` | Éditer nom, email, rôle, affilations |
| 🗑️ Supprimer utilisateur | `/admin/user/{id}` | POST | - | Suppression avec confirmation CSRF |
| 🔄 Activer/Désactiver | `/admin/user/{id}/toggle-active` | POST | - | Toggle isActive (avec protection auto-désactivation) |
| 🔑 Réinitialiser mot de passe | `/admin/user/{id}/reset-password` | POST | - | Génère pwd temporaire, log audit |
| 📊 Statistiques utilisateurs | `/admin/user/stats` | GET | `user/stats.html.twig` | Total, actifs, inactifs, par rôle |

**Données du Formulaire :**
- Email (unique)
- Nom, Prénom
- Mot de passe (bcrypt/Argon2)
- Rôle (dropdown)
- Région (conditionnelle si DR/Chef/Agent)
- Brigade (conditionnelle si Chef/Agent)
- Téléphone (optionnel)
- Statut actif (checkbox)

**Validations :**
- Email unique + format valide
- Mot de passe min 8 caractères
- Rôle requis
- Région/Brigade requis selon rôle

**Services Utilisés :**
- `UserPasswordHasherInterface` (hachage mot de passe)
- `EntityManager` (persist/flush)

**Audit :**
- ✅ Création utilisateur loggée
- ✅ Modification loggée (changements enregistrés)
- ✅ Suppression loggée
- ✅ Réinitialisation mdp loggée

---

### 2️⃣ Gestion des Régions

| Fonctionnalité | Route | Méthode | Template | Description |
|---|---|---|---|---|
| 📍 Lister régions | `/admin/region/` | GET | `admin/region/index.html.twig` | Toutes les 9 régions de Guinée |
| ➕ Créer région | `/admin/region/new` | GET/POST | `admin/region/new.html.twig` | Ajouter nouvelle région |
| 📌 Voir détail région | `/admin/region/{id}` | GET | `admin/region/show.html.twig` | Infos région, contact, brigades |
| ✏️ Modifier région | `/admin/region/{id}/edit` | GET/POST | `admin/region/edit.html.twig` | Éditer nom, code, directeur |
| 🗑️ Supprimer région | `/admin/region/{id}/delete` | POST | - | Supprimer avec CSRF |
| 🟢/🔴 Actif/Inactif | `/admin/region/{id}/toggle` | POST | - | Toggle isActive |

**Champs Formulaire :**
- Code région (ex: `CKY`, `CIN`)
- Nom (ex: `Conakry`, `Kindia`)
- Description
- Directeur (nom)
- Email contact
- Téléphone
- Adresse
- Statut actif

**Données Présentes :**
- 9 régions de Guinée (Conakry, Kindia, Labé, Faranah, Mamou, Boké, N'Zérékoré, Kankan, Siguiri)

**Repository Methods :**
- `RegionRepository::findAll()`
- `RegionRepository::find($id)`

---

aram `?region=id`)

**Données Présentes :**
- 11 brigades réparties dans les régions

---

### 4️⃣ Export de Données Complètes

**Route Base:** `/admin/export/`  
**Format:** CSV avec BOM UTF-8 (Excel compatible)

| Export | Route | Fichier Généré | Colonnes |
|---|---|---|---|
| 👥 Utilisateurs | `/users` | `utilisateurs_YYYY-MM-DD_HH-MM-SS.csv` | Email, Nom, Prénom, Téléphone, Rôles, Actif, CreatedAt |
| 🚔 Contrôles | `/controls` | `controles_YYYY-MM-DD_HH-MM-SS.csv` | ID, Agent, Date, Lieu, TypeVéhicule, Immatriculation, Conducteur, Observations |
| 📋 Infractions | `/infractions` | `infractions_YYYY-MM-DD_HH-MM-SS.csv` | ID, Contrôle, Code, Description, MontantAmende, Date |
| 💰 Amendes | `/amendes` | `amendes_YYYY-MM-DD_HH-MM-SS.csv` | NuméroAmende, Infraction, Montant, StatutPaiement, DateÉmission, DateÉchéance, DatePaiement |
| 📍 Régions | `/regions` | `regions_YYYY-MM-DD_HH-MM-SS.csv` | Code, Nom, Description, Directeur, Email, Téléphone, Adresse, Actif |
| 🏢 Brigades | `/brigades` | `brigades_YYYY-MM-DD_HH-MM-SS.csv` | Code, Nom, Région, Chef, Email, Téléphone, Localité, Actif |
| 📊 Rapports | `/rapports` | `rapports_YYYY-MM-DD_HH-MM-SS.csv` | ID, Titre, Auteur, Statut, DateCréation, DateValidation |

**Service Utilisé :** `ExportService` (méthodes `generateCSV`, `arrayToCSV`)

**Fonctionnalités :**
- ✅ UTF-8 BOM (compatible Excel)
- ✅ Délimiteur `;` (virgule française)
- ✅ Horodatage du fichier
- ✅ Audit logging (AuditService::logExport)

---

### 5️⃣ Audit & Logs

| Fonctionnalité | Route | Méthode | Template | Description |
|---|---|---|---|---|
| 📜 Lister tous les logs | `/admin/audit/` | GET | `admin/audit/index.html.twig` | Logs paginés (50/page) |
| 🔍 Voir détail log | `/admin/audit/{id}` | GET | `admin/audit/show.html.twig` | Détails complets action |
| 🔎 Recherche avancée | `/admin/audit/search` | POST | `admin/audit/search.html.twig` | Recherche par date, user, action |

**Données Loggées :**
- ID Log
- Utilisateur (identification unique)
- Action (LOGIN, LOGOUT, CREATE, UPDATE, DELETE, VIEW, EXPORT)
- Entité affectée (User, Controle, Infraction, etc.)
- ID Entité
- Changements (array: before/after)
- Description libre
- Adresse IP (avec fallback: CLIENT_IP → X_FORWARDED_FOR → REMOTE_ADDR)
- User Agent (navigateur)
- Timestamp création

**Filtres de Recherche :**
- Plage de dates (from/to)
- Utilisateur (email)
- Action (dropdown)
- Entité
- Pagination (50 par défaut)

**Repository Methods :**
- `AuditLogRepository::findAll()`
- Requêtes personnalisées avec QueryBuilder

---

### 6️⃣ Configuration Système (Optionnel)

*Structure pour gestion future :*
- Paramètres systèmes
- Paliers d'amendes
- Règles de validation
- Intégrations externes

---

### 📊 Tableau de Bord Admin

**Route:** `/admin` (si créé)  
**Contenu:**
- Vue d'ensemble statistiques globales
- Raccourcis vers gestion utilisateurs/régions/brigades
- Alertes système
- Accès rapide aux exports

---

<a id="direction-generale"></a>

## 🏛️ ROLE_DIRECTION_GENERALE - Direction Générale

**Accès:** Supervision nationale + Export | **Dossier:** `src/Controller/DirectionGenerale/`

### 1️⃣ Tableau de Bord National

| Fonctionnalité | Route | Méthode | Template | Description |
|---|---|---|---|---|
| 📊 Dashboard avec KPIs | `/direction-generale/dashboard` | GET | `direction_generale/dashboard.html.twig` | Vue globale chifrée |

**KPI Cards Affichées :**
- 👥 Total utilisateurs
- 👮 Total agents
- 🚔 Total contrôles effectués
- 📋 Total infractions détectées
- 💰 Total amendes émises
- 📍 Total régions
- ⏳ Amendes en attente de paiement

**Données Source :**
- `UserRepository::count(['roles' => 'ROLE_AGENT'])`
- `ControleRepository::count([])`
- `InfractionRepository::count([])`
- `AmendeRepository::count([])`
- `AmendeRepository::count(['statutPaiement' => 'EN_ATTENTE'])`

**Widgets Supplémentaires :**
- 📋 10 derniers contrôles (avec détails: lieu, date, agent)
- 📋 10 dernières infractions (avec codes)

---

### 2️⃣ Validation des Contrôles Majeurs

| Fonctionnalité | Route | Méthode | Template | Description |
|---|---|---|---|---|
| ✅ Valider contrôle | `/direction-generale/controls/{id}/validate` | POST | - | Marquer contrôle comme VALIDE |

**Processus :**
1. CSRF Validation (`'validate_controle' . $id`)
2. Update Controle:
   - `statut = 'VALIDE'`
   - `validatedBy = $user`
   - `dateValidation = now()`
3. Flush BD
4. Audit Logging via `AuditService::logUpdate()`
5. Flash message "Contrôle validé avec succès"

**Sécurité :**
- ✅ #[IsGranted('ROLE_DIRECTION_GENERALE')]
- ✅ CSRF token protection
- ✅ Audit trail complet

---

### 3️⃣ Rapports Globaux

| Fonctionnalité | Route | Méthode | Template | Description |
|---|---|---|---|---|
| 📈 Rapports par période | `/direction-generale/reports` | GET | `direction_generale/reports.html.twig` | Rapports filtrés |

**Paramètres de Rapport :**
- **Period:** Semaine, Mois, Trimestre, Année (radio buttons)
- **Région:** Optionnel, toutes par défaut (dropdown)

**Contenu du Rapport :**
- Nombre de contrôles effectués
- Nombre d'infractions
- Nombre d'amendes
- Montant total amendes
- Taux de recouvrement
- Moyenne par jour/mois

**Service Utilisé :** `ReportService::getReportData($period, $region)`

**Graphiques :**
- Tendance contrôles (par jour)
- Distribution infractions (par type)
- Évolution amendes (par statut)

---

### 4️⃣ Statistiques Nationales

| Fonctionnalité | Route | Méthode | Template | Description |
|---|---|---|---|---|
| 📊 Stats détaillées | `/direction-generale/statistics` | GET | `direction_generale/statistics.html.twig` | Analyse approfondie |

**Sections :**

**A. Statistiques Globales (6 cards) :**
- Total utilisateurs
- Total agents
- Total contrôles
- Total infractions
- Total amendes
- Total régions

**B. Analyse des Amendes :**
- 3-part progress bar:
  - 🟢 EN_ATTENTE (jaune)
  - 💚 PAYEE (vert)
  - ❌ REJETEE (rouge)

**C. Comparaison Régionale :**
- Table: Région | Contrôles | Infractions | Amendes | Taux paiement

**Service Utilisé :** `StatisticsService::getComprehensiveStatistics()` + `getRegionalStatistics()`

---

### 5️⃣ Liste des Contrôles (Tous)

| Fonctionnalité | Route | Méthode | Template | Description |
|---|---|---|---|---|
| 🚔 Lister tous contrôles | `/direction-generale/controls` | GET | `direction_generale/controls.html.twig` | Pagination 20/page |
| 🔎 Filtrer par région | (query param) | - | - | Optionnel région |

**Colonnes Tableau :**
- Date/Heure contrôle
- Lieu
- Agent (email)
- Brigade
- Région
- Véhicule (marque + immatriculation)
- Conducteur
- Actions (View, Edit, Delete, Validate)

**Pagination :**
- 20 contrôles par page
- Navigation prev/next
- Page counter

**Filtres :**
- Région (optionnel, dropdown)

---

### 6️⃣ Liste des Infractions (Tous)

| Fonctionnalité | Route | Méthode | Template | Description |
|---|---|---|---|---|
| 📋 Lister infractions | `/direction-generale/infractions` | GET | `direction_generale/infractions.html.twig` | Pagination 20/page |
| 🔎 Filtrer par région | (query param) | - | - | Optionnel |

**Colonnes Tableau :**
- Code infraction (badge warning)
- Description
- Montant amende (GNF)
- Date
- Contrôle (ID + lien)
- Région
- Actions (View, Edit, Delete)

**Filtres :**
- Région (optionnel)

---

### 7️⃣ Liste des Amendes (Tous)

| Fonctionnalité | Route | Méthode | Template | Description |
|---|---|---|---|---|
| 💰 Lister amendes | `/direction-generale/amendes` | GET | `direction_generale/amendes.html.twig` | Pagination 20/page |
| 📊 Filtrer statut | (query param) | - | - | EN_ATTENTE/PAYEE/REJETEE |
| 🔎 Filtrer région | (query param) | - | - | Optionnel |

**Colonnes Tableau :**
- Numéro amende
- Infraction (code)
- Montant (GNF)
- Statut (badge: success/warning/danger)
- Date émission
- Date échéance
- Date paiement
- Actions (View, Edit, Delete)

**Filtres :**
- Statut (dropdown: Tous / En attente / Payée / Rejetée)
- Région (dropdown)

**Badge Styles :**
- EN_ATTENTE → warning (jaune)
- PAYEE → success (vert)
- REJETEE → danger (rouge)

---

### 🔐 Autorisations

- ✅ Voir TOUS les contrôles
- ✅ Voir TOUTES les infractions
- ✅ Voir TOUTES les amendes
- ✅ Valider les contrôles
- ❌ Exporter les données (admin only)
- ❌ Gérer utilisateurs (admin only)

---

<a id="direction-regionale"></a>

## 🗺️ ROLE_DIRECTION_REGIONALE - Direction Régionale

**Accès:** Supervision régionale | **Dossier:** `src/Controller/DirectionRegionaleController.php`

### 1️⃣ Tableau de Bord Régional

| Fonctionnalité | Route | Méthode | Template | Description |
|---|---|---|---|---|
| 📊 Dashboard région | `/direction-regionale/dashboard` | GET | `direction_regionale/dashboard.html.twig` | Vue régionale chifrée |

**KPI Cards :**
- 🏢 Nombre brigades de la région
- 🚔 Total contrôles région
- 📋 Total infractions région
- 💰 Total amendes région
- ⏳ Amendes en attente
- ✅ Amendes payées

**Données Filtrées :**
- `$region = $user->getRegion()` (strictement)
- `BrigadeRepository::findBy(['region' => $region])`
- `ControleRepository::countByRegion($region->getId())`
- `InfractionRepository::countByRegion($region->getId())`
- `AmendeRepository::countByRegion($region->getId())`

**Widget :**
- 📋 10 derniers contrôles de la région
- Avec brigade source

---

### 2️⃣ Gestion des Brigades

| Fonctionnalité | Route | Méthode | Template | Description |
|---|---|---|---|---|
| 👥 Lister brigades région | `/direction-regionale/brigades` | GET | `direction_regionale/brigades.html.twig` | Toutes brigades de la région |

**Colonnes Tableau :**
- Code brigade
- Nom brigade
- Chef (nom)
- Localité
- Email brigade
- Téléphone
- Statut actif (badge)

**Actions (readonly) :**
- View détails brigade
- Lien vers contrôles brigade
- Lien vers agents brigade

---

### 3️⃣ Contrôles de la Région

| Fonctionnalité | Route | Méthode | Template | Description |
|---|---|---|---|---|
| 🚔 Lister contrôles région | `/direction-regionale/controls` | GET | `direction_regionale/controls.html.twig` | Pagination 20/page |

**Colonnes Tableau :**
- Date/Heure
- Brigade (badge code avec couleur)
- Lieu
- Véhicule (marque + immatriculation)
- Conducteur (nom + prénom)
- Agent (email)
- Actions (View, Edit, Delete)

**Filtrage Automatique :**
- Où brigade.region = $user->getRegion()

**Pagination :**
- 20 par page
- Next/Prev buttons

---

### 4️⃣ Infractions de la Région

| Fonctionnalité | Route | Méthode | Template | Description |
|---|---|---|---|---|
| 📋 Infractions région | `/direction-regionale/infractions` | GET | `direction_regionale/infractions.html.twig` | Pagination 20/page |

**Colonnes Tableau :**
- Code infraction (badge)
- Brigade source (badge)
- Description
- Montant (GNF)
- Date
- Actions (View, Edit)

**Filtrage :**
- Automatiquement par région

---

### 5️⃣ Amendes de la Région

| Fonctionnalité | Route | Méthode | Template | Description |
|---|---|---|---|---|
| 💰 Amendes région | `/direction-regionale/amendes` | GET | `direction_regionale/amendes.html.twig` | Pagination 20/page |
| 🔎 Filtrer statut | (query param) | - | - | EN_ATTENTE/PAYEE/REJETEE |

**Colonnes Tableau :**
- Numéro amende
- Brigade source (badge)
- Infraction (code)
- Montant
- Statut (badge: success/warning/danger)
- Date émission
- Actions (View, Edit, Delete)

**Filtres :**
- Statut paiement (dropdown)

**Filtrage Automatique :**
- Où brigade.region = $user->getRegion()

---

### 🔐 Autorisations

- ✅ Voir brigades de SA région seulement
- ✅ Voir contrôles de SA région seulement
- ✅ Voir infractions de SA région seulement
- ✅ Voir amendes de SA région seulement
- ✅ Rapports régionaux
- ❌ Voir autres régions
- ❌ Valider contrôles (admin/DG only)

---

<a id="chef-brigade"></a>

## 🚔 ROLE_CHEF_BRIGADE - Chef de Brigade

**Accès:** Gestion brigade + agents | **Dossier:** `src/Controller/Brigade/BrigadeChefController.php`

### 1️⃣ Tableau de Bord Brigade

| Fonctionnalité | Route | Méthode | Template | Description |
|---|---|---|---|---|
| 📊 Dashboard brigade | `/brigade/dashboard` | GET | `brigade/dashboard.html.twig` | Vue brigade locale |

**KPI Cards :**
- 👥 Nombre agents brigade
- 🚔 Total contrôles brigade
- 📋 Total infractions brigade
- 💰 Total amendes brigade
- ⏳ Amendes en attente

**Vérification :**
```php
$brigade = $user->getBrigade();
if (!$brigade) {
    throw $this->createAccessDeniedException('Brigade not found');
}
```

**Données Filtrées :**
- `AgentRepository::findBy(['brigade' => $brigade])`
- `ControleRepository::findBy(['brigade' => $brigade], limit: 10)`
- Comptes via repositories

**Widget :**
- 📋 10 derniers contrôles brigade

---

### 2️⃣ Gestion des Agents

| Fonctionnalité | Route | Méthode | Template | Description |
|---|---|---|---|---|
| 👥 Lister agents brigade | `/brigade/agents` | GET | `brigade/agents.html.twig` | Roster complet |

**Colonnes Tableau :**
- Matricule (badge code)
- Nom (initial capital)
- Prénom
- Grade (BRIGADIER, SERGEANT, LIEUTENANT, etc.)
- Date embauche
- Statut (actif/inactif)

**Actions :**
- View profil agent
- Edit détails
- Désactiver agent

**Données :**
- `AgentRepository::findBy(['brigade' => $brigade])`

---

### 3️⃣ Contrôles de la Brigade

| Fonctionnalité | Route | Méthode | Template | Description |
|---|---|---|---|---|
| 🚔 Lister contrôles | `/brigade/controls` | GET | `brigade/controls.html.twig` | Pagination 20/page |

**Colonnes Tableau :**
- Date/Heure
- Lieu
- Agent (email ou nom)
- Véhicule (marque + immat)
- Conducteur
- Actions (View, Edit, Delete, Add Infraction)

**Filtrage :**
- Automatiquement brigade = $user->getBrigade()

**Pagination :**
- 20 par page
- Navigation avec current_page / total_pages

---

### 4️⃣ Infractions de la Brigade

| Fonctionnalité | Route | Méthode | Template | Description |
|---|---|---|---|---|
| 📋 Infractions brigade | `/brigade/infractions` | GET | `brigade/infractions.html.twig` | Pagination 20/page |

**Colonnes Tableau :**
- Code infraction (warning badge)
- Description (condensée)
- Montant amende (GNF)
- Date
- Contrôle (lien)
- Actions (View)

**Filtrage :**
- Où brigade = $user->getBrigade()

---

### 5️⃣ Amendes de la Brigade

| Fonctionnalité | Route | Méthode | Template | Description |
|---|---|---|---|---|
| 💰 Amendes brigade | `/brigade/amendes` | GET | `brigade/amendes.html.twig` | Pagination 20/page |
| 📊 Filtrer statut | (query param) | - | - | EN_ATTENTE/PAYEE/REJETEE |

**Colonnes Tableau :**
- Numéro amende
- Infraction (code)
- Montant
- Statut (badge: success/warning/danger)
- Date émission
- Date échéance
- Actions (View, Edit, Delete)

**Filtres :**
- Statut (dropdown)

**Coloration :**
- EN_ATTENTE → badge-warning (jaune)
- PAYEE → badge-success (vert)
- REJETEE → badge-danger (rouge)

---

### 🔐 Autorisations

- ✅ Voir agents SA brigade
- ✅ Voir contrôles SA brigade
- ✅ Voir infractions SA brigade
- ✅ Voir amendes SA brigade
- ✅ Rapports brigade
- ✅ Dashboard brigade
- ❌ Voir autres brigades
- ❌ Gérer utilisateurs
- ❌ Gérer régions
- ❌ Exporter

---

<a id="agent"></a>

## 👮 ROLE_AGENT - Agent Terrain

**Accès:** Opérations terrain | **Dossier:** `src/Controller/{ControleController, InfractionController, AmendeController}`

### 1️⃣ Gestion des Contrôles

| Fonctionnalité | Route | Méthode | Template | Description |
|---|---|---|---|---|
| 🚔 Lister controllés | `/controle/` | GET | `controle/index.html.twig` | Pagination 20/page |
| ➕ Enregistrer contrôle | `/controle/new` | GET/POST | `controle/new.html.twig` | Nouveau contrôle |
| 👁️ Voir détail | `/controle/{id}` | GET | `controle/show.html.twig` | Détails complets |
| ✏️ Modifier contrôle | `/controle/{id}/edit` | GET/POST | `controle/edit.html.twig` | Éditer infos |
| 🗑️ Supprimer contrôle | `/controle/{id}` | POST | - | Seul admin/DG/DR |
| ➕ Ajouter infraction | `/controle/{id}/add-infraction` | GET | - | Redirect vers créer infraction |

**Champs du Formulaire (New/Edit) :**
- Date du contrôle (datetime)
- Lieu du contrôle (text)
- Brigade (pré-rempli: $user->getBrigade())
- Agent (pré-rempli: agent brigade)
- Marque véhicule (text)
- Immatriculation (text, validée)
- Nom conducteur (text)
- Prénom conducteur (text)
- N° de conducteur / permis (text)
- Observations (textarea)

**Validations :**
- Date contrôle: obligatoire
- Lieu: obligatoire
- Immatriculation: format validé AA0000BB
- Noms conducteur: min 2 caractères

**Colonnes Liste :**
- Date/Heure
- Lieu
- Immatriculation
- Conducteur (Nom Prénom)
- Brigade
- Nombre infractions
- Actions (View, Edit, Delete, Add Infraction)

**Filtres de Recherche :**
- **search:** Marque, immatriculation, lieu, noms conducteur
- **date_start / date_end:** Plage de dates
- **brigade:** Filtrer brigade (admins)
- **agent:** Filtrer agent (admins)

**Filtrage par Rôle :**
```php
if (in_array('ROLE_AGENT', $user->getRoles())) {
    $qb->andWhere('b.id = :agentBrigade')
        ->setParameter('agentBrigade', $user->getBrigade()?->getId());
}
```

**Pagination :**
- 20 par page
- Navigation avec compteur

---

### 2️⃣ Gestion des Infractions

| Fonctionnalité | Route | Méthode | Template | Description |
|---|---|---|---|---|
| 📋 Lister infractions | `/infraction/` | GET | `infraction/index.html.twig` | Listées filtrage |
| ➕ Ajouter infraction | `/infraction/new` | GET/POST | `infraction/new.html.twig` | Nouvelle infraction |
| 👁️ Voir détail | `/infraction/{id}` | GET | `infraction/show.html.twig` | Détails |
| ✏️ Modifier infraction | `/infraction/{id}/edit` | GET/POST | `infraction/edit.html.twig` | Éditer |
| 🗑️ Supprimer | `/infraction/{id}` | POST | - | Seul admin/DG/DR |

**Champs Formulaire (New/Edit) :**
- Contrôle (dropdown ou query param `?controleId=X`)
- Code infraction (text, ex: `C001`, `V002`)
- Description (textarea)
- Montant amende (number, GNF)
- Catégorie (dropdown)
- Référence auto-générée: `INF-YYYY-XXXXXXXX`

**Validations :**
- Code: obligatoire, min 3 chars
- Description: obligatoire, min 10 chars
- Montant: > 0, < 10M GNF

**Colonnes Liste :**
- Code (badge warning)
- Description (tronquée)
- Montant (GNF)
- Date infraction
- Contrôle (link)
- Brigade
- Actions (View, Edit, Delete, Payer)

**Filtrage par Rôle :**
- Agent: voir seulement infractions SES contrôles
- Chef: voir infractions brigade
- DR: voir infractions région
- Admin/DG: voir toutes

**Repository Utilisation :**
- `InfractionRepository::findAll()` (Admin/DG)
- `findByRegion($region)` (DR)
- `findByBrigade($brigade)` (Chef)
- `findByAgentEmail($email)` (Agent)

---

### 3️⃣ Gestion des Amendes

| Fonctionnalité | Route | Méthode | Template | Description |
|---|---|---|---|---|
| 💰 Lister amendes | `/amende/` | GET | `amende/index.html.twig` | Listées filtrées |
| ➕ Créer amende | `/amende/new` | GET/POST | `amende/new.html.twig` | Nouvelle amende |
| 👁️ Voir détail | `/amende/{id}` | GET | `amende/show.html.twig` | Détails amende |
| ✏️ Modifier amende | `/amende/{id}/edit` | GET/POST | `amende/edit.html.twig` | Éditer montant/statut |
| 🗑️ Supprimer | `/amende/{id}` | POST | - | Seul admin/DG/DR |
| 📨 Voir reçu | `/amende/{id}/recu` | GET | `amende/recu.html.twig` | Reçu imprimable |

**Champs Formulaire (New/Edit) :**
- Infraction (dropdown ou query param)
- Montant amende (number, GNF, pré-rempli de l'infraction)
- Statut paiement (dropdown: EN_ATTENTE, PAYEE, REJETEE)
- Date émission (datetime auto = now)
- Date échéance (datetime, calcul auto)
- Référence auto-générée: `AMD-YYYY-XXXXXXXX`

**Validations :**
- Infraction: obligatoire
- Montant: > 0
- Statut: parmi énumération

**Colonnes Liste :**
- Numéro amende
- Infraction (code)
- Conducteur
- Montant (GNF)
- Statut (badge: success/warning/danger)
- Date émission
- Date échéance
- Actions (View, Edit, Delete, Reçu)

**Statut Badges :**
- EN_ATTENTE → warning (jaune)
- PAYEE → success (vert)
- REJETEE → danger (rouge)

**Filtrage par Rôle :**
- Agent: voir amendes ses infractions
- Chef: voir amendes brigade
- DR: voir amendes région
- Admin/DG: voir toutes

**Logique Calcul Montant Total :**
```php
// Si montant >= infraction.montantAmende → PAYEE
// Sinon → PARTIELLEMENT_PAYEE ou EN_ATTENTE
```

---

### 4️⃣ Statistiques Personnelles

| Fonctionnalité | Route | Méthode | Template | Description |
|---|---|---|---|---|
| 📊 Mes stats (Agent) | `/controle/stats` | GET | `controle/stats.html.twig` | Stats personnelles |
| 📊 Stats brigade (Chef) | `/controle/stats` | GET | `controle/stats.html.twig` | Stats brigade complètes |

**Pour ROLE_AGENT :**
- Nom + Email
- Brigade affectée
- Nombre total contrôles enregistrés
- Nombre total infractions détectées
- Nombre total amendes émises

**Pour ROLE_CHEF_BRIGADE :**
- Brigade name
- Nombre agents brigade
- Nombre total contrôles brigade
- Nombre total infractions
- Nombre total amendes
- Amendes en attente

**Template :**
- 6 cards Bootstrap avec chiffres
- Badges status
- Actions (retour, imprimer, exporter)

**Service Utilisé :** Requêtes directes repositories

---

### 📋 Workflow Complet Agent

```
1. Effectuer contrôle routier
   ↓
2. /controle/new → Enregistrer contrôle
   - Marque, immatriculation, conducteur, observations
   ↓
3. /infraction/new?controleId=X → Ajouter infraction détectée
   - Code, description, montant amende
   ↓
4. /amende/new?infractionId=Y → Créer amende correspondante
   - Montant, date échéance, statut initial EN_ATTENTE
   ↓
5. Consultable dans /controle/, /infraction/, /amende/
   ↓
6. Vue statistiques: /controle/stats
```

---

### 🔐 Autorisations ROLE_AGENT

- ✅ Enregistrer contrôles
- ✅ Saisir infractions
- ✅ Créer amendes
- ✅ Voir ses contrôles/infractions/amendes
- ✅ Consulter stats personnelles
- ❌ Voir autres brigades
- ❌ Valider contrôles
- ❌ Supprimer données que admin/DG/DR
- ❌ Exporter

---

## 🔄 Filtrage Automatique par Rôle

Appliqué dans **tous les contrôleurs** :

```php
// ControleController::index() pattern
$user = $this->getUser();
if ($user && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DIRECTION_GENERALE')) {
    if ($this->isGranted('ROLE_DIRECTION_REGIONALE')) {
        $qb->andWhere('r.id = :userRegion')->setParameter('userRegion', $user->getRegion()?->getId());
    }
    if ($this->isGranted('ROLE_CHEF_BRIGADE')) {
        $qb->andWhere('b.id = :userBrigade')->setParameter('userBrigade', $user->getBrigade()?->getId());
    }
    if ($this->isGranted('ROLE_AGENT')) {
        $qb->andWhere('b.id = :agentBrigade')->setParameter('agentBrigade', $user->getBrigade()?->getId());
    }
}
```

**Appliqué à :**
- ✅ ControleController
- ✅ InfractionController
- ✅ AmendeController
- (Brigade/* et DirectionGenerale/* et DirectionRegionale* utilisent repositories dédiées)

---

## 📊 Récapitulatif Complet

| Rôle | Routes | Fonctionnalités | Filtrage |
|---|---|---|---|
| **ROLE_ADMIN** | 30+ | CRUD users/régions/brigades, export, audit | Aucun |
| **ROLE_DIRECTION_GENERALE** | 12 | Dashboard, rapports, statistiques, validation | Données globales |
| **ROLE_DIRECTION_REGIONALE** | 10 | Dashboard région, brigades, contrôles/infractions/amendes région | Région assignée |
| **ROLE_CHEF_BRIGADE** | 10 | Dashboard brigade, agents, contrôles/infractions/amendes brigade | Brigade assignée |
| **ROLE_AGENT** | 15 | Enregistrer contrôles/infractions/amendes, stats personnelles | Brigade + ses données |

---

**Total Implémenté :**
- ✅ 77+ routes
- ✅ 50+ templates
- ✅ 7 contrôleurs
- ✅ 7 services
- ✅ 12 repositories enrichis
- ✅ 0 erreurs PHP

---

*Document généré: 8 février 2026*  
*État: Production-Ready ✅*
### 3️⃣ Gestion des Brigades

| Fonctionnalité | Route | Méthode | Template | Description |
|---|---|---|---|---|
| 🚔 Lister brigades | `/admin/brigade/` | GET | `admin/brigade/index.html.twig` | Toutes les brigades (filtrable par région) |
| ➕ Créer brigade | `/admin/brigade/new` | GET/POST | `admin/brigade/new.html.twig` | Nouvelle brigade |
| 🔍 Voir détail brigade | `/admin/brigade/{id}` | GET | `admin/brigade/show.html.twig` | Infos brigade, agents, contrôles |
| ✏️ Modifier brigade | `/admin/brigade/{id}/edit` | GET/POST | `admin/brigade/edit.html.twig` | Éditer nom, localité, chef |
| 🗑️ Supprimer brigade | `/admin/brigade/{id}/delete` | POST | - | Supprimer avec CSRF |
| 🟢/🔴 Actif/Inactif | `/admin/brigade/{id}/toggle` | POST | - | Toggle isActive |

**Champs Formulaire :**
- Code brigade (ex: `CKY-001`, `CKY-002`)
- Nom brigade
- Région (dropdown des 9 régions)
- Chef (nom)
- Email contact
- Téléphone
- Localité (ville/zone)
- Statut actif

**Filtres :**
- Filtrer par région 


conexion 
📧 Email : admin@police-routiere.gn
🔑 Mot de passe : Admin@123456

📧 Email : direction-generale@police-routiere.gn
🔑 Mot de passe : DG@123456

📧 Email : agent-cky-br1-1@police-routiere.gn
🔑 Mot de passe : Agent@123456

📧 Email : chef-cky-br1@police-routiere.gn
🔑 Mot de passe : Chef@123456

📧 Email : direction-cky@police-routiere.gn
🔑 Mot de passe : DR@123456
👤 Nom : Directeur Conakry