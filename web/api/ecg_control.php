<?php
/**
 * API de contrôle ECG
 * Interface entre le frontend web et le service Python
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Gérer les requêtes OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Vérifier l'authentification
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

// URL du service Python
$ECG_SERVICE_URL = $_ENV['ECG_SERVICE_URL'] ?? 'http://ecg-python:5000';

/**
 * Effectuer une requête HTTP vers le service Python
 */
function makeHttpRequest($url, $method = 'GET', $data = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    switch (strtoupper($method)) {
        case 'POST':
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            break;
        case 'PUT':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            break;
        case 'DELETE':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        return ['error' => 'Erreur de connexion: ' . $error, 'http_code' => 0];
    }
    
    $decoded = json_decode($response, true);
    return ['data' => $decoded, 'http_code' => $httpCode];
}

/**
 * Vérifier que le diagnostic existe et appartient à l'utilisateur
 */
function validateDiagnosticAccess($diagnosticId) {
    try {
        $sql = "SELECT d.id, d.patient_id, p.name_encoded 
                FROM diagnostics d 
                JOIN patients p ON d.patient_id = p.id 
                WHERE d.id = ?";
        
        $result = fetchOne($sql, [$diagnosticId]);
        
        if (!$result) {
            http_response_code(404);
            echo json_encode(['error' => 'Diagnostic non trouvé']);
            exit();
        }
        
        return $result;
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur de validation']);
        exit();
    }
}

// Récupérer la méthode et l'action
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uriParts = explode('/', trim($uri, '/'));

// Structure d'URL: /api/ecg_control.php/action/diagnostic_id
$action = $uriParts[count($uriParts) - 2] ?? '';
$diagnosticId = $uriParts[count($uriParts) - 1] ?? '';

// Valider l'ID du diagnostic
if (!empty($diagnosticId) && !is_numeric($diagnosticId)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de diagnostic invalide']);
    exit();
}

try {
    switch ($action) {
        case 'start':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Méthode non autorisée']);
                exit();
            }
            
            $diagnostic = validateDiagnosticAccess($diagnosticId);
            
            // Démarrer la capture via le service Python
            $response = makeHttpRequest($ECG_SERVICE_URL . '/capture/start/' . $diagnosticId, 'POST');
            
            if ($response['http_code'] === 200) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Capture ECG démarrée',
                    'diagnostic_id' => $diagnosticId,
                    'data' => $response['data']
                ]);
            } else {
                http_response_code($response['http_code'] ?: 500);
                echo json_encode([
                    'error' => $response['data']['error'] ?? 'Erreur lors du démarrage',
                    'diagnostic_id' => $diagnosticId
                ]);
            }
            break;
            
        case 'stop':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Méthode non autorisée']);
                exit();
            }
            
            $diagnostic = validateDiagnosticAccess($diagnosticId);
            
            // Arrêter la capture via le service Python
            $response = makeHttpRequest($ECG_SERVICE_URL . '/capture/stop/' . $diagnosticId, 'POST');
            
            if ($response['http_code'] === 200) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Capture ECG arrêtée',
                    'diagnostic_id' => $diagnosticId,
                    'data' => $response['data']
                ]);
            } else {
                http_response_code($response['http_code'] ?: 500);
                echo json_encode([
                    'error' => $response['data']['error'] ?? 'Erreur lors de l\'arrêt',
                    'diagnostic_id' => $diagnosticId
                ]);
            }
            break;
            
        case 'status':
            if ($method !== 'GET') {
                http_response_code(405);
                echo json_encode(['error' => 'Méthode non autorisée']);
                exit();
            }
            
            $diagnostic = validateDiagnosticAccess($diagnosticId);
            
            // Récupérer le statut via le service Python
            $response = makeHttpRequest($ECG_SERVICE_URL . '/capture/status/' . $diagnosticId, 'GET');
            
            if ($response['http_code'] === 200) {
                echo json_encode([
                    'success' => true,
                    'diagnostic_id' => $diagnosticId,
                    'data' => $response['data']
                ]);
            } else {
                http_response_code($response['http_code'] ?: 500);
                echo json_encode([
                    'error' => $response['data']['error'] ?? 'Erreur lors de la récupération du statut',
                    'diagnostic_id' => $diagnosticId
                ]);
            }
            break;
            
        case 'images':
            if ($method !== 'GET') {
                http_response_code(405);
                echo json_encode(['error' => 'Méthode non autorisée']);
                exit();
            }
            
            $diagnostic = validateDiagnosticAccess($diagnosticId);
            
            // Récupérer les images via le service Python
            $response = makeHttpRequest($ECG_SERVICE_URL . '/images/' . $diagnosticId, 'GET');
            
            if ($response['http_code'] === 200) {
                echo json_encode([
                    'success' => true,
                    'diagnostic_id' => $diagnosticId,
                    'data' => $response['data']
                ]);
            } else {
                http_response_code($response['http_code'] ?: 500);
                echo json_encode([
                    'error' => $response['data']['error'] ?? 'Erreur lors de la récupération des images',
                    'diagnostic_id' => $diagnosticId
                ]);
            }
            break;
            
        case 'image':
            if ($method !== 'GET') {
                http_response_code(405);
                echo json_encode(['error' => 'Méthode non autorisée']);
                exit();
            }
            
            // Pour les images individuelles, l'ID est l'ID de l'image, pas du diagnostic
            $imageId = $diagnosticId; // Dans ce cas, c'est l'ID de l'image
            
            if (!is_numeric($imageId)) {
                http_response_code(400);
                echo json_encode(['error' => 'ID d\'image invalide']);
                exit();
            }
            
            // Vérifier que l'image existe et récupérer le diagnostic associé
            $sql = "SELECT e.id, e.diagnostic_id 
                    FROM ecg_data e 
                    JOIN diagnostics d ON e.diagnostic_id = d.id 
                    WHERE e.id = ?";
            
            $imageInfo = fetchOne($sql, [$imageId]);
            
            if (!$imageInfo) {
                http_response_code(404);
                echo json_encode(['error' => 'Image non trouvée']);
                exit();
            }
            
            // Récupérer l'image via le service Python
            $response = makeHttpRequest($ECG_SERVICE_URL . '/image/' . $imageId, 'GET');
            
            if ($response['http_code'] === 200) {
                echo json_encode([
                    'success' => true,
                    'image_id' => $imageId,
                    'data' => $response['data']
                ]);
            } else {
                http_response_code($response['http_code'] ?: 500);
                echo json_encode([
                    'error' => $response['data']['error'] ?? 'Erreur lors de la récupération de l\'image',
                    'image_id' => $imageId
                ]);
            }
            break;
            
        case 'health':
            if ($method !== 'GET') {
                http_response_code(405);
                echo json_encode(['error' => 'Méthode non autorisée']);
                exit();
            }
            
            // Vérifier la santé du service Python
            $response = makeHttpRequest($ECG_SERVICE_URL . '/health', 'GET');
            
            if ($response['http_code'] === 200) {
                echo json_encode([
                    'success' => true,
                    'service_status' => 'healthy',
                    'data' => $response['data']
                ]);
            } else {
                http_response_code(503);
                echo json_encode([
                    'error' => 'Service ECG indisponible',
                    'service_status' => 'unhealthy'
                ]);
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Action non trouvée']);
            break;
    }
    
} catch (Exception $e) {
    error_log("ECG Control API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur interne du serveur']);
}
?> 