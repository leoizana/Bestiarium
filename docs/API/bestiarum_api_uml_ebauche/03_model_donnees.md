# 🧩 Modèle de données (MERISE + UML)

## 1) MCD (MERISE) — Vue conceptuelle
```mermaid
erDiagram
    UTILISATEUR ||--o{ FICHE_CREATURE : redige
    ADMINISTRATEUR ||--o{ VALIDATION : valide
    FICHE_CREATURE }o--|| TYPE_CREATURE : "a pour type"
    FICHE_CREATURE }o--o{ REGION : "vit dans"
    FICHE_CREATURE }o--o{ HABITAT : "se trouve dans"

    UTILISATEUR {
        int id_utilisateur PK
        string nom
        string email UNIQUE
        string hash_mdp
        string role  "enum: chercheur|admin"
        bool actif
        datetime cree_le
    }

    FICHE_CREATURE {
        int id_creature PK
        string nom UNIQUE
        text description
        int dangerosite "1..5"
        string alimentation "enum: herbivore|carnivore|omnivore|autre"
        boolean est_validee
        datetime cree_le
        int auteur_id FK
        int type_id FK
    }

    TYPE_CREATURE {
        int id_type PK
        string libelle UNIQUE
        string description
    }

    REGION {
        int id_region PK
        string nom UNIQUE
        string climat
    }

    HABITAT {
        int id_habitat PK
        string nom UNIQUE
        string biome
    }

    VALIDATION {
        int id_validation PK
        int creature_id FK
        int admin_id FK
        datetime date_validation
        string decision "enum: approved|rejected"
        text commentaire
    }

    CREATURE_REGION {
        int creature_id FK
        int region_id FK
    }

    CREATURE_HABITAT {
        int creature_id FK
        int habitat_id FK
    }
````

**Notes :**

* Relation 1..N **Utilisateur → Fiche** (auteur).
* 1..N **Type → Fiche** (une fiche a un type principal) — extension MN possible.
* MN **Fiche ↔ Régions** & **Fiche ↔ Habitats**.
* **Validation** journalise les décisions d’admin (historique).

---

## 2) MLD (MERISE) — Vue logique SQL (extrait)

```sql
CREATE TABLE utilisateurs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nom VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  hash_mdp VARCHAR(255) NOT NULL,
  role ENUM('chercheur','admin') NOT NULL DEFAULT 'chercheur',
  actif BOOLEAN NOT NULL DEFAULT TRUE,
  cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE types_creature (
  id INT PRIMARY KEY AUTO_INCREMENT,
  libelle VARCHAR(120) NOT NULL UNIQUE,
  description TEXT
);

CREATE TABLE regions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nom VARCHAR(120) NOT NULL UNIQUE,
  climat VARCHAR(120)
);

CREATE TABLE habitats (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nom VARCHAR(120) NOT NULL UNIQUE,
  biome VARCHAR(120)
);

CREATE TABLE creatures (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nom VARCHAR(160) NOT NULL UNIQUE,
  description TEXT,
  dangerosite TINYINT NOT NULL CHECK (dangerosite BETWEEN 1 AND 5),
  alimentation ENUM('herbivore','carnivore','omnivore','autre') NOT NULL,
  est_validee BOOLEAN NOT NULL DEFAULT FALSE,
  cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  auteur_id INT NOT NULL,
  type_id INT NOT NULL,
  FOREIGN KEY (auteur_id) REFERENCES utilisateurs(id),
  FOREIGN KEY (type_id) REFERENCES types_creature(id)
);

CREATE TABLE creature_region (
  creature_id INT NOT NULL,
  region_id INT NOT NULL,
  PRIMARY KEY (creature_id, region_id),
  FOREIGN KEY (creature_id) REFERENCES creatures(id) ON DELETE CASCADE,
  FOREIGN KEY (region_id) REFERENCES regions(id)
);

CREATE TABLE creature_habitat (
  creature_id INT NOT NULL,
  habitat_id INT NOT NULL,
  PRIMARY KEY (creature_id, habitat_id),
  FOREIGN KEY (creature_id) REFERENCES creatures(id) ON DELETE CASCADE,
  FOREIGN KEY (habitat_id) REFERENCES habitats(id)
);

CREATE TABLE validations (
  id INT PRIMARY KEY AUTO_INCREMENT,
  creature_id INT NOT NULL,
  admin_id INT NOT NULL,
  date_validation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  decision ENUM('approved','rejected') NOT NULL,
  commentaire TEXT,
  FOREIGN KEY (creature_id) REFERENCES creatures(id),
  FOREIGN KEY (admin_id) REFERENCES utilisateurs(id)
);

CREATE INDEX idx_creatures_validee ON creatures(est_validee);
CREATE INDEX idx_creatures_dangerosite ON creatures(dangerosite);
```

---

## 3) UML — Diagramme de classes (PlantUML)

```plantuml
@startuml
class Utilisateur {
  +id: int
  +nom: string
  +email: string
  -hashMdp: string
  +role: Role
  +actif: bool
  +creeLe: DateTime
  +peutValider(): bool
}

enum Role { chercheur; admin }

class Creature {
  +id: int
  +nom: string
  +description: text
  +dangerosite: int
  +alimentation: string
  +estValidee: bool
  +creeLe: DateTime
  +type: TypeCreature
  +auteur: Utilisateur
  +habitats: List<Habitat>
  +regions: List<Region>
  +estVisiblePublique(): bool
}

class TypeCreature {
  +id: int
  +libelle: string
  +description: text
}

class Region {
  +id: int
  +nom: string
  +climat: string
}

class Habitat {
  +id: int
  +nom: string
  +biome: string
}

class Validation {
  +id: int
  +dateValidation: DateTime
  +decision: string
  +commentaire: text
  +creature: Creature
  +admin: Utilisateur
}

Utilisateur "1" -- "0..*" Creature : auteur
TypeCreature "1" -- "0..*" Creature : type
Creature "1" -- "0..*" Validation
Creature "0..*" -- "0..*" Region
Creature "0..*" -- "0..*" Habitat
@enduml
```

---

## 4) Contraintes et règles

* **nom** de créature unique (index + contrainte).
* **dangerosite** ∈ [1..5].
* Au moins **1 habitat**; au moins **1 région**.
* **Validation** requise pour visibilité publique.