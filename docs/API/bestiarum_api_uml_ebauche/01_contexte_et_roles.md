# 🧙‍♂️ Étude de cas — Bestiarum (Contexte & Rôles)

## 1) Objectif & périmètre
L’organisation **Bestiarum** veut concevoir une **API REST** qui centralise un bestiaire de créatures fantastiques pour :
- un site public (lecture),
- un back-office interne (gestion/validation),
- des systèmes tiers (ex : module d’entraînement pour aventuriers).

**Livrables attendus (phase conception) :**
- Use cases & maquettes de flux,
- Modèle de données (UML + MERISE),
- Spécification d’API (endpoints, règles, erreurs),
- Cas de test (positifs/negatifs).

**Périmètre inclus :**
- Gestion des fiches de créature,
- Gestion des habitats, régions, types,
- Processus de validation par un administrateur,
- Accès public aux fiches validées,
- Export filtré pour « Système Aventuriers ».

**Hors périmètre (v1) :**
- Gamification, captures, combats, quêtes,
- Paiements, abonnement,
- Traductions multilingues (option en extension).

---

## 2) Glossaire rapide
- **Fiche créature** : enregistrement décrivant une créature (nom, type, dangerosité, habitats…).
- **Validation** : action admin approuvant/refusant une fiche.
- **Habitat** : milieu de vie (grottes, marais…).
- **Région** : zone géographique (Nord, Montagnes…).
- **Type** : classification (Dragon, Esprit, Bête…).

---

## 3) Acteurs / Personas
| Acteur | Description | Motivations |
|---|---|---|
| **Visiteur** | Internaute anonyme | Consulter les créatures validées |
| **Chercheur** | Utilisateur authentifié qui crée/édite des fiches | Documenter de nouvelles créatures |
| **Administrateur** | Superviseur de la base | Garantir la qualité/fiabilité |
| **Système Aventuriers** | Consommateur machine-to-machine | Recevoir exports filtrés (dangerosité, régions…) |

---

## 4) Rôles & droits (RBAC simplifié)
Rôles implémentés dans le JWT : `chercheur`, `admin`.  
Les visiteurs n’ont pas de JWT (lecture publique seulement).

| Ressource / Action | Visiteur | Chercheur | Admin |
|---|:---:|:---:|:---:|
| Lister créatures validées | ✅ | ✅ | ✅ |
| Voir détails créature validée | ✅ | ✅ | ✅ |
| Créer fiche créature | ❌ | ✅ | ✅ |
| Modifier *sa* fiche non validée | ❌ | ✅ | ✅ |
| Voir fiches en attente | ❌ | ❌ | ✅ |
| Valider / Refuser fiche | ❌ | ❌ | ✅ |
| Supprimer fiche | ❌ | ❌ | ✅ |
| CRUD habitats / régions / types | ❌ | ✅ (création *proposée*) | ✅ (final) |
| Export Aventuriers | ❌ | ❌ | ✅ (ou clé API dédiée) |

---

## 5) Exigences non fonctionnelles
- **Sécurité** : JWT Bearer; clés API pour systèmes tiers; mots de passe hashés (bcrypt/argon2).
- **Disponibilité** : 99% cible; sauvegardes quotidiennes.
- **Performance** : listes paginées (par défaut 20), filtrage côté DB, indexes.
- **Traçabilité** : garder l’historique des validations; journaliser actions sensibles.
- **Qualité** : validation serveur des entrées; codes HTTP cohérents; erreurs JSON normalisées.
- **Portabilité** : PHP 8.2+, framework (Slim/Laravel/Symfony au choix); DB PostgreSQL/MySQL.

---

## 6) Règles métier clés
1. Une fiche **non validée** n’est pas visible publiquement (sauf par son auteur et l’admin).
2. Seul un **admin** peut **valider** ou **refuser** une fiche.
3. Une créature doit avoir **au moins un type** et **au moins un habitat**.
4. La **dangerosité** est un entier **1..5**.
5. Le **nom** de créature est **unique** (sens métier).
6. Suppression d’une fiche : interdite si référencée par des exports immuables (optionnel, selon implémentation).

---

## 7) Définition de prêt (« ready ») pour dev
- Use cases formalisés,
- Modèle conceptuel/logique validé,
- Endpoints décrits (entrée/sortie, erreurs),
- Cas de test définis + critères d’acceptation.