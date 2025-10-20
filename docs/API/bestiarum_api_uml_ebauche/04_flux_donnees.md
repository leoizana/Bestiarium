# 🔄 Flux & états — Bestiarum

## 1) Machine à états — Fiche créature
```plantuml
@startuml
[*] --> DRAFT : création (POST par Chercheur)
DRAFT --> DRAFT : modifications (PUT par Auteur)
DRAFT --> SUBMITTED : soumission (option) / auto à la création
SUBMITTED --> VALIDATED : validation (Admin)
SUBMITTED --> REJECTED : refus (Admin, motif)
VALIDATED --> ARCHIVED : archivage (Admin) [option]
REJECTED --> DRAFT : reprise/édition (Auteur)
@enduml
```

**Politique v1 simplifiée** : création = `SUBMITTED` directement; l’auteur peut éditer jusqu’à validation.

---

## 2) Séquence — Création puis validation

```plantuml
@startuml
actor Chercheur as C
actor Administrateur as A
participant API
database DB

C -> API : POST /api/v1/creatures {json}
API -> DB : INSERT creature(est_validee=false, auteur_id=C)
DB --> API : id
API --> C : 201 {id, est_validee=false}

A -> API : GET /api/v1/creatures/pending
API -> DB : SELECT * WHERE est_validee=false
API --> A : 200 [ ... ]

A -> API : PUT /api/v1/creatures/{id}/validate
API -> DB : UPDATE creature SET est_validee=true; INSERT validation(...)
API --> A : 200 {est_validee=true}
@enduml
```

---

## 3) Séquence — Consultation publique

```plantuml
@startuml
actor Visiteur as V
participant API
database DB

V -> API : GET /api/v1/creatures?dangerosite=3&page=1
API -> DB : SELECT ... WHERE est_validee=true AND dangerosite=3 LIMIT 20 OFFSET 0
DB --> API : rows
API --> V : 200 { items, pagination }
@enduml
```

---

## 4) Séquence — Export Aventuriers

```plantuml
@startuml
actor "Système Aventuriers" as S
participant API
database DB

S -> API : GET /api/v1/export/creatures (X-API-Key)
API -> API : Vérifier clé
API -> DB : SELECT validées + filtres
DB --> API : dataset
API --> S : 200 {export_date, count, creatures[]}
@enduml
```

---

## 5) Erreurs & politique d’accès

* **401** si JWT manquant/invalide (endpoints protégés),
* **403** si rôle insuffisant,
* **404** si ressource inexistante ou non autorisée,
* **409** si état incompatible (ex : déjà validée),
* **422** si validation de champs échoue (option vs 400),
* **429** si limite de débit atteinte (option).

---

## 6) Métadonnées d’API

* **Pagination** : `?page=1&per_page=20`; en-têtes `X-Total-Count`, `Link`.
* **Tri** : `?sort=nom:asc` (multi-colonnes possible).
* **Filtrage** : `?type=Dragon&region=2&dangerosite_min=3&dangerosite_max=5`.
* **Dates** : ISO 8601 UTC.
* **Erreurs JSON** :

```json
{ "error": { "code": "VALIDATION_ERROR", "message": "dangerosite must be 1..5", "details": { "dangerosite": ["min 1", "max 5"] } } }
```
