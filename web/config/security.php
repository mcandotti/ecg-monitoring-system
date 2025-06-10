<?php
/**
 * Security functions for authentication and data protection
 * 
 * This file contains all functions related to security and sensitive data processing
 */

// Include environment configuration
require_once __DIR__ . '/env.php';

/**
 * Generate a secure random token
 * 
 * @param int $length Length of the token
 * @return string Secure random token
 */
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Encode data in base64
 * 
 * @param string $data Data to encode
 * @return string Base64 encoded data
 */
function encodeBase64($data) {
    return base64_encode($data);
}

/**
 * Decode base64 data
 * 
 * @param string $data Encoded data to decode
 * @return string Decoded data
 */
function decodeBase64($data) {
    return base64_decode($data);
}

/**
 * Hash sensitive data like patient names or identifiers
 * This is used for data indexing/lookup, not for authentication
 * 
 * @param string $data Data to hash
 * @return string Hashed data
 */
function hashSensitiveData($data) {
    // Using SHA-256 for data lookup as it's fixed length and deterministic
    // This provides a consistent hash for the same input
    return hash('sha256', $data . HASH_KEY);
}

/**
 * Create a secure password hash using bcrypt
 * 
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify a password against its hash
 * 
 * @param string $password Plain text password
 * @param string $hash Password hash
 * @return bool True if password matches, false otherwise
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate a CSRF token to protect forms
 * 
 * @return string CSRF token
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateSecureToken();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify a CSRF token
 * 
 * @param string $token Token to verify
 * @return bool True if token is valid, false otherwise
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input data to prevent injections
 * 
 * @param string $input String to sanitize
 * @return string Sanitized string
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Hash a token for storage (for remember tokens, API tokens, etc.)
 * 
 * @param string $token Plain token
 * @return string Hashed token
 */
function hashToken($token) {
    return password_hash($token, PASSWORD_BCRYPT);
}

/**
 * Verify a token against its hash
 * 
 * @param string $token Plain token
 * @param string $hash Hashed token
 * @return bool True if token matches, false otherwise
 */
function verifyToken($token, $hash) {
    return password_verify($token, $hash);
}

/**
 * Check remember token cookie for auto login
 * 
 * This function should be called at the beginning of each page that requires authentication.
 * It checks if a remember token cookie exists and validates it against the database.
 * 
 * @return bool True if auto login was successful, false otherwise
 */
function checkRememberToken() {
    // If already logged in or no remember token cookie exists, return
    if (isset($_SESSION['logged_in']) || !isset($_COOKIE['remember_token'])) {
        return false;
    }
    
    $token = $_COOKIE['remember_token'];
    
    try {
        // Connect to database
        require_once __DIR__ . '/database.php';
        $db = getDbConnection();
        
        // Get current date/time for comparison with token expiry
        $now = date('Y-m-d H:i:s');
        
        // Find a valid token in the database
        $query = "SELECT rt.user_id, rt.token, u.username, u.role 
                 FROM remember_tokens rt 
                 JOIN users u ON rt.user_id = u.id 
                 WHERE rt.expires_at > :now 
                 ORDER BY rt.expires_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute(['now' => $now]);
        
        // Check all valid tokens
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Verify the token using password_verify
            if (verifyToken($token, $row['token'])) {
                // Token is valid, create a session
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['logged_in'] = true;
                
                // Generate a new token for better security (token rotation)
                $newToken = generateSecureToken();
                $expiry = time() + (30 * 24 * 60 * 60); // 30 days
                
                // Update the token in the database
                $updateQuery = "UPDATE remember_tokens 
                               SET token = :new_token, expires_at = :expires_at 
                               WHERE user_id = :user_id AND token = :old_token";
                               
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([
                    'new_token' => hashToken($newToken),
                    'expires_at' => date('Y-m-d H:i:s', $expiry),
                    'user_id' => $row['user_id'],
                    'old_token' => $row['token']
                ]);
                
                // Update the cookie
                setcookie('remember_token', $newToken, $expiry, '/', '', false, true);
                
                return true;
            }
        }
        
        // No valid token found, clear the cookie
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        return false;
        
    } catch (Exception $e) {
        // Error occurred, clear the cookie
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        return false;
    }
} 