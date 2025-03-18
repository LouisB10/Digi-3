# Tests fonctionnels et techniques pour Digi-3

Ce dossier contient l'ensemble des tests fonctionnels et techniques pour l'application Digi-3, conformément au cahier des charges.

## Structure des tests

Les tests sont organisés selon les catégories suivantes:

- **Functional/Controller/Auth**: Tests d'authentification et de sécurité (A1-A4)
- **Functional/Controller/User**: Tests de gestion des utilisateurs (U1-U5)
- **Functional/Controller/Project**: Tests de gestion des projets (P1)
- **Functional/Controller/Task**: Tests de gestion des tâches (P2-P3)
- **Functional/Controller/Communication**: Tests de collaboration et communication (C1-C2)
- **Performance**: Tests de performance (T1)
- **UI**: Tests d'interface utilisateur et de compatibilité (T2-T3)
- **Security**: Tests de sécurité

## Prérequis

- PHP 8.1 ou supérieur
- Symfony 6.4 ou supérieur
- PHPUnit 9.5 ou supérieur
- Base de données MySQL configurée

## Configuration

Avant d'exécuter les tests, assurez-vous que:

1. La base de données de test est configurée dans `.env.test`
2. Les dépendances sont installées: `composer install`
3. La variable `KERNEL_CLASS` est définie dans `phpunit.xml.dist`

## Exécution des tests

### Tous les tests

```bash
php bin/phpunit
```

### Tests par catégorie

```bash
# Tests d'authentification
php bin/phpunit tests/Functional/Controller/Auth

# Tests de gestion des utilisateurs
php bin/phpunit tests/Functional/Controller/User

# Tests de gestion des projets
php bin/phpunit tests/Functional/Controller/Project

# Tests de gestion des tâches
php bin/phpunit tests/Functional/Controller/Task

# Tests de collaboration et communication
php bin/phpunit tests/Functional/Controller/Communication

# Tests de performance
php bin/phpunit tests/Performance

# Tests d'interface utilisateur
php bin/phpunit tests/UI

# Tests de sécurité
php bin/phpunit tests/Security
```

### Test spécifique

```bash
php bin/phpunit tests/Functional/Controller/Auth/AuthenticationTest.php
```

## Couverture des tests

Les tests couvrent les fonctionnalités suivantes:

### Gestion des utilisateurs
- U1: Création d'un utilisateur
- U2: Modification d'un utilisateur
- U3: Suppression d'un utilisateur
- U4: Vérification des droits administrateur
- U5: Vérification des droits développeur full-web

### Authentification et Sécurité
- A1: Connexion avec identifiants valides
- A2: Connexion avec identifiants invalides
- A3: Déconnexion
- A4: Mot de passe oublié

### Gestion des Projets et Tâches
- P1: Création d'un projet
- P2: Ajout de tâches dans un projet
- P3: Assignation d'une tâche à un utilisateur

### Collaboration et Communication
- C1: Ajout d'un commentaire sur une tâche
- C2: Ajout d'une pièce jointe

### Tests de Performance et Compatibilité
- T1: Chargement du tableau de bord
- T2: Compatibilité mobile
- T3: Test multi-navigateurs

## Notes sur les tests

- Les tests utilisent des données générées aléatoirement pour éviter les conflits
- Certains tests nécessitent un utilisateur administrateur existant (`admin@example.com`)
- Les tests de performance peuvent varier selon l'environnement d'exécution
- Les tests d'interface utilisateur simulent différents navigateurs et appareils 