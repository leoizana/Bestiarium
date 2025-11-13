# Bestiarium

API PHP permettant de créer des créatures, de les associer à des joueurs et d'organiser des combats automatisés. Le projet s'appuie sur MySQL pour la persistance et sur le service Pollinations pour générer des statistiques ou des illustrations quand elles ne sont pas fournies.

## Prérequis
- PHP 8.2+
- Composer 2+
- MySQL 8 (port 3307 par défaut dans `BD/connect.php`)
- Accès réseau sortant vers `text.pollinations.ai` et `image.pollinations.ai`

## Installation
1. Cloner le dépôt puis installer les dépendances PHP :
   ```bash
   git clone https://github.com/leoizana/Bestiarium.git
   cd Bestiarium
   composer install
   ```
2. Créer une base `bestarium` avec les tables `user`, `bestiarium` et `combat`.
Toutes informations sont disponibles tout en bas du README, avec des liens permettant d'accéder aux différents documents.

## Configuration
1. Ajuster `BD/connect.php` :
   - `SERVER`, `BASE`, `USER`, `PASSWD` pour pointer vers votre MySQL
   - `SECRET_KEY` (clé utilisée pour signer les JWT)
   - `OPENROUTER_API_KEY` ou une clé Pollinations compatible si vous souhaitez personnaliser les appels IA
2. Vérifier que `vendor/autoload.php` est disponible (généré par Composer).

## Lancement
Vous pouvez exécuter l'API avec le serveur PHP intégré :
```bash
php -S localhost:8000
```
L'index détecte automatiquement les chemins `/bestiarium`, `/user` et `/matchs` et délègue aux contrôleurs correspondants.

## Informations d'accès
- **Base URL locale** : `http://localhost:8000`
- **Authentification** :
  - Création d'un compte via `POST /user/create`
  - Connexion via `POST /user/login` qui retourne un JWT signé (`HS256`)
  - Les routes protégées (`/bestiarium/create`, `/matchs/create`, `/user/me`) attendent l'en-tête `Authorization: Bearer <token>`


Documentation techniques : 
https://docs.google.com/document/d/12QXjA_P71ww6V-YTv5nSfa_k_Acbm-fjhPlU9sgVk1w/edit?usp=sharing

Documentation utilisateur :
https://docs.google.com/document/d/1iobfuVZNQviNbvIOB6_-3ivtoUzxeFbBVpGWdMK_I9E/edit?usp=sharing