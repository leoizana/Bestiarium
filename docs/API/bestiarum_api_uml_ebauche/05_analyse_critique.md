# 🧠 Analyse critique & travaux à rendre

## 1) Questions de modélisation
- **Type unique vs multi-types** : faut-il autoriser plusieurs `TypeCreature` par fiche ? Impacts MCD/MLD, endpoints, UI ?
- **Historique d’édition** : conserver un journal des modifications (qui/quand/quoi) ?
- **Taxonomie** : ajouter famille/genre/espèce ? Graphe hiérarchique ?
- **Images / médias** : stocker où (objet blob, S3-like, table medias) ? Métadonnées ?
- **Visibilité** : faut-il un champ `visibilite` (public/internal/draft) au lieu de booléen ?

## 2) Sécurité & accès
- Politique « l’auteur peut voir/éditer **ses** brouillons, pas ceux des autres » : cas limites ?
- JWT : durée de vie, refresh, révocation ?
- Clé API pour systèmes tiers : rotation, périmètre, quotas ?
- Rôle `chercheur` peut-il créer habitats/régions/types sans validation ? Processus de modération ?

## 3) Performance & scalabilité
- Indexes à prévoir (nom, dangerosité, est_validee, type_id).
- Pagination par curseur vs offset ?
- Mise en cache de `/creatures` publiques (ETag/Cache-Control) ?

## 4) Qualité & validation
- Normaliser les erreurs (codes applicatifs).
- Scénarios d’erreurs attendus (400/401/403/404/409/422/500).
- Jeux de données de démo (seed) pour les tests.

## 5) Travaux à rendre (proposés)
1. **Diagrammes UML** : Use cases, classes, séquences (au moins 2).
2. **MCD/MLD** : version initiale puis révisée après critique.
3. **Spécification API** : endpoints + JSON I/O + erreurs.
4. **Plan de tests** : table de couverture (UC ↔ endpoints), cas positifs/négatifs.
5. **Discussion critique** : 1 page sur arbitrages (sécurité, perf, évolutivité).

## 6) Rubrique d’évaluation (extrait)
- Cohérence modèle/API (30%),
- Qualité des diagrammes & justifications (25%),
- Exhaustivité des endpoints & erreurs (25%),
- Pertinence des cas de test (20%).