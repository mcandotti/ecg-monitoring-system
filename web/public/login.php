<?php
// Page de connexion au système de monitoring ECG
session_start();

require_once '../config/database.php';
require_once '../config/security.php';
require_once '../includes/functions.php';

$pageTitle = "Connexion";

// Process login if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données soumises
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validation basique
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = 'Veuillez remplir tous les champs.';
        // Stay on the same page
    } elseif (!verifyCsrfToken($csrf_token)) {
        $_SESSION['error'] = 'Session expirée. Veuillez réessayer.';
        // Stay on the same page
    } else {
        try {
            // Connexion à la base de données
            $db = getDbConnection();

            $new_hash = password_hash('admin', PASSWORD_BCRYPT);
    
            // Update admin user password
            $query = "UPDATE users SET password = :password WHERE username = 'admin'";
            $stmt = $db->prepare($query);
            $result = $stmt->execute(['password' => $new_hash]);
            
            // Requête pour vérifier les identifiants
            $query = "SELECT id, username, password, role FROM users WHERE username = :username LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $_SESSION['error'] = 'Identifiants incorrects.';
            } else {
                // Use proper password verification
                if (verifyPassword($password, $user['password'])) {
                    // Création de la session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['logged_in'] = true;
                    
                    // Clear any existing remember tokens for this user
                    $deleteQuery = "DELETE FROM remember_tokens WHERE user_id = :user_id";
                    $deleteStmt = $db->prepare($deleteQuery);
                    $deleteStmt->execute(['user_id' => $user['id']]);
                    
                    // Si "Se souvenir de moi" est coché, créer un cookie
                    if ($remember) {
                        $token = generateSecureToken(); // Generate a secure random token
                        $expiry = time() + (30 * 24 * 60 * 60); // 30 jours
                        
                        // Stocker le token en base de données
                        $query = "INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)";
                        $stmt = $db->prepare($query);
                        $stmt->execute([
                            'user_id' => $user['id'],
                            'token' => hashToken($token),
                            'expires_at' => date('Y-m-d H:i:s', $expiry)
                        ]);
                        
                        // Créer le cookie
                        setcookie('remember_token', $token, $expiry, '/', '', false, true);
                    } else {
                        // If remember me is not checked, ensure any cookie is removed
                        if (isset($_COOKIE['remember_token'])) {
                            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
                        }
                    }
                    
                    $_SESSION['success'] = 'Connexion réussie.';
                    
                    // Redirect to the saved URL if available, otherwise to the index page
                    if (isset($_SESSION['redirect_after_login']) && !empty($_SESSION['redirect_after_login'])) {
                        $redirect_url = $_SESSION['redirect_after_login'];
                        unset($_SESSION['redirect_after_login']);
                        redirect($redirect_url);
                    } else {
                        redirect('/pages/index.php');
                    }
                    exit;
                } else {
                    $_SESSION['error'] = 'Identifiants incorrects.';
                }
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Erreur de connexion à la base de données. Veuillez contacter l\'administrateur.';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Une erreur s\'est produite. Veuillez réessayer.';
        }
    }
}

// Display the login form
include_once '../includes/header.php';
?>

<div class="row mt-5 justify-center">
    <div class="col-md-6">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-sign-in-alt me-2"></i>Connexion</h3>
            </div>
            <div class="card-body">
                <form method="post" action="login.php">
                    <?php
                    // Génération d'un token CSRF
                    $csrf_token = generateCsrfToken();
                    ?>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Nom d'utilisateur</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Se souvenir de moi</label>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Se connecter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?> 