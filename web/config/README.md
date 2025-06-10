# Dossier Config

Ce dossier contient les fichiers de configuration essentiels pour l'application web du système de surveillance ECG.

## Structure du Dossier

```
web/config/
├── database.php      # Fonctions de connexion à la base de données
├── env.php           # Gestion des variables d'environnement
└── security.php      # Fonctions de sécurité et de chiffrement
```

## Détails des Fichiers

### database.php

Ce fichier fournit les fonctions nécessaires pour établir et gérer les connexions à la base de données MySQL. Il inclut:
- Établissement de connexions sécurisées
- Exécution de requêtes avec préparation (pour éviter les injections SQL)
- Gestion des transactions

Exemple de fonction de connexion:
```php
function getDbConnection() {
    static $conn = null;
    
    if ($conn === null) {
        $host = getenv('DB_HOST') ?: 'mysql';
        $port = getenv('DB_PORT') ?: '3306';
        $dbname = getenv('DB_NAME') ?: 'ecg_database';
        $username = getenv('DB_USER') ?: 'ecg_user';
        $password = getenv('DB_PASSWORD') ?: 'secure_password';
        
        try {
            $conn = new PDO(
                "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données: " . $e->getMessage());
        }
    }
    
    return $conn;
}
```

Exemple d'exécution de requête sécurisée:
```php
function executeQuery($sql, $params = []) {
    $conn = getDbConnection();
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}
```

### env.php

Ce fichier gère la lecture et l'accès aux variables d'environnement utilisées pour configurer l'application. Il permet de:
- Charger des variables d'environnement
- Fournir des valeurs par défaut
- Nettoyer et valider les entrées

Exemple de fonction de récupération de variable d'environnement:
```php
function getEnv($key, $default = null) {
    $value = getenv($key);
    
    if ($value === false) {
        return $default;
    }
    
    return $value;
}

function isDebugMode() {
    $debug = strtolower(getEnv('DEBUG', 'false'));
    return in_array($debug, ['true', '1', 'yes', 'on']);
}
```

### security.php

Ce fichier contient toutes les fonctions liées à la sécurité, notamment:
- Hachage et vérification des mots de passe
- Chiffrement et déchiffrement des données sensibles
- Encodage et décodage des informations
- Protection contre les attaques CSRF (Cross-Site Request Forgery)

Exemple de fonction de hachage de données sensibles:
```php
function hashSensitiveData($data) {
    $hashKey = getenv('HASH_KEY') ?: 'test2025';
    return hash_hmac('sha256', $data, $hashKey);
}
```

Exemple de fonction d'encodage/décodage:
```php
function encodeData($data) {
    return base64_encode($data);
}

function decodeData($encodedData) {
    return base64_decode($encodedData);
}
```

Exemple de fonction de protection CSRF:
```php
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}
```

## Utilisation

Ces fichiers de configuration sont utilisés tout au long de l'application pour:
1. Établir des connexions sécurisées à la base de données
2. Adapter le comportement de l'application en fonction de l'environnement
3. Protéger les données sensibles conformément aux normes médicales
4. Sécuriser l'application contre diverses vulnérabilités web 