<?php
/**
 * Utility functions for the application
 */

/**
 * Redirect to a specified URL
 *
 * @param string $url URL to redirect to
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Display a success message in an alert
 *
 * @param string $message Message to display
 * @return string HTML code for the alert
 */
function showSuccess($message) {
    return '<div class="alert alert-success">' . $message . '</div>';
}

/**
 * Display an error message in an alert
 *
 * @param string $message Message to display
 * @return string HTML code for the alert
 */
function showError($message) {
    return '<div class="alert alert-danger">' . $message . '</div>';
}

/**
 * Display an information message in an alert
 *
 * @param string $message Message to display
 * @return string HTML code for the alert
 */
function showInfo($message) {
    return '<div class="alert alert-info">' . $message . '</div>';
}

/**
 * Format date in locale format
 *
 * @param string $date Date in MySQL format
 * @return string Date in locale format (dd/mm/yyyy)
 */
function formatDate($date) {
    $timestamp = strtotime($date);
    return date('d/m/Y', $timestamp);
}

/**
 * Format an ECG value for display
 *
 * @param float $value Value to format
 * @return string Formatted value
 */
function formatEcgValue($value) {
    return number_format($value, 2, '.', ' ') . ' mV';
}

/**
 * Function to generate a unique ID for an acquisition session
 *
 * @return string Unique ID
 */
function generateSessionId() {
    return 'ECG-' . date('Ymd') . '-' . bin2hex(random_bytes(4));
}

/**
 * Create a filename for ECG data export
 * 
 * @param int $patientId Patient ID
 * @param string $date Date in Y-m-d format
 * @return string Filename
 */
function createEcgFileName($patientId, $date = null) {
    $date = $date ?: date('Y-m-d');
    return "ecg_" . $patientId . "_" . $date . ".csv";
} 