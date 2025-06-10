<?php
/**
 * Configuration et gestion de la connexion à la base de données
 * 
 * Ce fichier gère la connexion à la base de données MySQL
 */

// Inclusion de la configuration d'environnement
require_once __DIR__ . '/env.php';

/**
 * Établit une connexion à la base de données
 * 
 * @return PDO Instance PDO de connexion à la base de données
 */
function getDbConnection() {
    static $connection = null;
    
    if ($connection === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $connection = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
        } catch (PDOException $e) {
            if (DEBUG) {
                die("Erreur de connexion à la base de données: " . $e->getMessage());
            } else {
                die("Erreur de connexion à la base de données. Veuillez contacter l'administrateur.");
            }
        }
    }
    
    return $connection;
}

/**
 * Exécute une requête SQL avec des paramètres
 * 
 * @param string $sql La requête SQL à exécuter
 * @param array $params Les paramètres à lier à la requête
 * @return PDOStatement L'objet PDOStatement résultant
 */
function executeQuery($sql, $params = []) {
    $connection = getDbConnection();
    $stmt = $connection->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Récupère une seule ligne de résultat
 * 
 * @param string $sql La requête SQL à exécuter
 * @param array $params Les paramètres à lier à la requête
 * @return array|false La ligne de résultat ou false si aucun résultat
 */
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

/**
 * Récupère toutes les lignes de résultat
 * 
 * @param string $sql La requête SQL à exécuter
 * @param array $params Les paramètres à lier à la requête
 * @return array Tableau de toutes les lignes
 */
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Insère des données dans une table et renvoie l'ID généré
 * 
 * @param string $table Le nom de la table
 * @param array $data Les données à insérer (clé => valeur)
 * @return int|false L'ID généré ou false en cas d'échec
 */
function insert($table, $data) {
    $keys = array_keys($data);
    $placeholders = array_fill(0, count($keys), '?');
    
    $sql = "INSERT INTO `$table` (`" . implode('`, `', $keys) . "`) VALUES (" . implode(', ', $placeholders) . ")";
    
    $connection = getDbConnection();
    $stmt = $connection->prepare($sql);
    $stmt->execute(array_values($data));
    
    return $connection->lastInsertId();
}

/**
 * Met à jour des données dans une table
 * 
 * @param string $table Le nom de la table
 * @param array $data Les données à mettre à jour (clé => valeur)
 * @param string $whereCol La colonne pour la condition WHERE
 * @param mixed $whereVal La valeur pour la condition WHERE
 * @return int Le nombre de lignes affectées
 */
function update($table, $data, $whereCol, $whereVal) {
    $keys = array_keys($data);
    $set = [];
    
    foreach ($keys as $key) {
        $set[] = "`$key` = ?";
    }
    
    $sql = "UPDATE `$table` SET " . implode(', ', $set) . " WHERE `$whereCol` = ?";
    
    $params = array_values($data);
    $params[] = $whereVal;
    
    $stmt = executeQuery($sql, $params);
    return $stmt->rowCount();
} 