<?php
// Activer la mise en tampon de sortie pour permettre l'envoi d'en-têtes après l'affichage de contenu
ob_start();

// Page de configuration du système de monitoring ECG
$pageTitle = "Configuration";
$extraCss = "/css/pages/configuration.css";
require_once '../../config/database.php';
require_once '../../config/security.php';
include_once '../../includes/header.php';

// Vérifier si le formulaire a été soumis
$formSubmitted = false;
$formSuccess = false;
$formError = '';
$errors = []; // Tableau pour stocker les erreurs par champ
$redirectToUrl = ''; // Pour stocker l'URL de redirection si besoin

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formSubmitted = true;
    
    // Récupération des données du formulaire
    $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
    $numeroSecu = isset($_POST['numero_secu']) ? trim($_POST['numero_secu']) : '';
    $telephone = isset($_POST['telephone']) ? trim($_POST['telephone']) : '';
    $adresse = isset($_POST['adresse']) ? trim($_POST['adresse']) : '';
    $groupeSanguin = isset($_POST['groupe_sanguin']) ? trim($_POST['groupe_sanguin']) : '';
    $tempsAcquisition = isset($_POST['temps_acquisition']) ? (int)$_POST['temps_acquisition'] : 0;
    
    // Validation des données
    if (empty($nom)) {
        $errors['nom'] = 'Le nom est requis';
    }
    
    if (empty($numeroSecu)) {
        $errors['numero_secu'] = 'Le numéro de sécurité sociale est requis';
    } elseif (!preg_match('/^\d{15}$/', $numeroSecu)) {
        $errors['numero_secu'] = 'Le numéro de sécurité sociale doit contenir 15 chiffres';
    }
    
    if (empty($telephone)) {
        $errors['telephone'] = 'Le téléphone est requis';
    }
    
    if (empty($adresse)) {
        $errors['adresse'] = 'L\'adresse est requise';
    }
    
    if (empty($groupeSanguin)) {
        $errors['groupe_sanguin'] = 'Le groupe sanguin est requis';
    }
    
    if ($tempsAcquisition <= 0) {
        $errors['temps_acquisition'] = 'Le temps d\'acquisition doit être supérieur à 0';
    }
    
    // Si aucune erreur, procéder à l'enregistrement
    if (empty($errors)) {
        try {
            // Hashage et encodage des données sensibles
            $nomHash = hashSensitiveData($nom);
            $nomEncoded = encodeBase64($nom);
            $numeroSecuHash = hashSensitiveData($numeroSecu);
            $numeroSecuEncoded = encodeBase64($numeroSecu);
            $adresseEncoded = encodeBase64($adresse);
            
            // Connexion à la base de données
            $db = getDbConnection();
            
            // Insertion des données du patient
            $patientData = [
                'name_hash' => $nomHash,
                'name_encoded' => $nomEncoded,
                'secu_hash' => $numeroSecuHash,
                'secu_encoded' => $numeroSecuEncoded,
                'phone' => $telephone,
                'address_encoded' => $adresseEncoded,
                'blood_type' => $groupeSanguin
            ];
            
            $patientId = insert('patients', $patientData);
            
            if ($patientId) {
                // Insertion de la configuration
                $configData = [
                    'patient_id' => $patientId,
                    'acquisition_time' => $tempsAcquisition
                ];
                
                $configId = insert('configurations', $configData);
                
                if ($configId) {
                    $formSuccess = true;
                    $_SESSION['success'] = 'Configuration enregistrée avec succès. ID de configuration: ' . $configId;
                    $_SESSION['config_id'] = $configId;
                    $_SESSION['patient_id'] = $patientId;
                    
                    // Au lieu d'utiliser header(), nous allons utiliser une variable pour rediriger avec JavaScript
                    $redirectToUrl = '/pages/diagnostic.php?config_id=' . $configId;
                } else {
                    $formError = 'Erreur lors de l\'enregistrement de la configuration.';
                }
            } else {
                $formError = 'Erreur lors de l\'enregistrement des informations du patient.';
            }
        } catch (Exception $e) {
            $formError = 'Une erreur est survenue: ' . ($DEBUG ? $e->getMessage() : 'Contactez l\'administrateur');
        }
    } else {
        $formError = 'Veuillez corriger les erreurs dans le formulaire.';
    }
}
?>

<div class="config-header">
    <h2 class="page-title"><i class="fas fa-cog icon-spacing"></i>Configuration du système ECG</h2>
    <p class="page-subtitle">Entrez les informations du patient et configurez les paramètres d'acquisition</p>
</div>

<?php if ($formSubmitted && !empty($formError)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle icon-spacing"></i>
        <strong>Erreur!</strong> <?php echo $formError; ?>
    </div>
<?php endif; ?>

<?php if ($formSuccess): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle icon-spacing"></i>
        <strong>Succès!</strong> La configuration a été enregistrée avec succès.
        <div class="mt-3">
            <a href="/pages/diagnostic.php" class="btn btn-primary">
                <i class="fas fa-stethoscope"></i>Aller au diagnostic
            </a>
        </div>
    </div>
<?php endif; ?>

<div class="card config-card">
    <div class="card-header">
        <h3 class="card-title">Informations du patient et paramètres</h3>
    </div>
    <div class="card-body">
        <form id="config-form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="needs-validation">
            <div class="config-section">
                <h4 class="section-title"><i class="fas fa-user icon-spacing"></i>Informations personnelles</h4>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nom" class="form-label">Nom complet</label>
                        <div class="flex items-center">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control <?php echo isset($errors['nom']) ? 'is-invalid' : ''; ?>" 
                                id="nom" name="nom" value="<?php echo isset($nom) ? htmlspecialchars($nom) : ''; ?>">
                        </div>
                        <?php if (isset($errors['nom'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['nom']; ?></div>
                        <?php endif; ?>
                        <small class="text-muted">Cette information sera cryptée</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="numero_secu" class="form-label">Numéro de sécurité sociale</label>
                        <div class="flex items-center">
                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            <input type="text" class="form-control <?php echo isset($errors['numero_secu']) ? 'is-invalid' : ''; ?>" 
                                id="numero_secu" name="numero_secu" value="<?php echo isset($numeroSecu) ? htmlspecialchars($numeroSecu) : ''; ?>">
                        </div>
                        <?php if (isset($errors['numero_secu'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['numero_secu']; ?></div>
                        <?php endif; ?>
                        <small class="text-muted">Cette information sera cryptée</small>
                    </div>
                </div>
                
                <div class="form-row-2-col">
                    <div class="form-group">
                        <label for="telephone" class="form-label">Numéro de téléphone</label>
                        <div class="flex items-center">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="tel" class="form-control <?php echo isset($errors['telephone']) ? 'is-invalid' : ''; ?>" 
                                id="telephone" name="telephone" value="<?php echo isset($telephone) ? htmlspecialchars($telephone) : ''; ?>">
                        </div>
                        <?php if (isset($errors['telephone'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['telephone']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="groupe_sanguin" class="form-label">Groupe sanguin</label>
                        <select class="form-select <?php echo isset($errors['groupe_sanguin']) ? 'is-invalid' : ''; ?>" 
                            id="groupe_sanguin" name="groupe_sanguin">
                            <option value="" <?php echo !isset($groupeSanguin) || empty($groupeSanguin) ? 'selected' : ''; ?> disabled>Sélectionnez...</option>
                            <option value="A+" <?php echo isset($groupeSanguin) && $groupeSanguin === 'A+' ? 'selected' : ''; ?>>A+</option>
                            <option value="A-" <?php echo isset($groupeSanguin) && $groupeSanguin === 'A-' ? 'selected' : ''; ?>>A-</option>
                            <option value="B+" <?php echo isset($groupeSanguin) && $groupeSanguin === 'B+' ? 'selected' : ''; ?>>B+</option>
                            <option value="B-" <?php echo isset($groupeSanguin) && $groupeSanguin === 'B-' ? 'selected' : ''; ?>>B-</option>
                            <option value="AB+" <?php echo isset($groupeSanguin) && $groupeSanguin === 'AB+' ? 'selected' : ''; ?>>AB+</option>
                            <option value="AB-" <?php echo isset($groupeSanguin) && $groupeSanguin === 'AB-' ? 'selected' : ''; ?>>AB-</option>
                            <option value="O+" <?php echo isset($groupeSanguin) && $groupeSanguin === 'O+' ? 'selected' : ''; ?>>O+</option>
                            <option value="O-" <?php echo isset($groupeSanguin) && $groupeSanguin === 'O-' ? 'selected' : ''; ?>>O-</option>
                        </select>
                        <?php if (isset($errors['groupe_sanguin'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['groupe_sanguin']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="adresse" class="form-label">Adresse</label>
                    <div class="flex items-center">
                        <span class="input-group-text"><i class="fas fa-home"></i></span>
                        <textarea class="form-control <?php echo isset($errors['adresse']) ? 'is-invalid' : ''; ?>" 
                            id="adresse" name="adresse" rows="3"><?php echo isset($adresse) ? htmlspecialchars($adresse) : ''; ?></textarea>
                    </div>
                    <?php if (isset($errors['adresse'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['adresse']; ?></div>
                    <?php endif; ?>
                    <small class="text-muted">Cette information sera cryptée</small>
                </div>
            </div>
            
            <div class="config-section">
                <h4 class="section-title"><i class="fas fa-clock icon-spacing"></i>Paramètres d'acquisition</h4>
                
                <div class="form-group">
                    <label for="temps_acquisition" class="param-label">Temps d'acquisition (secondes)</label>
                    <div class="flex items-center">
                        <span class="input-group-text"><i class="fas fa-clock"></i></span>
                        <input type="number" class="form-control <?php echo isset($errors['temps_acquisition']) ? 'is-invalid' : ''; ?>" 
                            id="temps_acquisition" name="temps_acquisition" min="1" 
                            value="<?php echo isset($tempsAcquisition) ? $tempsAcquisition : '60'; ?>">
                    </div>
                    <?php if (isset($errors['temps_acquisition'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['temps_acquisition']; ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="config-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save icon-spacing"></i>Enregistrer la configuration
                </button>
                <a href="/index.php" class="btn btn-secondary">
                    <i class="fas fa-times icon-spacing"></i>Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<style>
.invalid-feedback {
    display: block;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875em;
    color: #dc3545;
}
.form-control.is-invalid, .form-select.is-invalid {
    border-color: #dc3545;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validation en temps réel: enlever les messages d'erreur lorsque les champs sont remplis
    const inputs = document.querySelectorAll('.form-control, .form-select');
    
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            // Vérifier si l'input est valide maintenant
            let isValid = true;
            
            if (this.id === 'nom' || this.id === 'adresse' || this.id === 'telephone') {
                isValid = this.value.trim() !== '';
            } else if (this.id === 'numero_secu') {
                isValid = /^\d{15}$/.test(this.value.trim());
            } else if (this.id === 'groupe_sanguin') {
                isValid = this.value !== '';
            } else if (this.id === 'temps_acquisition') {
                isValid = parseInt(this.value) > 0;
            }
            
            if (isValid) {
                // Supprimer la classe d'erreur
                this.classList.remove('is-invalid');
                
                // Trouver et supprimer le message d'erreur
                const feedbackEl = this.parentNode.querySelector('.invalid-feedback') || 
                                  this.parentNode.nextElementSibling;
                if (feedbackEl && feedbackEl.classList.contains('invalid-feedback')) {
                    feedbackEl.style.display = 'none';
                }
            }
        });
    });
    
    // Redirection si nécessaire (après soumission réussie)
    <?php if (!empty($redirectToUrl)): ?>
    window.location.href = "<?php echo $redirectToUrl; ?>";
    <?php endif; ?>
});
</script>

<?php 
include_once '../../includes/footer.php'; 
// Vider le tampon de sortie et l'envoyer au navigateur
ob_end_flush();
?> 