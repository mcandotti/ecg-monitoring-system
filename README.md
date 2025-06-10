# Système de Surveillance ECG

Un système complet pour la surveillance d'ECG (électrocardiogramme), conçu pour capturer, stocker et analyser des données cardiaques à l'aide d'un capteur AD8232 connecté à un Raspberry Pi.

## Aperçu du Projet

Ce système fournit une solution complète pour la surveillance ECG avec les fonctionnalités suivantes :
- Gestion des patients avec stockage sécurisé des données
- **Capture ECG en temps réel** avec contrôle start/stop via interface web
- **Galerie d'images ECG** avec visualisation chronologique
- **Service Python dédié** pour la gestion du capteur AD8232
- Visualisation et analyse des données
- Rapports de diagnostic médical
- Contrôle d'accès basé sur les rôles (administrateur, médecin, patient)

## Architecture du Système

Le système se compose de :
1. **Application Web** : Interface web basée sur PHP pour les utilisateurs
2. **Service Python ECG** : Service Flask pour la gestion du capteur AD8232 et la capture temps réel
3. **Base de données** : Base de données MySQL pour stocker les informations des patients, les données ECG et les diagnostics
4. **Conteneurisation** : Configuration Docker multi-services pour un déploiement facile

## Structure des Répertoires

```
ecg-monitoring-system/
├── .cursor/               # Configuration de l'éditeur Cursor
├── database/              # Fichiers d'initialisation de la base de données
│   └── init.sql           # Schéma de base de données et données initiales
├── docker/                # Configuration Docker
│   └── python/            # Service Python ECG
│       ├── Dockerfile     # Image Docker pour service Python
│       └── requirements.txt # Dépendances Python
├── scripts/               # Scripts Python pour ECG
│   ├── ecg_service.py     # Service Flask principal
│   ├── process_manager.py # Gestionnaire de processus
│   ├── ecg_capture.py     # Logique de capture ECG
│   └── database_manager.py # Gestionnaire base de données Python
├── web/                   # Code de l'application web
│   ├── api/               # APIs REST
│   │   └── ecg_control.php # API de contrôle ECG
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
│       │   └── pages/     # Styles spécifiques aux pages
│       ├── js/            # Fichiers JavaScript
│       │   └── ecg-realtime.js # Contrôleur temps réel
│       ├── pages/         # Pages de l'application
│       │   ├── diagnostic_details.php # Page détails diagnostic avec capture
│       │   ├── diagnostic.php # Page diagnostic
│       │   └── index.php  # Page d'accueil
│       ├── index.php      # Point d'entrée principal
│       ├── login.php      # Page de connexion
│       └── logout.php     # Gestionnaire de déconnexion
├── apache-config.conf     # Configuration du serveur Apache
├── docker-compose.yml     # Définition de la composition Docker
├── Dockerfile             # Définition de l'image Docker web
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
- `make shell-python` - Ouvrir un shell dans le conteneur du service Python ECG
- `make logs-python` - Afficher les logs du service Python ECG
- `make clean` - Arrêter et supprimer tous les conteneurs, volumes et images
- `make help` - Afficher les informations d'aide

### Gestion des Données Patients

Le système protège les données sensibles des patients grâce au hachage et au chiffrement. La protection des données comprend :
- Noms des patients (hachés et encodés)
- Numéros de sécurité sociale (hachés et encodés)
- Informations d'adresse (encodées)

### Gestion des Données ECG

Le système gère les données ECG via :
1. **Capture en temps réel** : Interface web pour démarrer/arrêter les sessions de capture
2. **Service Python autonome** : Gestion du capteur AD8232 via GPIO/SPI
3. **Stockage automatique** : Sauvegarde d'images ECG toutes les 5 secondes
4. **Galerie interactive** : Visualisation chronologique avec zoom et téléchargement
5. **Mise à jour temps réel** : Actualisation automatique pendant la capture

### Nouvelles Fonctionnalités ECG

- **Page de détails de diagnostic** : Interface dédiée pour chaque diagnostic
- **Contrôles de capture** : Boutons start/stop avec indicateurs de statut
- **Galerie d'images** : Vue grille/liste avec miniatures
- **API REST** : Communication entre PHP et Python
- **Conteneur Python séparé** : Service dédié avec accès GPIO

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
- `diagnostics` - Diagnostics médicaux liés aux patients
- `ecg_data` - Images ECG stockées en BLOB avec métadonnées
- `ecg_capture_sessions` - Sessions de capture avec statuts et compteurs
- `users` - Utilisateurs du système et authentification
- `remember_tokens` - Jetons de persistance de session

### Modifications de Structure

- **Suppression de `configurations`** : Simplification vers patients → diagnostics → ecg_data
- **Ajout de `ecg_capture_sessions`** : Suivi des sessions de capture temps réel
- **Modification de `ecg_data`** : Stockage d'images PNG au lieu de valeurs brutes

## Licence

[Spécifiez votre licence]

## Contact

[Vos informations de contact] 