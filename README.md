# 🎵 Deefy (Projet BUT 2)

**Développé par Nathan YVON & Samy CHERCHARI**

---

## Description

Deefy est une application web de gestion et de lecture de playlists musicales avec système d'authentification et contrôle d'accès par rôles.  
Les utilisateurs peuvent gérer leurs playlists, écouter de la musique directement depuis le navigateur et organiser leur bibliothèque audio de manière intuitive.

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
- JavaScript 
- MySQL/MariaDB  

---

## Rôles utilisateurs

| Rôle | Permissions |
|------|-------------|
| **Standard** | Création et gestion de playlists personnelles, lecture de musique |
| **Artiste** | Permissions Standard + ajout de pistes dans la bibliothèque |
| **Administrateur** | Accès complet à toutes les playlists et gestion des utilisateurs |

---

## Installation et configuration

1. **Importer la base de données**  
   - Importer le fichier SQL `deefy.sql` dans phpMyAdmin ou via la ligne de commande MySQL :  
     ```
     mysql -u root -p < deefy.sql
     ```

2. **Configurer le fichier `db.ini`**  
   - Modifier `ressources/acces/db.ini` pour correspondre à vos paramètres MySQL / phpMyAdmin :  
     ```
     host = localhost
     dbname = deefy
     user = root
     password = votre_mot_de_passe
     ```

3. **Modifier les droits du dossier `ressources`**  
   - Assurez-vous que le serveur web a les droits en écriture sur le dossier `ressources` pour permettre les uploads :  
     ```
     chmod -R 775 ressources
     ```

4. **Lancer l’application**  
   - Placer le projet dans votre répertoire web (`www` ou `htdocs`)  
   - Lancer votre serveur web (Apache2, XAMPP, etc.)  
   - Accéder via votre navigateur :

   **Méthode Apache2 (WSL/Unix)**  
   Ajouter un fichier `deefy.conf` dans `/etc/apache2/sites-available/` :  
   ```
   <VirtualHost *:80>
       ServerName deefy.localhost
       DocumentRoot /var/www/deefy/deefy
       <Directory "/var/www/deefy/deefy">
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```
   Puis accéder activer le grâce a la commande a2ensite, puis enfin acceder a votre navigateur via ce lien :  
   ```
   http://deefy.localhost/index.php
   ```

   **Méthode XAMPP**  
   ```
   http://localhost/deefy/
   ```

---

## Auteurs

- Nathan YVON  
- Samy CHERCHARI

