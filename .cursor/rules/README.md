# Règles Cursor

Ce dossier contient les règles Cursor qui sont utilisées pour faciliter la compréhension et le développement du système de surveillance ECG.

## Qu'est-ce que les Règles Cursor?

Les règles Cursor sont des fichiers Markdown qui fournissent des informations contextuelles et des directives pour le développement. Elles permettent à l'IA de Cursor de mieux comprendre la structure du projet et de fournir une assistance plus pertinente.

## Règles Disponibles

### 1. project-overview.mdc

Cette règle fournit une vue d'ensemble du projet de système de surveillance ECG:
- Description générale du système
- Structure principale du projet
- Les composants clés et leur rôle

### 2. development-workflow.mdc

Cette règle décrit le flux de travail de développement:
- Comment configurer et démarrer le projet
- Les commandes Makefile disponibles
- Comment gérer la base de données
- Les bonnes pratiques pour ajouter de nouvelles fonctionnalités

### 3. security-guidelines.mdc

Cette règle détaille les directives de sécurité du projet:
- Protection des données des patients
- Système d'authentification et autorisations
- Gestion des variables d'environnement
- Meilleures pratiques de sécurité à suivre

## Comment Utiliser les Règles

Les règles Cursor sont automatiquement chargées par l'éditeur Cursor lorsque vous travaillez sur le projet. Elles sont utilisées pour:

1. Fournir des suggestions contextuelles pertinentes
2. Guider le développement en accord avec les standards du projet
3. Aider à comprendre la structure et l'organisation du code

## Ajouter ou Modifier des Règles

Pour ajouter ou modifier des règles:

1. Créez ou modifiez un fichier avec l'extension `.mdc` dans ce dossier
2. Utilisez le format Markdown avec des références aux fichiers du projet
3. Pour référencer un fichier, utilisez le format: `[nom-fichier.ext](mdc:chemin/vers/nom-fichier.ext)`
4. Organisez le contenu avec des titres, des listes et des sections claires

Exemple: 