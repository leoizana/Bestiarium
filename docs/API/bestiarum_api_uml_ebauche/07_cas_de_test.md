# 🧪 Cas de test — Bestiarum API

## 0) Stratégie & prérequis
- **Données seed** : 2 types (`Dragon`, `Hybride`), 3 habitats (`Grottes`, `Falaises`, `Marais`), 3 régions (`Montagnes`, `Nord`, `Désert`), 1 admin, 1 chercheur.
- **Comptes** :
  - admin: admin@bestiarum.org / Admin#2025
  - chercheur: alice@bestiarum.org / Secret123!
- **En-têtes** :
  - `Authorization: Bearer <JWT>` (pour endpoints protégés)
  - `Content-Type: application/json`

---

## 1) Matrice de couverture (UC ↔ Endpoints)
| UC | Endpoints principaux |
|---|---|
| UC1 Liste | GET /creatures |
| UC2 Recherche | GET /creatures?type=...&region=... |
| UC3 Détail | GET /creatures/{id} |
| UC4 Créer | POST /creatures |
| UC5 Modifier | PUT /creatures/{id} |
| UC6 En attente | GET /creatures/pending |
| UC7 Valider/Refuser | PUT /creatures/{id}/validate`, `PUT /creatures/{id}/reject` |
| UC8 Référentiels | GET/POST/PUT/DELETE /types, /habitats, /regions |
| UC9 Export | GET /export/creatures |
| UC10 Auth | POST /auth/login, POST /auth/refresh |

---

## 2) Scénarios positifs

### S1 — Login chercheur
**POST** `/auth/login`
```json
{ "email": "alice@bestiarum.org", "password": "Secret123!" }
````

**Attendu** : `200` + `access_token` (rôle `chercheur`).

### S2 — Création d’une créature (chercheur)

**POST** `/creatures`

```json
{
  "nom": "Griffon du Nord",
  "type_id": 2,
  "dangerosite": 4,
  "alimentation": "carnivore",
  "description": "Hybride majestueux…",
  "regions": [2],
  "habitats": [2]
}
```

**Attendu** : `201` + `{ "id": X, "est_validee": false }`.

### S3 — Liste des fiches en attente (admin)

**GET** `/creatures/pending`
**Attendu** : `200` + contient la fiche « Griffon du Nord ».

### S4 — Validation par admin

**PUT** `/creatures/{X}/validate`

```json
{ "commentaire": "Sources vérifiées." }
```

**Attendu** : `200` + `est_validee=true` + trace en `validations`.

### S5 — Consultation publique filtrée

**GET** `/creatures?dangerosite_min=4&type=Hybride`
**Attendu** : `200` + inclus « Griffon du Nord ».

### S6 — CRUD référentiels (chercheur crée, admin modère)

1. **POST** `/habitats`

```json
{ "nom": "Plateaux venteux", "biome": "steppe" }
```

**Attendu** : `201` (chercheur).
2. **PUT** `/habitats/{id}`

```json
{ "biome": "steppe froide" }
```

**Attendu** : `200` (admin).

### S7 — Export Aventuriers (clé API)

**GET** `/export/creatures?dangerosite_min=3&region=2`
**Headers** : `X-API-Key: abc123`
**Attendu** : `200` + dataset filtré.

---

## 3) Scénarios négatifs

### N1 — Création invalide (dangerosité hors plage)

**POST** `/creatures`

```json
{
  "nom": "Test",
  "type_id": 1,
  "dangerosite": 6,
  "alimentation": "herbivore",
  "regions": [1],
  "habitats": [1]
}
```

**Attendu** : `422` (ou `400`) + message sur `dangerosite`.

### N2 — Doublon de nom

**POST** `/creatures`

```json
{ "nom": "Griffon du Nord", "type_id": 2, "dangerosite": 4, "alimentation": "carnivore", "regions":[2], "habitats":[2] }
```

**Attendu** : `409` (nom unique).

### N3 — Modification non autorisée (autre auteur)

**PUT** `/creatures/{X}`
**Attendu** : `403`.

### N4 — Accès sans JWT

**POST** `/creatures` (sans header)
**Attendu** : `401`.

### N5 — Détail d’une fiche non validée par un visiteur

**GET** `/creatures/{id_non_valide}`
**Attendu** : `404` (ou `403` selon politique).

### N6 — Validation d’une fiche déjà validée

**PUT** `/creatures/{X}/validate`
**Attendu** : `409`.

### N7 — Export sans clé API

**GET** `/export/creatures`
**Attendu** : `403`.

---

## 4) Données d’exemple (seed)

```json
{
  "types": [
    { "id": 1, "libelle": "Dragon" },
    { "id": 2, "libelle": "Hybride" }
  ],
  "habitats": [
    { "id": 1, "nom": "Grottes", "biome": "souterrain" },
    { "id": 2, "nom": "Falaises", "biome": "littoral" },
    { "id": 3, "nom": "Marais", "biome": "humide" }
  ],
  "regions": [
    { "id": 1, "nom": "Montagnes", "climat": "alpin" },
    { "id": 2, "nom": "Nord", "climat": "froid" },
    { "id": 3, "nom": "Désert", "climat": "aride" }
  ],
  "utilisateurs": [
    { "id": 1, "nom": "Admin", "email": "admin@bestiarum.org", "role": "admin" },
    { "id": 2, "nom": "Alice", "email": "alice@bestiarum.org", "role": "chercheur" }
  ]
}
```

---

## 5) Critères d’acceptation (exemples)

* **CA1** : Toute création invalide renvoie `422` avec `details` par champ.
* **CA2** : Les listes publiques n’incluent **jamais** les fiches non validées.
* **CA3** : La validation crée une **entrée** en table `validations`.
* **CA4** : Les endpoints protégés exigent un **JWT** (ou clé API) valide.
* **CA5** : Pagination : en-têtes `X-Total-Count` et `Link` sont présents.