# Dossier Public

Ce dossier contient tous les fichiers accessibles publiquement de l'application web du système de surveillance ECG. C'est le point d'entrée principal pour les utilisateurs.

## Structure du Dossier

```
web/public/
├── css/                # Feuilles de style CSS
│   └── pages/          # CSS spécifiques à certaines pages
├── js/                 # Scripts JavaScript
├── pages/              # Pages de l'application
├── index.php           # Page d'accueil
├── login.php           # Page de connexion
└── logout.php          # Script de déconnexion
```

## Détails des Fichiers et Sous-dossiers

### index.php

Ce fichier est la page d'accueil de l'application. C'est la première page que les utilisateurs voient en accédant au site.

Exemple de code:
```php
<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Accueil - Système de Surveillance ECG';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="jumbotron text-center">
    <h1 class="display-4">Bienvenue sur le Système de Surveillance ECG</h1>
    <p class="lead">Un système complet pour l'acquisition, l'analyse et le diagnostic des électrocardiogrammes.</p>
    
    <?php if (!isLoggedIn()): ?>
        <hr class="my-4">
        <p>Connectez-vous pour accéder à toutes les fonctionnalités.</p>
        <a class="btn btn-primary btn-lg" href="login.php" role="button">Connexion</a>
    <?php else: ?>
        <hr class="my-4">
        <p>Accédez à vos fonctionnalités selon votre rôle:</p>
        <div class="row justify-content-center">
            <?php if (isUserAuthorized('admin')): ?>
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Administration</h5>
                            <p class="card-text">Gérez les utilisateurs et les paramètres système</p>
                            <a href="pages/admin/dashboard.php" class="btn btn-primary">Accéder</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (isUserAuthorized('medecin')): ?>
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Gestion Patients</h5>
                            <p class="card-text">Consultez et gérez les dossiers patients</p>
                            <a href="pages/patients.php" class="btn btn-primary">Accéder</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Données ECG</h5>
                        <p class="card-text">Visualisez les données ECG</p>
                        <a href="pages/ecg-viewer.php" class="btn btn-primary">Accéder</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
```

### login.php

Ce fichier gère l'authentification des utilisateurs avec:
- Formulaire de connexion
- Validation des identifiants
- Option "Se souvenir de moi"
- Messages d'erreur

Exemple de code:
```php
<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/security.php';

// Rediriger si déjà connecté
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = validateInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($username) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        if (loginUser($username, $password, $remember)) {
            // Redirection après connexion réussie
            $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            header("Location: $redirect");
            exit;
        } else {
            $error = 'Identifiants invalides. Veuillez réessayer.';
        }
    }
}

$pageTitle = 'Connexion';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center">Connexion</h3>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <form method="post" action="login.php">
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
                    
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">Se connecter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
```

### logout.php

Ce fichier gère la déconnexion des utilisateurs:
- Destruction de la session
- Suppression des cookies
- Redirection vers la page de connexion

Exemple de code:
```php
<?php
require_once __DIR__ . '/../includes/auth.php';

// Détruire la session
logoutUser();

// Rediriger vers la page de connexion
header('Location: login.php');
exit;
?>
```

### Sous-dossier css/

Ce dossier contient toutes les feuilles de style CSS utilisées par l'application:
- Bibliothèque Bootstrap
- Styles personnalisés
- Styles spécifiques à certaines pages

Exemple de structure:
```
css/
├── bootstrap.min.css    # Framework CSS Bootstrap
├── style.css            # Styles personnalisés généraux
└── pages/               # Styles spécifiques aux pages
    ├── dashboard.css    # Styles pour le tableau de bord
    ├── ecg-viewer.css   # Styles pour le visualiseur ECG
    └── login.css        # Styles pour la page de connexion
```

### Sous-dossier js/

Ce dossier contient tous les scripts JavaScript utilisés par l'application:
- Bibliothèques JS (jQuery, Bootstrap, etc.)
- Scripts personnalisés
- Fonctionnalités interactives

Exemple de structure:
```
js/
├── bootstrap.bundle.min.js   # Framework JS Bootstrap
├── main.js                   # Fonctions JS générales
├── chart.min.js              # Bibliothèque de graphiques
└── ecg-viewer.js             # Script spécifique au visualiseur ECG
```

Exemple de code dans ecg-viewer.js:
```javascript
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('ecgChart').getContext('2d');
    let ecgData = [];
    let labels = [];
    
    // Fonction pour charger les données ECG
    async function loadEcgData(configId) {
        try {
            const response = await fetch(`../api/get-ecg-data.php?config_id=${configId}`);
            const data = await response.json();
            
            if (data.success) {
                ecgData = data.values;
                labels = data.timestamps;
                renderChart();
            } else {
                showError(data.message);
            }
        } catch (error) {
            showError('Erreur lors du chargement des données ECG');
        }
    }
    
    // Fonction pour afficher le graphique ECG
    function renderChart() {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Signal ECG',
                    data: ecgData,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                    fill: false,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Temps (s)'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Amplitude (mV)'
                        }
                    }
                }
            }
        });
    }
    
    // Charger les données initiales
    const configId = document.getElementById('config-id').value;
    if (configId) {
        loadEcgData(configId);
    }
    
    // Gestionnaire pour le bouton de rafraîchissement
    document.getElementById('refresh-btn').addEventListener('click', function() {
        loadEcgData(configId);
    });
});
```

### Sous-dossier pages/

Ce dossier contient toutes les pages de l'application organisées selon leur fonction:
- Pages d'administration
- Pages de gestion des patients
- Pages de visualisation des données ECG
- Pages de diagnostics

Exemple de structure:
```
pages/
├── admin/                # Pages d'administration
│   ├── dashboard.php     # Tableau de bord administrateur
│   ├── users.php         # Gestion des utilisateurs
│   └── settings.php      # Paramètres système
├── patients.php          # Liste des patients
├── patient-details.php   # Détails d'un patient
├── add-patient.php       # Ajout d'un patient
├── ecg-viewer.php        # Visualiseur de données ECG
└── diagnostics.php       # Gestion des diagnostics
```

## Utilisation

Le dossier public contient toutes les pages accessibles directement par les utilisateurs. Pour ajouter une nouvelle fonctionnalité:

1. Créer les fichiers PHP nécessaires dans le dossier `pages/`
2. Ajouter les styles CSS spécifiques dans `css/pages/`
3. Ajouter les scripts JavaScript nécessaires dans `js/`
4. S'assurer que chaque page inclut les fichiers header.php et footer.php
5. Implémenter la vérification des autorisations pour contrôler l'accès 