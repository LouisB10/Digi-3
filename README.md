# Digi-3 - Système de Gestion de Projets

Digi-3 est une application web de gestion de projets développée avec Symfony 7.2 et PHP 8.2. Elle permet de gérer des projets, des tâches, des clients et des utilisateurs avec un système de permissions avancé et une interface optimisée pour la productivité.

## Fonctionnalités

## Liste des fonctionnalités disponibles dans l’application

1. **Authentification et Gestion des Utilisateurs**
   - Accéder à la page de connexion via le bouton “Se connecter” en haut à droite.
   - Choisir entre s’inscrire ou se connecter avec un email et un mot de passe.
   - Possibilité de se connecter avec différents rôles (Admin, Manager, Utilisateur, etc.).
   - Utilisation de la commande Red amis pour obtenir des utilisateurs par défaut avec tous les rôles configurés.

2. **Tableau de Bord (Dashboard)**
   - Après connexion, accès au dashboard principal affichant les informations clés.
   - Navigation vers les différentes sections de l’application.

3. **Gestion des Projets (Accessible aux Admins et Managers)**
   - Accès à la page “Gestion de projet”.
   - Création d’un projet via le bouton “Créer un projet”.
   - Affichage de la liste des projets existants.
   - Sélection d’un projet pour afficher ses détails.

4. **Gestion des Tâches (Dans un projet spécifique)**
   - Ajout d’une nouvelle tâche via le bouton “Ajouter une tâche”.
   - Déplacement des tâches entre différentes colonnes (statuts).
   - Création d’un nombre illimité de tâches.
   - Consultation des détails d’une tâche en cliquant dessus.
   - Suppression d’une tâche si nécessaire.

5. **Paramètres de l’Application**
   - Accès à la section “Paramètres” via le menu.
   - Plusieurs onglets disponibles pour gérer les préférences de l’utilisateur.
   
   5.1. **Mon Compte**
   - Modifier son identifiant (nom, email…).
   - Changer sa photo de profil.

   5.2. **Gestion des Utilisateurs (Accessible aux Admins)**
   - Ajout d’un nouvel utilisateur via le panel d’administration.
   - Modification des informations d’un utilisateur existant.
   - Suppression d’un utilisateur.

   5.3. **Gestion des Clients (Accessible aux Admins et Managers)**
   - Ajouter un nouveau client.
   - Modifier ou supprimer un client existant.

   5.4. **Gestion des Projets**
   - Voir la liste de tous les projets.
   - Créer un nouveau projet directement depuis cette section.

6. **Déconnexion**
   - Bouton de déconnexion en haut à droite pour quitter la session.

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

6. Charger les fixtures si besoin (User de test avec tous les rôles différents) :
   ```bash
   php bin/console app:create-default-users
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

## Utilisateurs par défaut

| Email | Mot de passe | Rôle |
|-------|-------------|------|
| admin@digiworks.fr | Admin123! | Administrateur |
| responsable@digiworks.fr | Responsable123! | Responsable |
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
