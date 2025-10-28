<?php
// includes/profile.php

session_start();

// Charger l'autoload de Composer
require_once __DIR__ . '/../classes/init.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Deefy\Utilisateur\UtilManage;

// Redirection si non connecté
if (!isset($_SESSION['user'])) {
    header('Location: Log_Sig.php');
    exit();
}

$user = $_SESSION['user'];
$successMessage = '';
$errorMessage = '';
$userManager = new UtilManage($pdo);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    try {
        // Changement du nom d'utilisateur
        if (isset($_POST['update_username'])) {
            $newUsername = trim($_POST['username']);
            
            if ($userManager->updateUsername($user['email'], $newUsername)) {
                $_SESSION['user']['username'] = $newUsername;
                $user['username'] = $newUsername;
                $successMessage = "Nom d'utilisateur mis à jour avec succès !";
            }
        }
        
        // Changement de la photo de profil
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['avatar']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowed)) {
                throw new Exception("Format d'image non autorisé. Utilisez JPG, PNG, GIF ou WEBP.");
            }
            
            if ($_FILES['avatar']['size'] > 5000000) { // 5MB max
                throw new Exception("L'image est trop volumineuse (max 5MB).");
            }
            
            $uploadDir = __DIR__ . '/../ressources/images/avatars/';
            
            // Créer le dossier s'il n'existe pas
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Générer un nom unique
            $newFilename = uniqid('avatar_') . '.' . $ext;
            $destination = $uploadDir . $newFilename;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destination)) {
                // Supprimer l'ancienne photo si ce n'est pas l'avatar par défaut
                if (isset($_SESSION['user']['avatar']) && 
                    $_SESSION['user']['avatar'] !== 'ressources/images/defaut-avatar.png' &&
                    file_exists(__DIR__ . '/../' . $_SESSION['user']['avatar'])) {
                    unlink(__DIR__ . '/../' . $_SESSION['user']['avatar']);
                }
                
                // Sauvegarder le chemin relatif depuis la racine
                $avatarPath = 'ressources/images/avatars/' . $newFilename;
                
                // Mettre à jour en base de données
                if ($userManager->updateAvatar($user['email'], $avatarPath)) {
                    $_SESSION['user']['avatar'] = $avatarPath;
                    $user['avatar'] = $avatarPath;
                    $successMessage = "Photo de profil mise à jour avec succès !";
                }
            } else {
                throw new Exception("Erreur lors de l'upload de l'image.");
            }
        }
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mon Profil - Deefy</title>
  <link rel="stylesheet" href="../ressources/css/ProfileStyle.css">
</head>
<body>
  <div class="profile-container">
    <a href="../index.php" class="back-link">← Retour à l'accueil</a>

    <div class="profile-header">
      <h1>Mon Profil</h1>
    </div>

    <?php if ($successMessage): ?>
      <div class="alert alert-success">
        ✓ <?= htmlspecialchars($successMessage) ?>
      </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
      <div class="alert alert-error">
        ✗ <?= htmlspecialchars($errorMessage) ?>
      </div>
    <?php endif; ?>

    <!-- Section Avatar -->
    <div class="profile-avatar-section">
      <img 
        src="../<?= htmlspecialchars($user['avatar'] ?? 'ressources/images/defaut-avatar.png') ?>" 
        alt="Avatar" 
        class="profile-avatar-large"
        id="avatarPreview"
      >
      
      <form method="POST" enctype="multipart/form-data" class="avatar-form">
        <div class="form-group">
          <label for="avatar">Changer la photo de profil</label>
          <input 
            type="file" 
            id="avatar" 
            name="avatar" 
            accept="image/*"
            onchange="previewImage(event)"
          >
          <p class="info-text">Format accepté : JPG, PNG, GIF, WEBP (max 5MB)</p>
        </div>
        <button type="submit" class="btn-primary">Mettre à jour la photo</button>
      </form>
    </div>

    <!-- Informations du compte -->
    <div class="form-section">
      <h2>Informations du compte</h2>
      
      <div class="user-info-display">
        <p><strong>Email :</strong> <?= htmlspecialchars($user['email']) ?></p>
        <p><strong>Nom d'utilisateur actuel :</strong> <?= htmlspecialchars($user['username'] ?? 'Non défini') ?></p>
      </div>

      <form method="POST">
        <div class="form-group">
          <label for="username">Nouveau nom d'utilisateur</label>
          <input 
            type="text" 
            id="username" 
            name="username" 
            value="<?= htmlspecialchars($user['username'] ?? '') ?>"
            placeholder="Entrez votre nom d'utilisateur"
            required
            minlength="3"
          >
          <p class="info-text">Minimum 3 caractères</p>
        </div>

        <button type="submit" name="update_username" class="btn-primary">
          Mettre à jour le nom
        </button>
      </form>
    </div>

    <!-- Actions -->
    <div class="form-section">
      <h2>Actions</h2>
      <a href="../index.php" class="btn-secondary">Retour à l'accueil</a>
      <a href="Log_Sig.php?action=logout" class="btn-primary btn-logout">
        Se déconnecter
      </a>
    </div>
  </div>

  <script>
    // Prévisualisation de l'image
    function previewImage(event) {
      const file = event.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          document.getElementById('avatarPreview').src = e.target.result;
        };
        reader.readAsDataURL(file);
      }
    }
  </script>
</body>
</html>