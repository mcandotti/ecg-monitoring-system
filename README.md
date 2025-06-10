# Système de Surveillance ECG

Un système complet pour la surveillance d'ECG (électrocardiogramme), conçu pour capturer, stocker et analyser des données cardiaques à l'aide d'un capteur AD8232 connecté à un Raspberry Pi.

## Aperçu du Projet

Ce système fournit une solution complète pour la surveillance ECG avec les fonctionnalités suivantes :
- Gestion des patients avec stockage sécurisé des données
- Acquisition et configuration des données ECG
- Visualisation et analyse des données
- Rapports de diagnostic médical
- Contrôle d'accès basé sur les rôles (administrateur, médecin, patient)

## Architecture du Système

Le système se compose de :
1. **Application Web** : Interface web basée sur PHP pour les utilisateurs
2. **Base de données** : Base de données MySQL pour stocker les informations des patients, les données ECG et les diagnostics
3. **Conteneurisation** : Configuration Docker pour un déploiement facile

## Structure des Répertoires

```
ecg-monitoring-system/
├── .cursor/               # Configuration de l'éditeur Cursor
├── database/              # Fichiers d'initialisation de la base de données
│   └── init.sql           # Schéma de base de données et données initiales
├── web/                   # Code de l'application web
│   ├── config/            # Fichiers de configuration
│   │   ├── database.php   # Gestion de la connexion à la base de données
│   │   ├── env.php        # Gestion des variables d'environnement
│   │   └── security.php   # Fonctions de sécurité et de chiffrement
│   ├── includes/          # Composants PHP partagés
│   │   ├── auth.php       # Système d'authentification
│   │   ├── functions.php  # Fonctions utilitaires
│   │   ├── header.php     # Modèle d'en-tête commun
│   │   └── footer.php     # Modèle de pied de page commun
│   └── public/            # Fichiers accessibles publiquement
│       ├── css/           # Feuilles de style
│       ├── js/            # Fichiers JavaScript
│       ├── pages/         # Pages de l'application
│       ├── index.php      # Point d'entrée principal
│       ├── login.php      # Page de connexion
│       └── logout.php     # Gestionnaire de déconnexion
├── apache-config.conf     # Configuration du serveur Apache
├── docker-compose.yml     # Définition de la composition Docker
├── Dockerfile             # Définition de l'image Docker
└── Makefile               # Commandes de construction et de gestion
```

## Démarrage

### Prérequis

- Docker et Docker Compose
- Make (pour les commandes Makefile)

### Installation et Configuration

1. Clonez le dépôt :
   ```bash
   git clone <url-du-dépôt>
   cd ecg-monitoring-system
   ```

2. Construisez et démarrez les conteneurs :
   ```bash
   make build
   make up
   ```

3. Accédez à l'application :
   - Interface web : http://localhost:80
   - PHPMyAdmin : http://localhost:8080

### Identifiants par Défaut

- Utilisateur administrateur :
  - Nom d'utilisateur : admin
  - Mot de passe : admin

## Utilisation

### Gestion du Système

Utilisez les commandes Makefile pour gérer le système :

- `make up` - Démarrer les conteneurs
- `make down` - Arrêter les conteneurs
- `make restart` - Redémarrer les conteneurs
- `make logs` - Afficher les journaux des conteneurs
- `make shell-web` - Ouvrir un shell dans le conteneur web
- `make shell-db` - Ouvrir un shell dans le conteneur MySQL
- `make clean` - Arrêter et supprimer tous les conteneurs, volumes et images
- `make help` - Afficher les informations d'aide

### Gestion des Données Patients

Le système protège les données sensibles des patients grâce au hachage et au chiffrement. La protection des données comprend :
- Noms des patients (hachés et encodés)
- Numéros de sécurité sociale (hachés et encodés)
- Informations d'adresse (encodées)

### Gestion des Données ECG

Le système gère les données ECG via :
1. Configuration des paramètres d'acquisition
2. Capture et stockage des données
3. Visualisation et analyse
4. Rapports de diagnostic

## Fonctionnalités de Sécurité

- Chiffrement et hachage des données patients
- Hachage des mots de passe pour l'authentification des utilisateurs
- Contrôle d'accès basé sur les rôles
- Gestion sécurisée des sessions

## Développement

Lors du développement de nouvelles fonctionnalités :

1. Utilisez `make shell-web` pour accéder au conteneur web
2. Suivez la structure des répertoires pour les nouveaux fichiers
3. Utilisez les fonctions de sécurité pour gérer les données sensibles
4. Testez soigneusement avant le déploiement

## Structure de la Base de Données

La base de données comprend des tables pour :
- `patients` - Dossiers des patients avec données personnelles protégées
- `configurations` - Configurations d'acquisition ECG
- `ecg_data` - Valeurs réelles des signaux ECG
- `diagnostics` - Diagnostics médicaux et mesures
- `users` - Utilisateurs du système et authentification
- `remember_tokens` - Jetons de persistance de session

## Licence

[Spécifiez votre licence]

## Contact

[Vos informations de contact] 