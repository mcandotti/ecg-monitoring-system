<?php
/**
 * Logout page
 * 
 * Destroys the session and redirects to the login page
 */

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include functions
require_once '../includes/functions.php';
require_once '../config/security.php';
require_once '../config/database.php';

// Clear remember me cookie if it exists
if (isset($_COOKIE['remember_token'])) {
    // Delete the token from database if we know the user
    if (isset($_SESSION['user_id'])) {
        try {
            $db = getDbConnection();
            $query = "DELETE FROM remember_tokens WHERE user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->execute(['user_id' => $_SESSION['user_id']]);
        } catch (Exception $e) {
            // Silently fail, we're logging out anyway
        }
    }
    
    // Remove the cookie
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}

// Save user ID to a variable before destroying session
$wasLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// Destroy the session
$_SESSION = array();
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// Start a new session for the message
session_start();
if ($wasLoggedIn) {
    $_SESSION['success'] = 'Vous avez été déconnecté avec succès.';
}

// Redirect to the login page
header('Location: /login.php');
exit;
?> 