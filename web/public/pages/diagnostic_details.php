<?php
$pageTitle = "Détails du Diagnostic";
$extraCss = "/css/pages/diagnostic_details.css";
$extraJs = "/js/ecg-realtime.js";

require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../config/security.php';
require_once '../../includes/functions.php';

// Vérifier l'authentification
if (!isLoggedIn()) {
    header('Location: /auth/login.php');
    exit();
}

// Récupérer l'ID du diagnostic
$diagnosticId = $_GET['id'] ?? 0;

if (!$diagnosticId || !is_numeric($diagnosticId)) {
    $_SESSION['error'] = 'ID de diagnostic invalide';
    header('Location: /pages/diagnostic.php');
    exit();
}

// Récupérer les informations du diagnostic
try {
    $sql = "SELECT d.id, d.patient_id, d.created_at,
                   p.name_encoded, p.phone, p.blood_type, p.address_encoded
            FROM diagnostics d
            JOIN patients p ON d.patient_id = p.id
            WHERE d.id = ?";
    
    $diagnostic = fetchOne($sql, [$diagnosticId]);
    
    if (!$diagnostic) {
        $_SESSION['error'] = 'Diagnostic non trouvé';
        header('Location: /pages/diagnostic.php');
        exit();
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur lors de la récupération du diagnostic';
    header('Location: /pages/diagnostic.php');
    exit();
}

include_once '../../includes/header.php';
?>

<div class="container-fluid">
    <!-- En-tête du diagnostic -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-heartbeat me-2"></i>
                        Diagnostic #<?php echo $diagnostic['id']; ?>
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5><i class="fas fa-user me-2"></i>Informations Patient</h5>
                            <p><strong>Nom :</strong> <?php echo decodeBase64($diagnostic['name_encoded']); ?></p>
                            <p><strong>Téléphone :</strong> <?php echo $diagnostic['phone']; ?></p>
                            <p><strong>Groupe sanguin :</strong> 
                                <span class="badge bg-primary"><?php echo $diagnostic['blood_type']; ?></span>
                            </p>
                            <p><strong>Adresse :</strong> <?php echo decodeBase64($diagnostic['address_encoded']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5><i class="fas fa-calendar me-2"></i>Informations Diagnostic</h5>
                            <p><strong>Date de création :</strong> <?php echo formatDate($diagnostic['created_at']); ?></p>
                            <div class="mt-3">
                                <a href="/pages/diagnostic.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>Retour aux diagnostics
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contrôles de capture ECG -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-play-circle me-2"></i>
                        Contrôle de Capture ECG
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <div class="d-grid gap-2">
                                <button id="startCaptureBtn" class="btn btn-success btn-lg">
                                    <i class="fas fa-play me-2"></i>Démarrer la Capture
                                </button>
                                <button id="stopCaptureBtn" class="btn btn-danger btn-lg" style="display: none;">
                                    <i class="fas fa-stop me-2"></i>Arrêter la Capture
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <div id="captureStatus" class="status-indicator">
                                    <div class="status-light status-idle"></div>
                                    <div class="status-text">Prêt</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="capture-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Images capturées:</span>
                                    <span id="imageCount" class="stat-value">0</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Durée:</span>
                                    <span id="captureTime" class="stat-value">00:00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Galerie des images ECG -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-images me-2"></i>
                        Galerie des Captures ECG
                    </h4>
                    <div>
                        <button id="refreshGalleryBtn" class="btn btn-outline-primary">
                            <i class="fas fa-sync-alt me-1"></i>Actualiser
                        </button>
                        <div class="btn-group ms-2" role="group">
                            <button id="gridViewBtn" class="btn btn-outline-secondary active">
                                <i class="fas fa-th-large"></i>
                            </button>
                            <button id="listViewBtn" class="btn btn-outline-secondary">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="loadingGallery" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-2">Chargement des images...</p>
                    </div>
                    
                    <div id="galleryContainer" style="display: none;">
                        <div id="imageGallery" class="image-gallery-grid">
                            <!-- Les images seront chargées ici via JavaScript -->
                        </div>
                        
                        <div id="noImagesMessage" class="text-center py-5" style="display: none;">
                            <i class="fas fa-image fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Aucune image disponible</h5>
                            <p class="text-muted">Démarrez une capture ECG pour voir les images ici.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour affichage d'image en grand -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">Image ECG</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" alt="Image ECG" class="img-fluid">
                <div id="imageInfo" class="mt-3">
                    <!-- Informations sur l'image -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" id="downloadImageBtn" class="btn btn-primary">
                    <i class="fas fa-download me-1"></i>Télécharger
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Configuration globale
const ECG_CONFIG = {
    diagnosticId: <?php echo $diagnosticId; ?>,
    apiBaseUrl: '/api/ecg_control.php',
    refreshInterval: 3000, // 3 secondes
    maxRetries: 3
};

// Initialiser la page
document.addEventListener('DOMContentLoaded', function() {
    if (typeof ECGRealtimeController !== 'undefined') {
        window.ecgController = new ECGRealtimeController(ECG_CONFIG);
        window.ecgController.init();
    } else {
        console.error('ECGRealtimeController not loaded');
    }
});
</script>

<?php include_once '../../includes/footer.php'; ?> 