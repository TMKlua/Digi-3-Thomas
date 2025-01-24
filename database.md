Description de la base de données

Description de la structure de la base de données permettant de répondre aux besoins des utilisateurs.
Liste des tables

Nom de la table
Commentaire
Users
La table des utilisateurs de l’application
Users_Vacation
Déclaration des temps non-travaillés
Parameters
Table de paramétrage / configuration de l’application
Tasks
Table principale permettant de gérer les projets, tâches, sous-tâches et bugs
Task_Label
Liste des attributs associés à une tâche
Task_Comments
Liste des commentaires associés à une tâche
Task_Attachments
Liste des pièces jointes associées à une tâche
Task_Rates
Taux horaire à appliquer pour chaque user dans le cadre d’une tâche
Task_Workload
Temps passé par un collaborateur sur une tâche
Customers
Table des clients. Un client est associé à un ou plusieurs projets, ce qui permet de générer les factures mensuelles 
Invoice_Header
Table reprenant le contenu d’une facture
Invoice_Details
Table reprenant le détail du contenu de la facture



Table Users

Nom du champs
Type de champs
Commentaire
id
Integer, not null, auto_increment
Clé unique de la table
user_first_name
varchar(35)
Prénom de l’utilisateur
user_last_name
varchar(35)
Nom de l’utilisateur
user_email
varchar(35)
Email de l’utilisateur pour envoi de mails
user_avatar
varchar(255)
URL vers photos de l’avatar de l’utilisateur
user_role
varchar(35)
Rôle au sein de l’application. 
user_password
varchar(255)
Mot de passe crypté de l’utilisateur
user_date_from
datetime
date de début de validité de la ligne
user_date_to
datetime
date de fin de validité de la ligne
user_user_maj
Integer
identifie l’utilisateur ayant procédé à la maintenance de la table via son ID


Explications complémentaires:
La liste des rôles est définie par l’administrateur dans la table Params. En fonction des rôles, les accès aux différentes fonctionnalités de l’application sont autorisées ou pas, en lecture seule ou en création/modification/suppression.
Pour des raisons de simplicité nous ne gérons pas ce lien entre le rôle et les permissions dans une table de la base de données mais ce pourrait être une évolution pour une version future.
Accessibilité
Cette table est uniquement accessible par un profil administrateur.




Table Users_Vacation

Nom du champs
Type de champs
Commentaire
id
Integer, not null, auto_increment
Clé unique de la table
users_vacation_user
integer
Id de l’utilisateur
users_vacation_from
datetime
date de début vacance
users_vacation_to
datetime
date de fin vacance
users_date_from
datetime
date de début de validité de la ligne
users_date_to
datetime
date de fin de validité de la ligne
users_user_maj
Integer
identifie l’utilisateur ayant procédé à la maintenance de la table via son ID


Explications complémentaires:
La déclaration des périodes non travaillées pour chaque collaborateur est importante car elle impacte la planification des tâches au sein des projets.
Accessibilité
Cette table est accessible par tous.

Table Parameters

Nom du champs
Type de champs
Commentaire
id
Integer, not null, auto_increment
Clé unique de la table
param_key
varchar(35)
Code permettant de définir un type de paramètre qui sera utilisé dans l’application. Par exemple la liste des statuts des projets ou des tâches
param_value
varchar(35)
Valeur du paramètre
param_date_from
datetime
date de début de validité de la ligne
param_date_to
datetime
date de fin de validité de la ligne
param_user_maj
Integer
Lien avec clé unique table User


Explications complémentaires:
L'utilisation de dates de validité dans cette table permet de conserver l’historique des créations, modifications et suppressions au sein de la même table. Les suppressions sont gérées non pas en supprimant la ligne de la table mais en mettant à jour la date de fin de validité de la ligne.
Une nouvelle entrée dans la table a une date de validité vide (si l’utilisateur ne définit pas lui-même de date de fin).
Le lien avec la table User permet de savoir qui a réalisé ces opérations de maintenance de la table.
Accessibilité
Cette table est accessible par tous en lecture seule. Seul un utilisateur de type administrateur a les droits nécessaires pour réaliser les opérations de maintenance.

Table Tasks

Nom du champs
Type de champs
Commentaire
id
Integer, not null, auto_increment
Clé unique de la table
Task_Type
varchar(35)
Type de tâche
Task_name
varchar(35)
Nom de la tâche
Task_text
varchar(255)
Description de la tâche
Task_parent
integer
Lien entre une tâche et une autre
Task_User
integer
Collaborateur responsable de la réalisation de la tâche
Task_Real_Start_Date
datetime
Date de début réelle de la tâche
Task_Real_End_Dtate
datetime
Date de fin réelle de la tâche
Task_Target_Start_Date
datetime
Date de début théorique de la tâche
Task_Target_End_Date
datetime
Date de fin théorique de la tâche
Task_Complexity
varchar(35)
Niveau de complexité de la tâche
Task_Priority
varchar(35)
Niveau de priorité de la tâche
Task_date_from
datetime
date de début de validité de la ligne
Task_date_to
datetime
date de fin de validité de la ligne
Task_user_maj
Integer
Lien avec clé unique table User


Explications complémentaires:
Le type de tâche est défini dans la table Params (programme, projet, tâche, sous-tâche, bug, …).
Le champ 'Task_Parent' établit un lien hiérarchique entre les tâches, permettant de définir une relation parent-enfant. Cette relation est univoque : une tâche ne peut avoir qu'un seul parent direct. Cette contrainte nous permet d'intégrer cette notion directement dans la structure de la table. 
Des règles spécifiques, liées au type de tâche, encadrent cette relation parent-enfant. 
Un programme n’a pas de parent. C’est un ensemble de projets.
Un projet peut être lié à un programme sans que ce ne soit une obligation
Une tâche est forcément liée à un projet
Une sous-tâche est forcément liée à une tâche
Un bug peut-être lié à tous les types de tâches.
Il n’y qu’un seul seul responsable de l’exécution de la tâche. On ne peut pas attribuer deux personnes sur une même tâche. Si le besoin s’en fait sentir, il faut alors créer deux sous-tâches associés à cette tâche.
Les niveaux de complexité et de priorité sont définis par le chef de projet sur la base d’une liste de valeurs définies dans la table Params. Exemple: priorité de 1 à 5 et niveau de complexité de 1 à 5.
Accessibilité
Cette table est accessible par tous en lecture seule. 
Un utilisateur de type administrateur est le seul habilité à créer tous les types de tâche et notamment la tâche de type “Programme” et de type “Projet”.
Le profil “Chef de projet” peut lui créer des tâches et des sous-tâches
Le profil “Développeur” peut créer des sous-tâches
Tous les types de profil peuvent créer des tâches de type “Bug”.

Table Tasks_Label

Nom du champs
Type de champs
Commentaire
id
Integer, not null, auto_increment
Clé unique de la table
Task_Label_ID
Integer
Lien avec le numéro de tâche
Task_Label_Value
varchar(255)
valeur


Explications complémentaires:
Il y a autant de lignes dans la table que nécessaire pour une seule tâche. Un label permet de qualifier une tâche et donc de regrouper des tâches, y compris de projets différents entre elles.
Accessibilité
Cette table est accessible par tous en lecture seule. 
Il n’y a pas d’interface permettant de gérer directement le contenu de cette table. Elle est alimentée via l’interface de gestion des tâches / Projets
Dans cette table, pas de gestion de l’historique car jugé inutile.

Table Task_Comments

Nom du champs
Type de champs
Commentaire
id
Integer, not null, auto_increment
Clé unique de la table
Task_Comments_ID
Integer
Lien avec le numéro de tâche
Task_Comments_Line
Integer
Numéro de ligne du commentaire
Task_Comments_Value
varchar(255)
Texte de la ligne de commentaire


Explications complémentaires:
Il y a autant de lignes dans la table que nécessaire pour une seule tâche. La zone Tasks_Comments_Line permet de conserver le texte du commentaire ligne par ligne.
Accessibilité
Cette table est accessible par tous en lecture seule. 
Il n’y a pas d’interface permettant de gérer directement le contenu de cette table. Elle est alimentée via l’interface de gestion des tâches / Projets
Dans cette table, pas de gestion de l’historique car jugé inutile.

Table Task_Attachments

Nom du champs
Type de champs
Commentaire
id
Integer, not null, auto_increment
Clé unique de la table
Task_Attachments_ID
Integer
Lien avec le numéro de tâche
Task_Attachments_Value
varchar(255)
valeur


Explications complémentaires:
Il y a autant de lignes dans la table que nécessaire pour une seule tâche.
Accessibilité
Cette table est accessible par tous en lecture seule. 
Il n’y a pas d’interface permettant de gérer directement le contenu de cette table. Elle est alimentée via l’interface de gestion des tâches / Projets
Dans cette table, pas de gestion de l’historique car jugé inutile.

Table Task_Rates

Nom du champs
Type de champs
Commentaire
id
Integer, not null, auto_increment
Clé unique de la table
Task_Rates_User_Role
Integer
Rôle de l’utilisateur
Task_Rates_Task
Integer
Tâche
Task_Rates_Amount
Integer
Taux horaire à appliquer


Explications complémentaires:
Cette table permet de définir le montant facturé au client pour le temps passé par chaque type de collaborateur sur un programme ou projet.
Cela permet à l’administrateur ou le commercial en charge du dossier client de pouvoir négocier des tarifs horaires spécifiques pour chaque projet et type de profil.
Nous aurions pû gérer ces montants par utilisateur mais, pour simplifier la gestion sur de gros projets impliquant de nombreuses personnes, il est plus simple de gérer les taux horaires par type de profil. Sachant qu’il est toujours possible de créer de nouveaux types de profil dans la table Params. Le seul problème étant de gérer dynamiquement les permissions au sein de l’application. A voir dans une version V2.

Accessibilité
Cette table est accessible uniquement à l’administrateur et au chef de projet en lecture et écriture
Les autres type de profil ne doivent pas avoir accès à ces informations






Table Task_Workload

Nom du champs
Type de champs
Commentaire
id
Integer, not null, auto_increment
Clé unique de la table
Task_Workload_Task
Integer
Id de la tâche
Task_Workload_User
Integer
Id collaborateur
Task_Workload_Duration
Integer
Nombre d’heures effectuées
Task_Workload_Date_From
datetime
Date de début de validité
Task_Workload_Date_To
datetime
Date de fin de validité
Task_Workload_User_Maj
Integer
Qui a fait la mise à jour 


Explications complémentaires:
Cette table permet à chaque collaborateur de définir le temps passé réellement sur chaque tâche. C’est une information structurante pour le calcul des KPI (Key Performance Indicator) mais aussi la facture client.

Accessibilité
Cette table est accessible uniquement à tous

Table Customers

Nom du champs
Type de champs
Commentaire
id
Integer, not null, auto_increment
Clé unique de la table
Customer_name
varchar(255)
Lien avec le numéro de tâche
Customer_address_street
varchar(255)


Customer_address_zipcode
varchar(35)


Customer_address_city
varchar(255)


Customer_address_country
varchar(35)


Customer_VAT
varchar(35)
Code TVA du client
Customer_SIREN
varchar(35)
Numéro de SIREN du client
Customer_reference
varchar(255)
Eventuellement une référence client 
Customer_Date_From
datetime


Customer_Date_To
datetime


Customer_User_Maj
integer




Explications complémentaires:
Il y a autant de lignes dans la table que nécessaire pour une seule tâche.
Accessibilité
Cette table est accessible par tous en lecture seule. 
Seul l’administrateur peut ajouter ou modifier la table client


Table Task_Customer

Nom du champs
Type de champs
Commentaire
id
Integer, not null, auto_increment
Clé unique de la table
Task_Customer_Task
Integer
Id de la tâche
Task_Customer_Name
Integer
Id du client
Task_Customer_Date_From
datetime
Date de début de validité
Task_Customer_Date_To
datetime
Date de fin de validité
Task_Customer_User_Maj
Integer
Qui a fait la mise à jour 


Explications complémentaires:
Cette table permet de lier une tâche de type “Projet” avec un client


Table Invoice_Header

Nom du champs
Type de champs
Commentaire
id
Integer, not null, auto_increment
Clé unique de la table
Invoice_Header_Number
varchar(35)
Numéro de facture
Invoice_Header_Type
varchar(35)
Type de facture
Invoice_Header_Date
datetime
Date de la facture
Invoice_Header_Customer
Integer
Lien vers table Customers
Invoice_Header_HT
Integer
Montant total HT de la facture
Invoice_Header_VAT
Integer
Montant TVA
Invoice_Header_TTC
Integer
Montant TTC
Invoice_Header_URL
varchar(255)
Lien URL vers le PDF de la facture


Explications complémentaires:
Une seule ligne par facture.
Numéro de facture est unique
Type de facture = Facture ou Avoir - pas utilisé dans l’application dans sa première version

Accessibilité
Cette table est accessible par tous en lecture seule. 
Seul l’administrateur peut ajouter ou modifier la table Invoice_Header




Table Invoice_Details

Nom du champs
Type de champs
Commentaire
id
Integer, not null, auto_increment
Clé unique de la table
Invoice_Details_Number
varchar(35)
Numéro de facture
Invoice_Details_Tasks
varchar(35)
Numéro de tâche
Invoice_Header_Date
datetime
Date de la facture
Invoice_Header_Customer
Integer
Lien vers table Customers
Invoice_Details_HT
Integer
Montant total HT de la facture


Explications complémentaires:
Pas de calcul de TVA et TTC à la ligne détail.
La facture est constituée à partir de la déclaration des temps passés par les collaborateurs sur l’ensemble des tâches associées à un client (en dehors de des tâches de type “Bug” qui ne doivent pas être facturées au client) et des taux horaires associés à chaque profil au sein de chaque projet. Tout ceci sur une période mensuelle débutant le premier jour du mois et se terminant le dernier jour du même mois.
La logique de calcul est la suivante:
sélection d’un client (table Customers)
sélection de l’ensemble des tâches (tous types) associées à ce client avec une date de début et fin réelle comprise dans l’intervalle de temps du mois à facturer (du 1er au 31 Octobre par exemple). (table Tasks)
pour chaque tâche identifiée, recherche du temps déclaré par le collaborateur sur cette même période (table Tasks_Workload)
Récupération du taux horaire à appliquer (table Task_Rates)
Calcul du montant sur chaque ligne qui sera inséré dans la table Invoice_Details
La table Invoice_Header est ajouté à la fin du calcul avec les totaux et le calcul de la TVA
Un document de type PDF est créé et sauvegardé. Le lien est inscrit dans la table Invoice_Headers

Accessibilité
Cette table est accessible par tous en lecture seule. 
Seul l’administrateur peut ajouter ou modifier la table Invoice_Details

