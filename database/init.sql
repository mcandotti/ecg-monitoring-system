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

-- Table des configurations d'acquisition
CREATE TABLE IF NOT EXISTS `configurations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` INT NOT NULL,
  `acquisition_time` INT NOT NULL COMMENT 'Temps d''acquisition en secondes',
  `config_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des données ECG
CREATE TABLE IF NOT EXISTS `ecg_data` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `configuration_id` INT NOT NULL,
  `timestamp` TIMESTAMP NOT NULL COMMENT 'Horodatage de l''échantillon',
  `value` FLOAT NOT NULL COMMENT 'Valeur du signal ECG',
  FOREIGN KEY (`configuration_id`) REFERENCES `configurations`(`id`) ON DELETE CASCADE,
  INDEX `idx_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des diagnostics
CREATE TABLE IF NOT EXISTS `diagnostics` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `configuration_id` INT NOT NULL,
  `professor_name` VARCHAR(255) NOT NULL,
  `consultation_address` TEXT NOT NULL,
  `report` TEXT NOT NULL,
  `atrial_release_time` FLOAT DEFAULT NULL COMMENT 'Temps de relâchement des oreillettes en ms',
  `p_wave` FLOAT DEFAULT NULL COMMENT 'Niveau de l''onde P',
  `q_wave` FLOAT DEFAULT NULL COMMENT 'Niveau de l''onde Q',
  `r_wave` FLOAT DEFAULT NULL COMMENT 'Niveau de l''onde R',
  `s_wave` FLOAT DEFAULT NULL COMMENT 'Niveau de l''onde S',
  `t_wave` FLOAT DEFAULT NULL COMMENT 'Niveau de l''onde T',
  `diagnosis_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`configuration_id`) REFERENCES `configurations`(`id`) ON DELETE CASCADE
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

-- Insertion d'un utilisateur admin par défaut (mot de passe: admin)
INSERT INTO `users` (`username`, `password`, `role`) VALUES
('admin', '$2y$10$0kTC8gQ5xgboB28eqzIR2.7LnJ29rEoSH.miHNinV4YoV7LWzbhee', 'admin'); 