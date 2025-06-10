# Dossier Database

Ce dossier contient les fichiers nécessaires à l'initialisation et à la configuration de la base de données MySQL utilisée par le système de surveillance ECG.

## Structure du Dossier

```
database/
└── init.sql           # Script d'initialisation de la base de données
```

## Détails des Fichiers

### init.sql

Ce fichier contient les scripts SQL pour créer la structure initiale de la base de données, notamment:
- La création de la base de données elle-même
- La création des tables nécessaires
- L'ajout des contraintes et des index
- L'insertion des données initiales (comme l'utilisateur admin)

#### Tables Principales

Le script crée plusieurs tables essentielles:

1. **Table des patients**:
```sql
CREATE TABLE IF NOT EXISTS `patients` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name_hash` VARCHAR(255) NOT NULL COMMENT 'Nom hashé',
  `name_encoded` VARCHAR(255) NOT NULL COMMENT 'Nom encodé en base64',
  `secu_hash` VARCHAR(255) NOT NULL COMMENT 'Numéro de sécurité sociale hashé',
  `secu_encoded` VARCHAR(255) NOT NULL COMMENT 'Numéro de sécurité sociale encodé en base64',
  `phone` VARCHAR(20) NOT NULL,
  `address_encoded` TEXT NOT NULL COMMENT 'Adresse encodée en base64',
  `blood_type` VARCHAR(10) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```
Cette table stocke les informations des patients avec des mesures de sécurité (hashage et encodage) pour protéger les données sensibles.

2. **Table des configurations d'acquisition**:
```sql
CREATE TABLE IF NOT EXISTS `configurations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` INT NOT NULL,
  `acquisition_time` INT NOT NULL COMMENT 'Temps d''acquisition en secondes',
  `config_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE
);
```
Cette table définit les paramètres d'acquisition pour chaque session ECG.

3. **Table des données ECG**:
```sql
CREATE TABLE IF NOT EXISTS `ecg_data` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `configuration_id` INT NOT NULL,
  `timestamp` TIMESTAMP NOT NULL COMMENT 'Horodatage de l''échantillon',
  `value` FLOAT NOT NULL COMMENT 'Valeur du signal ECG',
  FOREIGN KEY (`configuration_id`) REFERENCES `configurations`(`id`) ON DELETE CASCADE,
  INDEX `idx_timestamp` (`timestamp`)
);
```
Cette table stocke les valeurs des signaux ECG collectées pendant les sessions d'acquisition.

4. **Table des utilisateurs**:
```sql
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'medecin', 'patient') NOT NULL DEFAULT 'patient',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_login` TIMESTAMP NULL
);
```
Cette table gère les utilisateurs du système avec leurs rôles et informations d'authentification.

## Utilisation

Le script init.sql est automatiquement exécuté lors de la première création du conteneur MySQL via Docker. Cela permet de s'assurer que la base de données est correctement initialisée avec toutes les tables nécessaires. 