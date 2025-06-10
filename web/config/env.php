<?php
/**
 * Gestionnaire des variables d'environnement
 * 
 * Utilise les variables d'environnement pour configurer le système
 */

// Fonction pour récupérer une variable d'environnement
if (!function_exists('getEnv')) {
    function getEnv($key, $default = null) {
        return isset($_ENV[$key]) ? $_ENV[$key] : 
               (getenv($key) ? getenv($key) : $default);
    }
}

// Variables de base de données
define('DB_HOST', getEnv('DB_HOST', 'mysql'));
define('DB_PORT', getEnv('DB_PORT', '3306'));
define('DB_NAME', getEnv('DB_NAME', 'ecg_database'));
define('DB_USER', getEnv('DB_USER', 'ecg_user'));
define('DB_PASSWORD', getEnv('DB_PASSWORD', 'secure_password'));

// Clé de hashage
define('HASH_KEY', getEnv('HASH_KEY', 'test2025'));

// Raspberry Pi
define('PI_IP', getEnv('PI_IP', '192.168.1.100'));
define('PI_PORT', getEnv('PI_PORT', '8000'));

// Mode debug
define('DEBUG', true); // Force debug mode for troubleshooting

// Activer le mode debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); 