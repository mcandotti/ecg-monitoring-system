<?php
// Inclusion des fichiers de configuration
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Récupération du titre de la page
$pageTitle = isset($pageTitle) ? $pageTitle : 'Système de Monitoring ECG';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Raspberry Pi ECG</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="/css/main.css">
    
    <?php if (isset($extraCss)): ?>
        <!-- CSS supplémentaire spécifique à la page -->
        <link rel="stylesheet" href="<?php echo $extraCss; ?>">
    <?php endif; ?>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container navbar-container">
            <a class="navbar-brand" href="/pages/index.php">
                <i class="fas fa-heartbeat"></i>ECG Monitoring
            </a>
            <button class="navbar-toggler" type="button">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/pages/index.php">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/pages/configuration.php">Configuration</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/pages/diagnostic.php">Diagnostic</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav ms-auto">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button">
                                <i class="fa fa-user icon-spacing"></i><?php echo htmlspecialchars($_SESSION['username']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/logout.php">Déconnexion</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/login.php">Connexion</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="container">
            <?php
            // Affichage des messages d'alerte stockés en session
            if (isset($_SESSION['success'])) {
                echo showSuccess($_SESSION['success']);
                unset($_SESSION['success']);
            }
            
            if (isset($_SESSION['error'])) {
                echo showError($_SESSION['error']);
                unset($_SESSION['error']);
            }
            
            if (isset($_SESSION['info'])) {
                echo showInfo($_SESSION['info']);
                unset($_SESSION['info']);
            }
            ?>
