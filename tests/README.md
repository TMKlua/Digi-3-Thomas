# Tests de l'application DIGI-3

Ce dossier contient tous les tests automatisés de l'application DIGI-3, organisés selon les scénarios définis dans le cahier des charges.

## Structure des tests

Les tests sont organisés en deux catégories principales :

### Tests fonctionnels

Les tests fonctionnels vérifient que les fonctionnalités de l'application fonctionnent correctement de bout en bout. Ils sont organisés par domaine fonctionnel :

- **User** : Tests de gestion des utilisateurs (création, modification, suppression, rôles et permissions)
- **Auth** : Tests d'authentification et de sécurité (connexion, déconnexion, mot de passe oublié)
- **Project** : Tests de gestion des projets et tâches (création, suivi, modification, suppression)
- **Collaboration** : Tests de collaboration et communication (commentaires, pièces jointes)
- **Performance** : Tests de performance et compatibilité (temps de chargement, compatibilité navigateurs et responsive design)

### Tests unitaires

Les tests unitaires vérifient le bon fonctionnement des composants individuels de l'application :

- **Entity** : Tests des entités (User, Project, Task, etc.)
- **Service** : Tests des services (SecurityService, ProjectService, etc.)

## Exécution des tests

### Prérequis

- PHP 8.2 ou supérieur
- Composer
- Une base de données de test configurée

### Configuration

Assurez-vous que votre fichier `.env.test` est correctement configuré avec les paramètres de votre base de données de test :

```
DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/digi3_test?serverVersion=8.0"
```

### Exécuter tous les tests

```bash
php bin/phpunit
```

### Exécuter une catégorie spécifique de tests

```bash
# Tests fonctionnels uniquement
php bin/phpunit tests/Functional

# Tests unitaires uniquement
php bin/phpunit tests/Unit
```

### Exécuter un test spécifique

```bash
# Test d'authentification
php bin/phpunit tests/Functional/Auth/AuthenticationTest.php

# Test de l'entité User
php bin/phpunit tests/Unit/Entity/UserTest.php
```

## Scénarios de test couverts

### Gestion des utilisateurs (U1-U5)

- U1 : Création d'un utilisateur
- U2 : Modification d'un utilisateur
- U3 : Suppression d'un utilisateur
- U4 : Vérification des droits administrateur
- U5 : Vérification des droits développeur

### Authentification et sécurité (A1-A4)

- A1 : Connexion avec identifiants valides
- A2 : Connexion avec identifiants invalides
- A3 : Déconnexion
- A4 : Mot de passe oublié

### Gestion des projets et tâches (P1-P3)

- P1 : Création d'un projet
- P2 : Ajout de tâches dans un projet
- P3 : Assignation d'une tâche à un utilisateur

### Collaboration et communication (C1-C2)

- C1 : Ajout d'un commentaire sur une tâche
- C2 : Ajout d'une pièce jointe

### Performance et compatibilité (T1-T3)

- T1 : Chargement du tableau de bord
- T2 : Compatibilité mobile
- T3 : Test multi-navigateurs

## Bonnes pratiques

- Chaque test doit être indépendant et ne pas dépendre de l'état laissé par d'autres tests
- Utilisez des données de test spécifiques et évitez de modifier les données existantes
- Nettoyez les données créées par vos tests après leur exécution
- Documentez clairement le scénario testé dans chaque méthode de test
- Utilisez des assertions explicites pour vérifier les résultats attendus

## Maintenance des tests

Pour maintenir les tests à jour :

1. Lorsque vous ajoutez une nouvelle fonctionnalité, ajoutez également les tests correspondants
2. Lorsque vous modifiez une fonctionnalité existante, mettez à jour les tests concernés
3. Exécutez régulièrement la suite de tests complète pour détecter les régressions
4. Maintenez une couverture de code élevée (idéalement > 80%)

## Génération de rapports de couverture

Pour générer un rapport de couverture de code :

```bash
XDEBUG_MODE=coverage php bin/phpunit --coverage-html var/coverage
```

Le rapport sera disponible dans le dossier `var/coverage`. 