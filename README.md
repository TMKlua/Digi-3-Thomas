# Digi-3 - Système de Gestion de Projets

Digi-3 est une application web de gestion de projets développée avec Symfony 7.2 et PHP 8.2. Elle permet de gérer des projets, des tâches, des clients et des utilisateurs avec un système de permissions avancé.

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

## Installation

1. Cloner le dépôt :
   ```bash
   git clone https://github.com/votre-utilisateur/digi-3.git
   cd digi-3
   ```

2. Installer les dépendances PHP :
   ```bash
   composer install
   ```

3. Installer les dépendances JavaScript :
   ```bash
   npm install
   npm run build
   ```

4. Configurer la base de données dans le fichier `.env.local` :
   ```
   DATABASE_URL="mysql://user:password@127.0.0.1:3306/digi3?serverVersion=8.0"
   ```

5. Créer la base de données et appliquer les migrations :
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

6. Charger les fixtures (données de test) :
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
- `src/Enum/` : Énumérations PHP 8.1+
- `templates/` : Templates Twig
- `public/` : Fichiers publics (CSS, JS, images)
- `config/` : Fichiers de configuration
- `migrations/` : Migrations de base de données

## Système de sécurité

Digi-3 utilise un système de sécurité avancé basé sur les Voters de Symfony pour gérer les permissions de manière granulaire.

### Voters

Les Voters sont des classes qui déterminent si un utilisateur a le droit d'effectuer une action spécifique sur une ressource donnée. L'application utilise les Voters suivants :

- **ProjectVoter** : Gère les permissions sur les projets
  - `view` : Voir un projet
  - `edit` : Modifier un projet
  - `delete` : Supprimer un projet
  - `create` : Créer un projet
  - `manage_tasks` : Gérer les tâches d'un projet

- **TaskVoter** : Gère les permissions sur les tâches
  - `view` : Voir une tâche
  - `edit` : Modifier une tâche
  - `delete` : Supprimer une tâche
  - `create` : Créer une tâche
  - `change_status` : Changer le statut d'une tâche
  - `assign` : Assigner une tâche à un utilisateur

- **CustomerVoter** : Gère les permissions sur les clients
  - `view` : Voir un client
  - `edit` : Modifier un client
  - `delete` : Supprimer un client
  - `create` : Créer un client

- **UserVoter** : Gère les permissions sur les utilisateurs
  - `view` : Voir un utilisateur
  - `edit` : Modifier un utilisateur
  - `delete` : Supprimer un utilisateur
  - `create` : Créer un utilisateur
  - `change_role` : Changer le rôle d'un utilisateur

### Utilisation dans les contrôleurs

Dans les contrôleurs, les permissions sont vérifiées avec la méthode `denyAccessUnlessGranted()` :

```php
// Vérifier si l'utilisateur peut voir un projet
$this->denyAccessUnlessGranted('view', $project);

// Vérifier si l'utilisateur peut créer un projet
$this->denyAccessUnlessGranted('create', null);
```

### Utilisation dans les templates Twig

Dans les templates Twig, les permissions sont vérifiées avec la fonction `is_granted()` :

```twig
{% if is_granted('edit', project) %}
    <button class="edit-button">Modifier</button>
{% endif %}
```

## Utilisateurs par défaut

Les fixtures créent plusieurs utilisateurs par défaut :

| Email | Mot de passe | Rôle |
|-------|-------------|------|
| admin@digiworks.fr | Admin123! | Administrateur |
| responsable@digiworks.fr | Responsable123! | Chef de projet |
| pm@digiworks.fr | ProjectManager123! | Chef de projet |
| lead@digiworks.fr | LeadDev123! | Lead développeur |
| dev@digiworks.fr | Dev123! | Développeur |
| user@digiworks.fr | User123! | Utilisateur standard |

## Développement

### Commandes utiles

- Créer une entité :
  ```bash
  php bin/console make:entity
  ```

- Créer un contrôleur :
  ```bash
  php bin/console make:controller
  ```

- Créer une migration :
  ```bash
  php bin/console make:migration
  ```

- Appliquer les migrations :
  ```bash
  php bin/console doctrine:migrations:migrate
  ```

- Vider le cache :
  ```bash
  php bin/console cache:clear
  ```

### Conventions de code

- PSR-1, PSR-2 et PSR-4
- Utilisation des attributs PHP 8 pour les annotations
- Utilisation des énumérations PHP 8.1+ pour les types énumérés
- Injection de dépendances via le constructeur

## Tests

Pour exécuter les tests :

```bash
php bin/phpunit
```

## Déploiement

1. Configurer les variables d'environnement pour la production dans `.env.local`
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

Pour toute question ou suggestion, veuillez contacter l'équipe de développement à l'adresse suivante : dev@digiworks.fr