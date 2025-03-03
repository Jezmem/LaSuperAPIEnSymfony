# LaSuperApiSymfony - Gestion des Jeux Vidéo et Newsletter avec une API REST

## Description
Ce projet Symfony est une plateforme de gestion des jeux vidéo permettant d'afficher les jeux disponibles et leurs informations, ainsi qu'un système de newsletter envoyant les sorties de jeux à venir aux utilisateurs abonnés.

## Fonctionnalités
- **Gestion des Jeux Vidéo** :
  - Ajout, modification et suppression des jeux.
  - Filtrage des jeux par catégorie et éditeur.
  - Affichage des jeux à venir.
- **Gestion des Editeurs** :
  - Ajout, modification et suppression des éditeurs de jeux vidéo.
- **Gestion des Categories** :
  - Ajout, modification et suppression des catégories de jeux vidéo.
- **Newsletter** :
  - Envoi automatique chaque lundi à 8h30 aux abonnés.
  - Contenu généré dynamiquement avec les sorties des 7 prochains jours.
- **Sécurité et Utilisateurs** :
  - Authentification des utilisateurs.
  - Gestion des rôles.
  - Inscription avec option d'abonnement à la newsletter.

## Installation
### Prérequis
- PHP 8.1+
- Composer
- Symfony CLI
- Base de données MySQL ou PostgreSQL
- Mailtrap pour les e-mails en environnement de développement

### Étapes
1. Cloner le projet :
   ```bash
   git clone https://github.com/Jezmem/LaSuperAPIEnSymfony
   cd LaSuperAPIEnSymfony
   ```
2. Installer les dépendances :
   ```bash
   composer install
   ```
3. Configurer les variables d'environnement :
   ```bash
   cp .env.example .env
   ```
   - Modifier `.env` pour adapter la connexion à la base de données.
   - Configurer Mailer pour Mailtrap :
     ```env
     MAILER_DSN=smtp://username:password@smtp.mailtrap.io:2525
     ```
     - **Connexion à la base de données** :
     ```env
     DATABASE_URL="YOUR_DATABSE_URL"
     ```
   
   - **Secret de l'application** :
     ```env
     APP_SECRET=YOUR_APP_SECRET
     ```
   
   - **JWT pour l'authentification** :
     ```env
     JWT_SECRET_KEY=YOUR_JWT_SECRET_KEY
     JWT_PUBLIC_KEY=YOUR_JWT_PUBLIC_KEY
     JWT_PASSPHRASE=YOUR_PASS_PHRASE
     ```
4. Générer les clés et la base de données :
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```
5. Lancer le serveur Symfony :
   ```bash
   symfony server:start
   ```

## Utilisation
- Accéder à l'application via : `http://127.0.0.1:8000`
- Inscription et abonnement à la newsletter.
- Accéder aux jeux disponibles et leurs détails.
- Lancer la commande manuelle de la newsletter :
  ```bash
  php bin/console app:send-newsletter
  ```
- Consommer les messages envoyés en asynchrone :
  ```bash
  php bin/console messenger:consume async
  ```

## Planification CRON
La newsletter est envoyée automatiquement chaque lundi à 8h30 grâce à `symfony/scheduler` et grâce à l'annotation `#[AsCronTask('30 8 * * 1')]` :

## Technologies Utilisées
- Symfony 7
- Doctrine ORM
- Twig pour les templates
- Symfony Messenger 
- Mailtrap pour les tests d'e-mail
- Bootstrap pour le frontend du template du mail

