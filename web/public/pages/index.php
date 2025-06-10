<?php
// Page d'accueil du système de monitoring ECG
$pageTitle = "Accueil";
$extraCss = "/css/pages/index.css";
// La session est déjà démarrée par auth.php
include_once '../../includes/header.php';

// Récupération des diagnostics récents
require_once '../../config/database.php';
require_once '../../config/security.php';
require_once '../../includes/functions.php';

try {
    $sql = "SELECT d.id, d.patient_id, d.created_at,
                p.name_encoded, p.phone, p.blood_type
            FROM diagnostics d
            JOIN patients p ON d.patient_id = p.id
            ORDER BY d.created_at DESC
            LIMIT 5";
    
    $diagnostics = fetchAll($sql);
} catch (Exception $e) {
    $diagnostics = [];
    $_SESSION['error'] = 'Erreur lors de la récupération des diagnostics: ' . ($DEBUG ? $e->getMessage() : 'Contactez l\'administrateur');
}
?>

<div class="row">
    <div class="col-md-12">
        <div class="jumbotron">
            <h1 class="display-4">Système de Monitoring ECG</h1>
            <p class="lead">Bienvenue dans l'application de monitoring cardiaque basée sur Raspberry Pi et le capteur AD8232.</p>
            <p>Ce système permet d'acquérir, visualiser et diagnostiquer des signaux ECG.</p>
            <hr class="my-4">
        </div>
    </div>
</div>

<div class="row mt-5">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2"></i>Diagnostics récents</h3>
            </div>
            <div class="card-body">
                <?php if (empty($diagnostics)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Aucun diagnostic n'a encore été enregistré.
                        <div class="mt-2">
                            <a href="/pages/diagnostic.php" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus me-1"></i>Créer un diagnostic
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Patient</th>
                                    <th>Groupe sanguin</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($diagnostics as $diagnostic): ?>
                                <tr>
                                    <td class="text-center"><?php echo $diagnostic['id']; ?></td>
                                    <td class="text-center"><?php echo decodeBase64($diagnostic['name_encoded']); ?></td>
                                    <td class="text-center"><span class="badge bg-primary text-white"><?php echo $diagnostic['blood_type']; ?></span></td>
                                    <td class="text-center"><?php echo formatDate($diagnostic['created_at']); ?></td>
                                    <td class="text-center">
                                        <a href="/pages/diagnostic.php?id=<?php echo $diagnostic['id']; ?>" class="btn btn-sm btn-primary me-1">
                                            <i class="fas fa-eye"></i> Voir
                                        </a>
                                        <a href="/pages/diagnostic_details.php?id=<?php echo $diagnostic['id']; ?>" class="btn btn-sm btn-success">
                                            <i class="fas fa-heartbeat"></i> ECG
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <a href="/pages/diagnostic.php" class="btn btn-outline-primary">
                            <i class="fas fa-list me-1"></i>Voir tous les diagnostics
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-5">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-heartbeat mr-2"></i>À propos de l'ECG</h3>
            </div>
            <div class="card-body">
                <p>L'électrocardiogramme (ECG) est un examen médical qui enregistre l'activité électrique du cœur.</p>
                <p>Il permet de détecter diverses anomalies cardiaques comme :</p>
                <ul>
                    <li>Les troubles du rythme cardiaque</li>
                    <li>Les problèmes de conduction électrique</li>
                    <li>L'hypertrophie des cavités cardiaques</li>
                    <li>L'ischémie myocardique</li>
                </ul>
                <p>Notre système utilise le capteur AD8232 connecté à un Raspberry Pi pour offrir une solution accessible et fiable.</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Comment utiliser</h3>
            </div>
            <div class="card-body">
                <ol>
                    <li>
                        <strong>Enregistrement patient :</strong> 
                        <p>Enregistrez les informations du patient dans le système.</p>
                    </li>
                    <li>
                        <strong>Acquisition :</strong> 
                        <p>Connectez les électrodes et lancez l'acquisition pour la durée souhaitée.</p>
                    </li>
                    <li>
                        <strong>Visualisation :</strong> 
                        <p>Examinez le tracé ECG avec les outils d'analyse fournis.</p>
                    </li>
                    <li>
                        <strong>Diagnostic :</strong> 
                        <p>Entrez vos observations et diagnostics pour les sauvegarder dans le système.</p>
                    </li>
                </ol>
                <a href="/pages/diagnostic.php" class="btn btn-success mt-3">
                    <i class="fas fa-play me-1"></i>Commencer
                </a>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?> 