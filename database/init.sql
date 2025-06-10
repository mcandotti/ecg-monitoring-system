-- Création de la base de données
CREATE DATABASE IF NOT EXISTS `ecg_database`;
USE `ecg_database`;

-- Table des patients
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

-- Table des diagnostics
CREATE TABLE IF NOT EXISTS `diagnostics` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des données ECG
CREATE TABLE IF NOT EXISTS `ecg_data` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `image_blob` LONGBLOB NOT NULL COMMENT 'Image blob',
  `image_created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Horodatage de la création de l''image',
  `diagnostic_id` INT NOT NULL,
  `capture_duration` INT DEFAULT 5 COMMENT 'Durée de capture en secondes',
  `status` ENUM('captured', 'processing', 'completed') DEFAULT 'completed' COMMENT 'Statut de l''image',
  FOREIGN KEY (`diagnostic_id`) REFERENCES `diagnostics`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des utilisateurs (pour l'authentification)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'medecin', 'patient') NOT NULL DEFAULT 'patient',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_login` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for remember me tokens
CREATE TABLE IF NOT EXISTS `remember_tokens` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_expires_at` (`expires_at`),
  INDEX `idx_user_token` (`user_id`, `token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table de statut des captures ECG
CREATE TABLE IF NOT EXISTS `ecg_capture_sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `diagnostic_id` INT NOT NULL,
  `status` ENUM('idle', 'running', 'stopped', 'error') DEFAULT 'idle',
  `started_at` TIMESTAMP NULL,
  `stopped_at` TIMESTAMP NULL,
  `total_images` INT DEFAULT 0,
  `last_error` TEXT NULL,
  FOREIGN KEY (`diagnostic_id`) REFERENCES `diagnostics`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion d'un utilisateur admin par défaut (mot de passe: admin)
INSERT INTO `users` (`username`, `password`, `role`) VALUES
('admin', '$2y$10$0kTC8gQ5xgboB28eqzIR2.7LnJ29rEoSH.miHNinV4YoV7LWzbhee', 'admin'); 