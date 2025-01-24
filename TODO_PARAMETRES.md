# TODO Liste - Section Paramètres

## 1. Harmonisation du Code

### Templates
- [ ] Créer un template de base `_base_management.html.twig`
  - [ ] Définir les blocs communs (header, table, modales)
  - [ ] Adapter les templates existants pour hériter du template de base
- [ ] Extraire les composants communs
  - [ ] `_flash_messages.html.twig`
  - [ ] `_table_actions.html.twig`
  - [ ] `_modal_delete.html.twig`

### JavaScript
- [ ] Créer une classe de base `BaseManager.js`
  - [ ] Méthodes CRUD génériques
  - [ ] Gestion des modales
  - [ ] Gestion des permissions
- [ ] Adapter les fichiers JS existants
  - [ ] `user-management.js`
  - [ ] `customer-management.js`
  - [ ] `tasks-management.js`

## 2. Configuration

### Interface de Configuration
- [ ] Créer `templates/parameter/configuration.html.twig`
  - [ ] Section paramètres généraux
  - [ ] Section paramètres email
  - [ ] Section paramètres de sécurité
  - [ ] Section sauvegarde/restauration

### Backend
- [ ] Créer `ConfigurationController.php`
- [ ] Créer `ConfigurationService.php`
- [ ] Implémenter la gestion des paramètres système
  - [ ] Stockage en base de données
  - [ ] Cache système

## 3. Facturation

### Interface
- [ ] Créer `templates/parameter/billing.html.twig`
  - [ ] Liste des factures
  - [ ] Formulaire de création/édition
  - [ ] Aperçu PDF

### Backend
- [ ] Créer `BillingController.php`
- [ ] Créer `BillingService.php`
- [ ] Implémenter la génération de PDF
- [ ] Gestion des numéros de facture
- [ ] Calculs automatiques (TVA, totaux)

### Base de données
- [ ] Créer les entités
  - [ ] `Invoice`
  - [ ] `InvoiceLine`
  - [ ] `BillingSettings`

## 4. Améliorations CRUD

### Utilisateurs
- [ ] Ajouter la gestion des avatars
- [ ] Améliorer la gestion des rôles
- [ ] Ajouter des filtres de recherche

### Clients
- [ ] Ajouter la géolocalisation
- [ ] Historique des interactions
- [ ] Documents associés

### Tâches
- [ ] Ajouter des statuts
- [ ] Système de tags
- [ ] Filtres avancés

## 5. Sécurité et Permissions

- [ ] Implémenter la hiérarchie des rôles
- [ ] Ajouter des logs d'audit
- [ ] Gestion des sessions
- [ ] Protection CSRF
- [ ] Validation des données

## 6. Tests

- [ ] Tests unitaires
  - [ ] Services
  - [ ] Contrôleurs
- [ ] Tests fonctionnels
  - [ ] Scénarios CRUD
  - [ ] Workflows utilisateur
- [ ] Tests de sécurité

## 7. Documentation

- [ ] Documentation technique
  - [ ] Architecture
  - [ ] API
  - [ ] Base de données
- [ ] Documentation utilisateur
  - [ ] Manuel d'administration
  - [ ] Guide d'utilisation
  - [ ] FAQ 