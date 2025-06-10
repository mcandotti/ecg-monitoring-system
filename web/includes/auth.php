<?php
/**
 * Authentication handler
 * 
 * This file handles all authentication-related functions and checks.
 * It enforces authentication for all pages except login and logout.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include security functions
require_once __DIR__ . '/../config/security.php';

// Define a flag to skip authentication
$skip_auth_check = false;

// Try auto-login with remember token if not logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    checkRememberToken();
}

/**
 * Check if user is logged in
 *
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Check if user has a specific role
 *
 * @param string $role Role to check for
 * @return bool True if user has the role, false otherwise
 */
function hasRole($role) {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Require authentication to access the page
 * Redirects to login page if not authenticated
 *
 * @param string $role Optional role requirement
 * @return void
 */
function requireAuth($role = null) {
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'Authentication required to access this page.';
        header('Location: /login.php');
        exit;
    }
    
    if ($role !== null && !hasRole($role)) {
        $_SESSION['error'] = 'You do not have permission to access this page.';
        header('Location: /pages/index.php');
        exit;
    }
}

/**
 * Check authentication for current page
 * 
 * This function checks if the current page requires authentication
 * and redirects to login if needed.
 */
function checkAuth() {
    global $skip_auth_check;
    
    // Skip check if flag is set
    if ($skip_auth_check) {
        return;
    }
    
    // Get the current script name
    $current_page = basename($_SERVER['SCRIPT_NAME']);
    $allowed_pages = ['login.php', 'logout.php'];
    
    // Check if the current page is allowed without authentication
    if (!in_array($current_page, $allowed_pages)) {
        // Check if the user is logged in, redirect to login if not
        if (!isLoggedIn()) {
            // Save the requested URL for redirection after login
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            
            // Set error message
            $_SESSION['error'] = 'Veuillez vous connecter pour accéder à cette page.';
            
            // Redirect to login page
            header('Location: /login.php');
            exit;
        }
    }
}

// Set skip_auth_check flag if we're on login or logout page
$current_page = basename($_SERVER['SCRIPT_NAME']);
if (in_array($current_page, ['login.php', 'logout.php'])) {
    $skip_auth_check = true;
}

// Automatically check authentication when this file is included
checkAuth(); 