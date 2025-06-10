# Application Web du Système de Surveillance ECG

Ce dossier contient l'ensemble de l'application web pour le système de surveillance ECG. L'application est construite en PHP avec une architecture MVC simplifiée.

## Structure Globale

```
web/
├── config/              # Fichiers de configuration
├── includes/            # Composants PHP partagés
├── public/              # Fichiers accessibles publiquement
│   ├── css/             # Feuilles de style
│   ├── js/              # Scripts JavaScript
│   ├── pages/           # Pages de l'application
│   ├── index.php        # Point d'entrée principal
│   ├── login.php        # Page de connexion
│   └── logout.php       # Script de déconnexion
├── .htaccess            # Configuration Apache pour le dossier web
└── index.php            # Redirection vers public/index.php
```

## Architecture de l'Application

L'architecture de l'application suit un modèle MVC (Modèle-Vue-Contrôleur) simplifié:

1. **Modèle**: La logique métier et l'accès aux données sont principalement gérés dans:
   - `config/database.php` - Connexion et requêtes à la base de données
   - Les fonctions spécifiques à certaines entités comme les patients, ECG, etc.

2. **Vue**: La présentation est gérée par:
   - Les fichiers dans `public/pages/`
   - Les modèles communs dans `includes/header.php` et `includes/footer.php`
   - Les feuilles de style CSS dans `public/css/`

3. **Contrôleur**: La logique de contrôle se trouve dans:
   - Les scripts PHP qui traitent les formulaires
   - Les fonctions d'authentification et d'autorisation dans `includes/auth.php`
   - Les fonctions utilitaires dans `includes/functions.php`

## Configuration Apache (.htaccess)

Le fichier `.htaccess` est un élément crucial pour la configuration d'Apache et la sécurité de l'application. 

### Rôle et fonctionnalités

Le fichier `.htaccess` dans ce projet:

1. **Contrôle l'accès aux dossiers**:
   ```apache
   # Empêcher l'accès direct aux dossiers sensibles
   <FilesMatch "(?i:^\..*|.*\.config.php|.*\.ini)">
       Order Allow,Deny
       Deny from all
   </FilesMatch>
   
   # Interdire la navigation des répertoires
   Options -Indexes
   ```

2. **Configuration de la réécriture d'URL**:
   ```apache
   <IfModule mod_rewrite.c>
       RewriteEngine On
       
       # Rediriger toutes les requêtes vers le dossier public
       RewriteCond %{REQUEST_URI} !^/public/
       RewriteRule ^(.*)$ /public/$1 [L]
       
       # Gestion des URL propres pour les pages
       RewriteCond %{REQUEST_FILENAME} !-f
       RewriteCond %{REQUEST_FILENAME} !-d
       RewriteRule ^public/(.*)$ /public/index.php?route=$1 [QSA,L]
   </IfModule>
   ```

3. **Paramètres de sécurité**:
   ```apache
   # Protection contre les attaques XSS
   <IfModule mod_headers.c>
       Header set X-XSS-Protection "1; mode=block"
       Header set X-Frame-Options "SAMEORIGIN"
       Header set X-Content-Type-Options "nosniff"
       Header set Content-Security-Policy "default-src 'self'; script-src 'self'"
   </IfModule>
   
   # Cacher la signature du serveur
   ServerSignature Off
   ```

4. **Paramètres de performance**:
   ```apache
   # Compression et mise en cache
   <IfModule mod_deflate.c>
       AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
   </IfModule>
   
   <IfModule mod_expires.c>
       ExpiresActive On
       ExpiresByType text/css "access plus 1 week"
       ExpiresByType text/javascript "access plus 1 week"
       ExpiresByType application/javascript "access plus 1 week"
       ExpiresByType image/gif "access plus 1 month"
       ExpiresByType image/jpeg "access plus 1 month"
       ExpiresByType image/png "access plus 1 month"
   </IfModule>
   ```

### Importance pour le projet

Le fichier `.htaccess` est essentiel pour:
- Renforcer la sécurité de l'application
- Protéger les fichiers sensibles contre l'accès direct
- Structurer les URL de manière propre et conviviale
- Améliorer les performances via la compression et la mise en cache
- Implémenter une architecture MVC par la redirection des requêtes

## Flux de Travail Typique

### 1. Initialisation

Chaque page suit généralement ce flux:

```php
<?php
// Charger les dépendances
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

// Vérifier les autorisations
if (!isUserAuthorized('role_requis')) {
    header('Location: /public/login.php');
    exit;
}

// Logique de la page (traitement de formulaire, etc.)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Traiter les données du formulaire
}

// Définir le titre et charger l'en-tête
$pageTitle = 'Titre de la Page';
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Contenu HTML de la page -->
<div class="container">
    <!-- ... -->
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
```

### 2. Gestion de Base de Données

L'accès à la base de données se fait via des fonctions qui utilisent PDO:

```php
// Exemple de récupération de données
function getPatientById($patientId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$patientId]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($patient) {
        // Décoder les données sensibles
        $patient['name'] = decodeData($patient['name_encoded']);
        // ...
    }
    
    return $patient;
}

// Exemple d'insertion de données
function addNewPatient($name, $securite, $phone, $address, $bloodType) {
    $conn = getDbConnection();
    
    // Préparation des données sensibles
    $nameHash = hashSensitiveData($name);
    $nameEncoded = encodeData($name);
    $secuHash = hashSensitiveData($securite);
    $secuEncoded = encodeData($securite);
    $addressEncoded = encodeData($address);
    
    $sql = "INSERT INTO patients (name_hash, name_encoded, secu_hash, secu_encoded, 
                                 phone, address_encoded, blood_type)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    return $stmt->execute([
        $nameHash, $nameEncoded, $secuHash, $secuEncoded,
        $phone, $addressEncoded, $bloodType
    ]);
}
```

### 3. Sécurité et Protection des Données

La sécurité est gérée à plusieurs niveaux:

```php
// Validation des entrées
$input = validateInput($_POST['donnee']);

// Protection CSRF
$token = generateCsrfToken();
// Et dans les formulaires:
<input type="hidden" name="csrf_token" value="<?= $token ?>">

// Vérification des autorisations
if (!isUserAuthorized('medecin')) {
    // Rediriger ou afficher une erreur
}

// Protection des données sensibles
$dataProtegee = encodeData($donneeOriginale);
```

## Points Importants

1. **Séparation des Fichiers**: 
   - Les fichiers de configuration sont séparés des fichiers d'application
   - Le dossier `public` est le seul accessible directement par les navigateurs
   - Les fichiers sensibles sont placés en dehors de la racine web

2. **Sécurité**:
   - Toutes les requêtes SQL utilisent des requêtes préparées pour éviter les injections
   - Les données sensibles sont hachées et/ou encodées
   - L'authentification est requise pour la plupart des fonctionnalités
   - La protection CSRF est mise en œuvre pour tous les formulaires

3. **Modularité**:
   - Les fonctions communes sont placées dans des fichiers partagés
   - Les éléments visuels communs sont dans des modèles réutilisables
   - Le code est organisé pour faciliter la maintenance et l'extension

## Développement et Extension

Pour ajouter de nouvelles fonctionnalités à l'application:

1. Créer les tables nécessaires dans la base de données
2. Ajouter les fonctions d'accès aux données dans les fichiers appropriés
3. Créer des pages dans le dossier `public/pages/`
4. S'assurer que les contrôles d'autorisation appropriés sont en place
5. Ajouter les liens dans la navigation et sur la page d'accueil

Pour plus de détails sur chaque composant, consultez les README.md dans les sous-dossiers respectifs. 