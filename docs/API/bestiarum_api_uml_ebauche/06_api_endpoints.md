# `06_api_endpoints.md`

````markdown
# 🧠 Spécification d’API — Bestiarum

## 0) Conventions
- **Base** : `/api/v1`
- **Format** : `application/json` (sauf upload image : `multipart/form-data`)
- **Auth** : `Authorization: Bearer <JWT>` (chercheur, admin) ; `X-API-Key: <key>` pour systèmes tiers
- **Pagination** : `?page=1&per_page=20` + en-têtes `X-Total-Count`, `Link`
- **Tri** : `?sort=champ:asc,autre:desc`
- **Erreur JSON** :
```json
{ "error": { "code": "RESOURCE_NOT_FOUND", "message": "Creature not found" } }
````

---

## 1) Authentification

### POST `/auth/login`

* **Accès** : public
* **Body** :

```json
{ "email": "alice@bestiarum.org", "password": "Secret123!" }
```

* **Réponses** :

  * `200` :

    ```json
    { "access_token": "<jwt>", "refresh_token": "<refresh>", "expires_in": 3600, "role": "chercheur" }
    ```
  * `401` identifiants invalides
* **Critère de réussite** : JWT signé, rôle cohérent.

### POST `/auth/refresh`

* **Body** :

```json
{ "refresh_token": "<refresh>" }
```

* **Réponses** : `200` nouveau JWT ; `401` token invalide/expiré.

> **Création d’utilisateur** : via endpoints Admin (voir §4.4).

---

## 2) Ressource `creatures`

### GET `/creatures`

* **Accès** : public
* **Query** : `q` (texte), `type`, `region`, `habitat`, `dangerosite_min`, `dangerosite_max`, `page`, `per_page`, `sort`
* **Comportement** : renvoie **uniquement** `est_validee=true` pour public ; si **auth admin**, peut forcer `?all=true`.
* **200** :

```json
{
  "items": [
    {
      "id": 12, "nom": "Dragon des Monts de Brume",
      "type": { "id": 1, "libelle": "Dragon" },
      "dangerosite": 5,
      "habitats": [ {"id":2,"nom":"Grottes"} ],
      "regions": [ {"id":3,"nom":"Montagnes"} ],
      "est_validee": true, "cree_le": "2025-10-01T12:30:22Z"
    }
  ],
  "page": 1, "per_page": 20, "total": 47
}
```

* **Erreurs** : `400` (filtres invalides), `500`.

### GET `/creatures/{id}`

* **Accès** : public si `est_validee=true`; sinon auteur/admin.
* **200** :

```json
{
  "id": 15, "nom": "Griffon du Nord", "description": "…",
  "type": {"id":2,"libelle":"Hybride"},
  "dangerosite": 4, "alimentation":"carnivore",
  "habitats":[{"id":1,"nom":"Falaises"}],
  "regions":[{"id":2,"nom":"Nord"}],
  "est_validee": true, "auteur":{"id":7,"nom":"Alice"}, "cree_le":"2025-10-03T08:20:00Z"
}
```

* **Erreurs** : `404`.

### POST `/creatures`

* **Accès** : `chercheur|admin` (JWT)
* **Body** *(exemple minimal viable)* :

```json
{
  "nom": "Griffon du Nord",
  "type_id": 2,
  "dangerosite": 4,
  "alimentation": "carnivore",
  "description": "Hybride majestueux…",
  "regions": [1,3],
  "habitats": [2]
}
```

* **Règles** :

  * `nom` unique, `dangerosite` 1..5, `regions/habitats` non vides.
* **201** :

```json
{ "id": 15, "est_validee": false, "location": "/api/v1/creatures/15" }
```

* **Erreurs** : `400|422`, `401`, `409` (nom dupliqué).

### PUT `/creatures/{id}`

* **Accès** : auteur (si non validée) ou admin
* **Body** : mêmes champs que POST (partiels acceptés si `PATCH` implémenté)
* **200** : ressource mise à jour
* **Erreurs** : `401`, `403` (pas auteur), `404`, `409` (déjà validée).

### DELETE `/creatures/{id}`

* **Accès** : admin
* **204** : supprimée
* **Erreurs** : `401`, `403`, `404`.

### PUT `/creatures/{id}/validate`

* **Accès** : admin
* **Body (option)** :

```json
{ "commentaire": "Sources vérifiées." }
```

* **200** :

```json
{ "id": 15, "est_validee": true, "validated_at": "2025-10-10T10:11:12Z" }
```

* **Erreurs** : `401`, `404`, `409` (déjà validée).

### PUT `/creatures/{id}/reject`

* **Accès** : admin
* **Body** :

```json
{ "motif": "Données insuffisantes." }
```

* **200** :

```json
{ "id": 15, "est_validee": false, "state": "REJECTED" }
```

* **Erreurs** : `401`, `404`.

### GET `/creatures/pending`

* **Accès** : admin
* **200** : liste des fiches `est_validee=false`.

### POST `/creatures/{id}/image` *(option bonus)*

* **Accès** : auteur/admin
* **Type** : `multipart/form-data` (champ `file`)
* **201** : `{ "media_id": 9, "url": "…" }`
* **Erreurs** : `400`, `401`, `403`, `404`.

---

## 3) Référentiels (types, habitats, régions)

### GET `/types` — public

* **200** :

```json
{ "items": [ { "id":1, "libelle":"Dragon" }, { "id":2, "libelle":"Hybride" } ] }
```

### POST `/types` — `chercheur|admin`

* **Body** :

```json
{ "libelle": "Esprit", "description": "Entité immatérielle..." }
```

* **201** : créé
* **Erreurs** : `400|422`, `401`, `409` (doublon libellé)

### PUT `/types/{id}` — `admin` (ou modération)

* **200** : mis à jour
* **Erreurs** : `401`, `403`, `404`.

> **Habitats** et **Régions** suivent les mêmes patterns :

* `GET /habitats` (public), `POST /habitats` (chercheur/admin), `PUT/DELETE /habitats/{id}` (admin),
* `GET /regions`, `POST /regions`, `PUT/DELETE /regions/{id}`.

**Exemples de body :**

```json
// POST /habitats
{ "nom": "Grottes", "biome": "souterrain" }

// POST /regions
{ "nom": "Montagnes", "climat": "alpin" }
```

---

## 4) Utilisateurs (administration)

### GET `/users` — admin

* **Filtres** : `role`, `actif`
* **200** : liste paginée

### POST `/users` — admin

* **Body** :

```json
{ "nom": "Alice", "email": "alice@bestiarum.org", "password": "Secret123!", "role": "chercheur" }
```

* **201** : créé
* **Erreurs** : `400|422`, `409` (email unique)

### GET `/users/{id}` — admin ou **self**

* **200** : détails
* **403** : si autre utilisateur et non admin

### PUT `/users/{id}` — admin ou **self** (champs autorisés)

* **200** : mis à jour
* **Erreurs** : `403`, `404`.

### DELETE `/users/{id}` — admin

* **204** : supprimé

---

## 5) Export Aventuriers

### GET `/export/creatures`

* **Accès** : clé API (header `X-API-Key`)
* **Query** : `dangerosite_min`, `dangerosite_max`, `region`, `type`, `limit` (max 1000)
* **200** :

```json
{
  "export_date": "2025-10-10T09:00:00Z",
  "filters": { "dangerosite_min": 3, "region": 2 },
  "count": 5,
  "creatures": [
    { "id": 12, "nom": "Dragon des Monts de Brume", "dangerosite": 5, "regions": [3] },
    { "id": 34, "nom": "Chimère des Sables", "dangerosite": 3, "regions": [5] }
  ]
}
```

* **Erreurs** : `403` (clé invalide), `400` (filtres), `429` (quota).

---

## 6) Critères de réussite (par endpoint)

* **GET /creatures** : respecte filtres/pagination; exclut les non validées côté public; codes `200|400|500`.
* **POST /creatures** : `201` + ressource créée; validation stricte; pas de doublon `nom`.
* **PUT /creatures/{id}** : auteur/admin; `409` si déjà validée.
* **PUT /creatures/{id}/validate|reject** : admin; écrit dans `validations`.
* **Référentiels** : `409` sur doublons; lecture publique.
* **Auth** : `200` avec JWT signé; refresh opérationnel.
* **Export** : clé contrôlée; filtres appliqués; cap sur `limit`.

---

## 7) Schémas JSON (validation indicative)

```json
// CreatureCreate
{
  "type": "object",
  "required": ["nom", "type_id", "dangerosite", "alimentation", "regions", "habitats"],
  "properties": {
    "nom": { "type": "string", "minLength": 3, "maxLength": 160 },
    "type_id": { "type": "integer", "minimum": 1 },
    "dangerosite": { "type": "integer", "minimum": 1, "maximum": 5 },
    "alimentation": { "type": "string", "enum": ["herbivore","carnivore","omnivore","autre"] },
    "description": { "type": "string" },
    "regions": { "type": "array", "items": { "type": "integer", "minimum": 1 }, "minItems": 1 },
    "habitats": { "type": "array", "items": { "type": "integer", "minimum": 1 }, "minItems": 1 }
  }
}
```
