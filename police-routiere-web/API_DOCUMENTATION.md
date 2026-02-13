# Documentation API - Police Routière

## Endpoints principaux

### Authentication
- `POST /api/auth/login` - Connexion
- `POST /api/auth/logout` - Déconnexion
- `POST /api/auth/refresh` - Renouveler token

### Controls
- `GET /api/v1/controls` - Liste des contrôles (paginé)
- `GET /api/v1/controls/{id}` - Détail d'un contrôle
- `POST /api/v1/controls` - Créer un contrôle (ROLE_AGENT)
- `PUT /api/v1/controls/{id}` - Modifier un contrôle (ROLE_AGENT)
- `DELETE /api/v1/controls/{id}` - Supprimer (ROLE_ADMIN)

### Infractions
- `GET /api/v1/infractions` - Liste paginée
- `GET /api/v1/infractions/{id}` - Détail
- `POST /api/v1/infractions` - Créer (ROLE_AGENT)
- `GET /api/v1/infractions/search?code=CODE` - Rechercher par code

### Amendes
- `GET /api/v1/amendes` - Liste (filtres: status, brigade)
- `GET /api/v1/amendes/{id}` - Détail
- `POST /api/v1/amendes` - Créer (ROLE_CHEF_BRIGADE)
- `PUT /api/v1/amendes/{id}/status` - Mettre à jour le statut
- `POST /api/v1/amendes/{id}/pay` - Enregistrer un paiement

### Statistics
- `GET /api/v1/statistics` - Stats globales
- `GET /api/v1/statistics/regional` - Stats par région
- `GET /api/v1/statistics/brigade/{brigadeId}` - Stats brigade
- `GET /api/v1/statistics/daily` - Stats quotidiennes

### Reports
- `GET /api/v1/reports/monthly` - Rapport mensuel
- `GET /api/v1/reports/regional` - Rapport régional
- `GET /api/v1/reports/compliance` - Rapport conformité
- `GET /api/v1/reports/revenue` - Rapport de revenus

### Export
- `GET /api/v1/export/csv` - Export CSV
- `GET /api/v1/export/excel` - Export Excel
- `GET /api/v1/export/pdf` - Export PDF

## Format des réponses

### Succès (200/201)
```json
{
  "success": true,
  "data": {...},
  "timestamp": "2024-01-15T10:30:00Z"
}
```

### Erreur (400/401/403/500)
```json
{
  "success": false,
  "error": "Message d'erreur",
  "code": "ERROR_CODE",
  "timestamp": "2024-01-15T10:30:00Z"
}
```

## Pagination
Query params : `page=1&limit=50&sort=id&order=desc`

Response headers:
- `X-Total-Count: 1000`
- `X-Total-Pages: 20`
- `X-Current-Page: 1`

## Authentification
Header: `Authorization: Bearer <JWT_TOKEN>`

## Filtres disponibles

**Controls:**
- `?status=EFFECTUE,EN_COURS`
- `?brigade_id=123`
- `?date_from=2024-01-01&date_to=2024-01-31`
- `?region=CONAKRY`

**Infractions:**
- `?category=VITESSE,STATIONNEMENT`
- `?amount_min=50000&amount_max=200000`

**Amendes:**
- `?status=EN_ATTENTE,PAYEE`
- `?brigade_id=123`
- `?overdue=true` (Amendes en retard)

## Validations
Tous les montants en GNF (Franc Guinéen)
Dates en format ISO 8601 (YYYY-MM-DD)
Immatriculations: AA0000BB format

## Rate Limiting
100 requêtes par minute par authenticating user
1000 requêtes par heure pour les Admin

## CORS
Autorisé pour: https://police-routiere.gn, https://admin.police-routiere.gn
