<?php
// Page de diagnostic du système de monitoring ECG
$pageTitle = "Diagnostic";
$extraCss = "/css/pages/diagnostic.css";

include_once '../../includes/header.php';

// Récupération des diagnostics existants ou du diagnostic spécifique demandé
require_once '../../config/database.php';
require_once '../../config/security.php';
require_once '../../includes/functions.php';

// Fonction de nettoyage des entrées utilisateur
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

$diagnosticId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$diagnostic = null;
$patient = null;

// Si on a un ID de diagnostic spécifique
if ($diagnosticId > 0) {
    try {
        // Récupération des données du diagnostic
        $sql = "SELECT d.*, p.name_encoded, p.phone, p.address_encoded, p.blood_type 
                FROM diagnostics d 
                JOIN patients p ON d.patient_id = p.id 
                WHERE d.id = ?";
        $diagnostic = fetchOne($sql, [$diagnosticId]);
        
        if ($diagnostic) {
            $patient = $diagnostic; // Les données patient sont incluses dans le résultat
            
            // Récupération des données ECG pour ce diagnostic
            $sql = "SELECT * FROM ecg_data WHERE diagnostic_id = ? ORDER BY image_created_at";
            $ecgRawData = fetchAll($sql, [$diagnosticId]);
            
            // Préparation des données ECG pour JavaScript
            $ecgData = [];
            $timeData = [];
            
            // Si aucune donnée ECG n'est trouvée, on simule des données pour la démonstration
            if (empty($ecgRawData)) {
                // Génération de données ECG simulées pour la démonstration
                $sampleCount = 500;
                $frequency = 250; // Hz
                $duration = 10; // secondes
                $sampleCount = $frequency * $duration;
                
                // Génération d'un signal ECG basique simulé
                for ($i = 0; $i < $sampleCount; $i++) {
                    $time = $i / $frequency;
                    
                    // Simulation d'un signal ECG basique avec ondes sinusoïdales et impulsions
                    $value = 0.5 * sin(2 * M_PI * 1 * $time); // Composante de base
                    
                    // Ajout des ondes P, QRS et T
                    if ($i % 250 >= 50 && $i % 250 <= 60) {
                        // Onde P
                        $value += 0.25 * sin(2 * M_PI * 10 * ($time - floor($time)));
                    } else if ($i % 250 >= 100 && $i % 250 <= 110) {
                        // Complexe QRS (onde Q)
                        $value -= 0.2;
                    } else if ($i % 250 >= 110 && $i % 250 <= 115) {
                        // Complexe QRS (onde R)
                        $value += 1.0;
                    } else if ($i % 250 >= 115 && $i % 250 <= 125) {
                        // Complexe QRS (onde S)
                        $value -= 0.3;
                    } else if ($i % 250 >= 150 && $i % 250 <= 180) {
                        // Onde T
                        $value += 0.3 * sin(2 * M_PI * 5 * ($time - floor($time)));
                    }
                    
                    // Ajout de bruit aléatoire
                    $value += (mt_rand(-10, 10) / 100);
                    
                    $timeData[] = $time;
                    $ecgData[] = $value;
                }
                
                // Positions des ondes pour la démonstration
                $wavePositions = [
                    'p' => 55,
                    'q' => 100,
                    'r' => 112,
                    's' => 120,
                    't' => 165
                ];
                
                $isSimulated = true;
            } else {
                // Transformation des données réelles
                foreach ($ecgRawData as $sample) {
                    $timeData[] = (float)$sample['timestamp'];
                    $ecgData[] = (float)$sample['value'];
                }
                
                // Indices approximatifs des ondes
                $totalSamples = count($ecgData);
                $wavePositions = [
                    'p' => (int)($totalSamples * 0.05),
                    'q' => (int)($totalSamples * 0.1),
                    'r' => (int)($totalSamples * 0.12),
                    's' => (int)($totalSamples * 0.15),
                    't' => (int)($totalSamples * 0.25)
                ];
                
                $isSimulated = false;
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Erreur lors de la récupération du diagnostic: ' . ($DEBUG ? $e->getMessage() : 'Contactez l\'administrateur');
    }
}

// Récupération de tous les diagnostics pour la liste
try {
    $sql = "SELECT d.id, d.created_at, p.name_encoded, p.blood_type
            FROM diagnostics d
            JOIN patients p ON d.patient_id = p.id
            ORDER BY d.created_at DESC";
    $diagnostics = fetchAll($sql);
} catch (Exception $e) {
    $diagnostics = [];
    $_SESSION['error'] = 'Erreur lors de la récupération des diagnostics: ' . ($DEBUG ? $e->getMessage() : 'Contactez l\'administrateur');
}

// Traitement du formulaire de création/modification de diagnostic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientId = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
    
    // Validation
    if ($patientId <= 0) {
        $_SESSION['error'] = 'Veuillez sélectionner un patient.';
    } else {
        try {
            $diagnosticData = [
                'patient_id' => $patientId
            ];
            
            if ($diagnosticId > 0) {
                // Mise à jour d'un diagnostic existant
                $updated = update('diagnostics', $diagnosticData, 'id', $diagnosticId);
                if ($updated) {
                    $_SESSION['success'] = 'Diagnostic mis à jour avec succès.';
                    redirect('/pages/diagnostic.php?id=' . $diagnosticId);
                } else {
                    $_SESSION['error'] = 'Erreur lors de la mise à jour du diagnostic.';
                }
            } else {
                // Création d'un nouveau diagnostic
                $newDiagnosticId = insert('diagnostics', $diagnosticData);
                if ($newDiagnosticId) {
                    $_SESSION['success'] = 'Nouveau diagnostic créé avec succès.';
                    redirect('/pages/diagnostic.php?id=' . $newDiagnosticId);
                } else {
                    $_SESSION['error'] = 'Erreur lors de la création du diagnostic.';
                }
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Une erreur est survenue: ' . ($DEBUG ? $e->getMessage() : 'Contactez l\'administrateur');
        }
    }
}

// Récupération des patients disponibles pour le formulaire de nouveau diagnostic
try {
    $sql = "SELECT id, name_encoded, blood_type, created_at
            FROM patients
            ORDER BY created_at DESC";
    $patientsDisponibles = fetchAll($sql);
} catch (Exception $e) {
    $patientsDisponibles = [];
}
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2><i class="fas fa-stethoscope me-2"></i>Diagnostic ECG</h2>
        <p class="lead">Visualisez et analysez les enregistrements ECG</p>
    </div>
</div>

<?php if ($diagnostic): ?>
<!-- Affichage d'un diagnostic spécifique -->
<div class="card mb-4">
    <div class="card-header d-flex justify-between align-items-center">
        <h3 class="card-title align-self-center">
            <i class="fas fa-file-medical me-2"></i>
            Diagnostic #<?php echo $diagnostic['id']; ?> - 
            <?php echo decodeBase64($diagnostic['name_encoded']); ?>
        </h3>
        <div>
            <button class="btn btn-outline-primary print-button" onclick="printPage()">
                <i class="fas fa-print me-2"></i>Imprimer
            </button>
        </div>
    </div>
    <div class="card-body">
        <!-- Informations patient -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h4 class="mb-3">Informations du patient</h4>
                <p><strong>Nom:</strong> <?php echo decodeBase64($diagnostic['name_encoded']); ?></p>
                <p><strong>Téléphone:</strong> <?php echo $diagnostic['phone']; ?></p>
                <p><strong>Adresse:</strong> <?php echo decodeBase64($diagnostic['address_encoded']); ?></p>
            </div>
            <div class="col-md-6">
                <h4 class="mb-3">Détails du diagnostic</h4>
                <p><strong>Groupe sanguin:</strong> <span class="badge bg-primary blood-type-badge"><?php echo $diagnostic['blood_type']; ?></span></p>
                <p><strong>Date:</strong> <?php echo formatDate($diagnostic['created_at']); ?></p>
            </div>
        </div>
        
        <!-- Visualisation ECG -->
        <div class="ecg-visualization">
            <h3>Électrocardiogramme</h3>
            <?php if (isset($ecgData) && !empty($ecgData)): ?>
                <div id="ecg-chart-container">
                    <canvas id="ecg-chart" class="ecg-chart-container"></canvas>
                </div>
                
                <script>
                // Transmission directe des données ECG au JavaScript
                document.addEventListener('DOMContentLoaded', function() {
                    const ecgData = <?php echo json_encode($ecgData); ?>;
                    const timeData = <?php echo json_encode($timeData); ?>;
                    const wavePositions = <?php echo json_encode($wavePositions); ?>;
                    
                    // Création du graphique
                    const chart = createAdvancedEcgChart('ecg-chart', ecgData, timeData, wavePositions);
                });
                </script>
            <?php else: ?>
                <div id="loading-indicator" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-2">Chargement des données ECG...</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Liste des diagnostics ou formulaire de création -->
<div class="row">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Diagnostics récents</h3>
            </div>
            <div class="card-body">
                <?php if (empty($diagnostics)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Aucun diagnostic n'a encore été enregistré.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Patient</th>
                                    <th>Date</th>
                                    <th>Groupe sanguin</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($diagnostics as $d): ?>
                                <tr>
                                    <td class="text-center"><?php echo $d['id']; ?></td>
                                    <td class="text-center"><?php echo decodeBase64($d['name_encoded']); ?></td>
                                    <td class="text-center"><?php echo formatDate($d['created_at']); ?></td>
                                    <td class="text-center"><span class="badge bg-primary text-white"><?php echo $d['blood_type']; ?></span></td>
                                    <td class="text-center">
                                        <a href="?id=<?php echo $d['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye" style="margin:0px;"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Nouveau diagnostic</h3>
            </div>
            <div class="card-body">
                <?php if (empty($patientsDisponibles)): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Aucun patient disponible pour créer un nouveau diagnostic.
                        <div class="mt-2">
                            <a href="/pages/patients.php" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus me-1"></i>Ajouter un patient
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                        <div class="mb-3">
                            <label for="patient_id" class="form-label">Patient *</label>
                            <select class="form-select" id="patient_id" name="patient_id" required>
                                <option value="" selected disabled>Sélectionnez un patient...</option>
                                <?php foreach ($patientsDisponibles as $patient): ?>
                                <option value="<?php echo $patient['id']; ?>">
                                    <?php echo decodeBase64($patient['name_encoded']); ?> - 
                                    <?php echo $patient['blood_type']; ?> - 
                                    <?php echo formatDate($patient['created_at']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i>Créer le diagnostic
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include_once '../../includes/footer.php'; ?> 