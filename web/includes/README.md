# Dossier Includes

Ce dossier contient les composants PHP partagés qui sont utilisés à travers toute l'application web du système de surveillance ECG.

## Structure du Dossier

```
web/includes/
├── auth.php         # Système d'authentification
├── functions.php    # Fonctions utilitaires communes
├── header.php       # Modèle d'en-tête HTML commun
└── footer.php       # Modèle de pied de page HTML commun
```

## Détails des Fichiers

### auth.php

Ce fichier gère tout le système d'authentification et d'autorisation de l'application, notamment:
- Connexion et déconnexion des utilisateurs
- Vérification des droits d'accès
- Gestion des sessions
- Fonctionnalité "Se souvenir de moi"

Exemple de fonction de connexion:
```php
function loginUser($username, $password, $remember = false) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !password_verify($password, $user['password'])) {
        return false;
    }
    
    // Initialiser la session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $user['role'];
    $_SESSION['last_activity'] = time();
    
    // Mettre à jour la dernière connexion
    $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $updateStmt->execute([$user['id']]);
    
    // Créer un token "se souvenir de moi" si demandé
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $tokenStmt = $conn->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $tokenStmt->execute([$user['id'], $token, $expires]);
        
        // Définir le cookie pour 30 jours
        setcookie('remember_token', $token, time() + 60 * 60 * 24 * 30, '/', '', true, true);
    }
    
    return true;
}
```

Exemple de vérification des autorisations:
```php
function isUserAuthorized($requiredRole) {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Si l'admin est requis, seul l'admin peut accéder
    if ($requiredRole === 'admin') {
        return $_SESSION['role'] === 'admin';
    }
    
    // Si le médecin est requis, les admins et médecins peuvent accéder
    if ($requiredRole === 'medecin') {
        return in_array($_SESSION['role'], ['admin', 'medecin']);
    }
    
    // Si le patient est requis, tout utilisateur connecté peut accéder
    return true;
}
```

### functions.php

Ce fichier contient des fonctions utilitaires génériques utilisées dans toute l'application:
- Formatage de dates
- Validation de formulaires
- Fonctions d'aide à l'affichage
- Gestion des erreurs

Exemple de fonction de validation:
```php
function validateInput($data, $type = 'string') {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    
    switch ($type) {
        case 'email':
            if (!filter_var($data, FILTER_VALIDATE_EMAIL)) {
                return false;
            }
            break;
        case 'int':
            if (!is_numeric($data) || intval($data) != $data) {
                return false;
            }
            $data = intval($data);
            break;
        case 'float':
            if (!is_numeric($data)) {
                return false;
            }
            $data = floatval($data);
            break;
    }
    
    return $data;
}
```

Exemple de fonction d'affichage:
```php
function displayAlert($message, $type = 'info') {
    $alertClass = 'alert-info';
    
    switch ($type) {
        case 'success':
            $alertClass = 'alert-success';
            break;
        case 'warning':
            $alertClass = 'alert-warning';
            break;
        case 'error':
            $alertClass = 'alert-danger';
            break;
    }
    
    echo "<div class='alert {$alertClass}' role='alert'>{$message}</div>";
}
```

### header.php

Ce fichier contient la partie supérieure du modèle HTML utilisé dans toutes les pages de l'application:
- Déclaration DOCTYPE
- Balises meta
- Liens vers les CSS
- Barre de navigation
- Messages de notification

Exemple de code (partiel):
```php
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Système de Surveillance ECG' ?></title>
    <link rel="stylesheet" href="/public/css/bootstrap.min.css">
    <link rel="stylesheet" href="/public/css/style.css">
    <?php if (isset($extraCss)): ?>
        <link rel="stylesheet" href="<?= $extraCss ?>">
    <?php endif; ?>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/public/index.php">ECG Monitor</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/public/index.php">Accueil</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <?php if (isUserAuthorized('medecin')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/public/pages/patients.php">Patients</a>
                            </li>
                        <?php endif; ?>
                        <!-- Autres liens de menu selon le rôle -->
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <span class="nav-link">Bonjour, <?= htmlspecialchars($_SESSION['username']) ?></span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/public/logout.php">Déconnexion</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/public/login.php">Connexion</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <!-- Affichage des messages d'alerte -->
        <?php if (isset($_SESSION['alert_message'])): ?>
            <?php displayAlert($_SESSION['alert_message'], $_SESSION['alert_type'] ?? 'info'); ?>
            <?php unset($_SESSION['alert_message'], $_SESSION['alert_type']); ?>
        <?php endif; ?>
```

### footer.php

Ce fichier contient la partie inférieure du modèle HTML:
- Pied de page
- Scripts JavaScript
- Fermeture des balises HTML

Exemple de code:
```php
    </div><!-- Fermeture du container -->
    
    <footer class="mt-5 py-3 bg-light">
        <div class="container text-center">
            <p>&copy; <?= date('Y') ?> Système de Surveillance ECG</p>
        </div>
    </footer>
    
    <script src="/public/js/bootstrap.bundle.min.js"></script>
    <script src="/public/js/main.js"></script>
    <?php if (isset($extraJs)): ?>
        <script src="<?= $extraJs ?>"></script>
    <?php endif; ?>
</body>
</html>
```

## Utilisation

Ces fichiers sont inclus dans chaque page de l'application pour:
1. Maintenir une structure HTML cohérente (header.php et footer.php)
2. Gérer l'authentification des utilisateurs (auth.php)
3. Fournir des fonctions utilitaires communes (functions.php)

Exemple d'inclusion dans une page:
```php
<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Vérifier que l'utilisateur est connecté et a les droits
if (!isUserAuthorized('medecin')) {
    // Rediriger vers la page de connexion
    header('Location: /public/login.php');
    exit;
}

$pageTitle = 'Liste des Patients';
require_once __DIR__ . '/../../includes/header.php';

// Contenu de la page...

require_once __DIR__ . '/../../includes/footer.php';
?> 