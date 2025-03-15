git checkout c796c816b7448b9eae0401746a5f7a42e785e05d# Digi-3 - Système de Gestion de Projets

Digi-3 est une application web de gestion de projets développée avec Symfony 7.2 et PHP 8.2. Elle permet de gérer des projets, des tâches, des clients et des utilisateurs avec un système de permissions avancé et une interface optimisée pour la productivité.

## Fonctionnalités

- **Gestion des utilisateurs** : Création, modification et suppression d'utilisateurs avec différents rôles (Admin, Chef de projet, Lead développeur, Développeur, etc.)
- **Gestion des projets** : Création, modification et suivi de l'état d'avancement des projets
- **Gestion des tâches** : Création, modification et suivi des tâches associées aux projets
- **Gestion des clients** : Gestion des informations clients associés aux projets
- **Système de permissions** : Contrôle d'accès basé sur les rôles avec une hiérarchie de permissions
- **Tableau de bord** : Vue d'ensemble des projets et tâches en cours

## Prérequis

- PHP 8.2 ou supérieur
- Composer
- MySQL 8.0 ou supérieur
- Node.js et npm (pour les assets)
- Symfony CLI (recommandé pour le développement)

## Installation de l'application en local

1. Cloner le dépôt :
   ```bash
   git clone https://github.com/votre-utilisateur/digi-3.git
   cd digi-3
   ```

2. Installer les dépendances PHP :
   ```bash
   composer install
   ```

3. Installer les dépendances JavaScript et build les assets :
   ```bash
   npm install
   npx encore dev
   ```

4. Configurer la base de données dans le fichier `.env.local` :
   ```
   DATABASE_URL="mysql://user:password@127.0.0.1:3306/digi3?serverVersion=8.0"
   ```

5. Créer la base de données et appliquer les migrations :
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   php bin/console cache:clear
   ```

6. Charger les fixtures si besoin (données de test) :
   ```bash
   php bin/console doctrine:fixtures:load
   ```

7. Démarrer le serveur de développement :
   ```bash
   symfony server:start
   ```

## Structure du projet

- `src/Controller/` : Contrôleurs de l'application
- `src/Entity/` : Entités Doctrine (modèles de données)
- `src/Repository/` : Repositories Doctrine pour l'accès aux données
- `src/Service/` : Services métier
- `src/Form/` : Formulaires
- `src/Security/` : Classes liées à la sécurité
- `src/Enum/` : Énumérations PHP 8.2+
- `templates/` : Templates Twig
- `public/` : Fichiers publics (CSS, JS, images)
- `config/` : Fichiers de configuration
- `migrations/` : Migrations de base de données

## Système de sécurité

Digi-3 utilise un système de sécurité avancé basé sur les Voters de Symfony pour gérer les permissions de manière granulaire.

### Voters

Les Voters sont des classes qui déterminent si un utilisateur a le droit d'effectuer une action spécifique sur une ressource donnée.

#### **ProjectVoter** (Permissions sur les projets)
- `view` : Voir un projet
- `edit` : Modifier un projet
- `delete` : Supprimer un projet
- `create` : Créer un projet
- `manage_tasks` : Gérer les tâches d'un projet

#### **TaskVoter** (Permissions sur les tâches)
- `view` : Voir une tâche
- `edit` : Modifier une tâche
- `delete` : Supprimer une tâche
- `create` : Créer une tâche
- `change_status` : Changer le statut d'une tâche
- `assign` : Assigner une tâche à un utilisateur

#### **CustomerVoter** (Permissions sur les clients)
- `view` : Voir un client
- `edit` : Modifier un client
- `delete` : Supprimer un client
- `create` : Créer un client

#### **UserVoter** (Permissions sur les utilisateurs)
- `view` : Voir un utilisateur
- `edit` : Modifier un utilisateur
- `delete` : Supprimer un utilisateur
- `create` : Créer un utilisateur
- `change_role` : Changer le rôle d'un utilisateur

### Vérification des permissions dans les contrôleurs

```php
$this->denyAccessUnlessGranted('view', $project);
$this->denyAccessUnlessGranted('create', null);
```

## Utilisateurs par défaut

| Email | Mot de passe | Rôle |
|-------|-------------|------|
| admin@digiworks.fr | Admin123! | Administrateur |
| responsable@digiworks.fr | Responsable123! | Chef de projet |
| pm@digiworks.fr | ProjectManager123! | Chef de projet |
| lead@digiworks.fr | LeadDev123! | Lead développeur |
| dev@digiworks.fr | Dev123! | Développeur |
| user@digiworks.fr | User123! | Utilisateur standard |

## Conventions de code

- **PSR-1, PSR-2 et PSR-4**
- **Utilisation des attributs PHP 8** pour les annotations
- **Utilisation des énumérations PHP 8.2+** pour les types énumérés
- **Injection de dépendances via le constructeur**
- **Nommage standardisé** :
  - `PascalCase` : Pour les classes (`ProjectController`)
  - `camelCase` : Pour les méthodes et variables (`getProjectById()`, `$taskStatus`)
  - `SCREAMING_SNAKE_CASE` : Pour les constantes (`MAX_TASKS_PER_PROJECT`)
- **Séparation des responsabilités** :
  - `src/Controller/` : Contrôleurs
  - `src/Service/` : Services métier
  - `src/Repository/` : Repositories
- **Gestion des erreurs** :
  - Exceptions spécifiques (`ProjectNotFoundException`)
  - Gestion des erreurs avec `try/catch`

## Tests

Pour exécuter les tests :

```bash
php bin/phpunit
```

## Déploiement

1. Configurer les variables d'environnement pour la production dans `.env`
2. Optimiser l'autoloader :
   ```bash
   composer dump-autoload --optimize --no-dev --classmap-authoritative
   ```
3. Vider le cache :
   ```bash
   APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear
   ```
4. Exécuter les migrations :
   ```bash
   APP_ENV=prod php bin/console doctrine:migrations:migrate
   ```

## Licence

Ce projet est sous licence propriétaire. Tous droits réservés.

## Contact

Pour toute question ou suggestion, contactez l'équipe de développement :
**dev@digiworks.fr**