# 🎵 Deefy

**Développé par Nathan YVON & Samy CHERCHARI**

---

## Description

Deefy est une application web de gestion et de lecture de playlists musicales avec système d'authentification et contrôle d'accès par rôles. Les utilisateurs peuvent gérer leurs playlists, écouter de la musique directement depuis le navigateur et organiser leur bibliothèque audio de manière intuitive.

---

## Fonctionnalités

### Fonctionnalités de base

- **Mes playlists** : Affichage de la liste des playlists de l'utilisateur authentifié
- **Consultation d'une playlist** : Navigation cliquable vers chaque playlist qui devient alors la playlist courante (stockée en session)
- **Ajout de pistes** : Réservé aux administrateurs et artistes via la bibliothèque (avec formulaire de saisie)
- **Création de playlist** : Formulaire permettant de créer une nouvelle playlist vide qui devient immédiatement la playlist courante
- **Affichage de la playlist courante** : Consultation de la playlist stockée en session
- **Inscription** : Création de compte utilisateur avec le rôle Standard
- **Authentification** : Connexion avec identifiants pour accéder à son espace personnel

### Fonctionnalités étendues

- **Gestion du profil** : Modification du nom d'utilisateur et de l'avatar avec upload sécurisé (contrôle du format et de la taille)
- **Interface administrateur** : Affichage adapté selon le rôle avec accès étendu aux playlists pour les admins
- **Recherche avancée** : Filtrage par titre, artiste ou genre pour ajouter des pistes depuis la base de données vers une playlist
- **Lecteur audio intégré** : Lecture, pause et navigation entre morceaux avec affichage dynamique des informations et des pochettes
- **Barre de recherche globale** : Recherche de pistes directement depuis la page d'accueil

---

## Technologies utilisées

- PHP
- HTML5, CSS
- MySQL

---

## Rôles utilisateurs

| Rôle | Permissions |
|------|-------------|
|  **Standard** | Création et gestion de playlists personnelles, lecture de musique |
|  **Artiste** | Permissions Standard + ajout de pistes dans la bibliothèque |
|  **Administrateur** | Accès complet à toutes les playlists et gestion des utilisateurs |

