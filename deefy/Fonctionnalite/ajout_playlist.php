<?php
session_start();

require_once __DIR__ . '/../classes/init.php';
require_once __DIR__ . '/../classes/Utilisateur/UtilManage.php';

use Deefy\Utilisateur\UtilManage;

if (!isset($_SESSION['user'])) {
    header('Location: ../Fonctionnalite/log_sig.php');
    exit;
}

$message = '';
$messageType = '';

if (isset($_POST['creer'])) {
    $nom = trim($_POST['nom']);
    $image = "ressources/images/playlist/img_playlist.png"; // image par défaut

    try {
        if (empty($nom)) {
            throw new Exception("⚠️ Le nom de la playlist ne peut pas être vide.");
        }
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowed)) {
                throw new Exception("Format d'image non autorisé. Utilisez JPG, PNG, GIF ou WEBP.");
            }
            
            // ✅ CORRECTION LIGNES 36-37
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo === false) {
                throw new Exception("Erreur lors de la vérification du type de fichier.");
            }
            
            $mimeType = finfo_file($finfo, $_FILES['image']['tmp_name']);
            finfo_close($finfo);
            
            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            if (!in_array($mimeType, $allowedMimes)) {
                throw new Exception("Type de fichier non autorisé.");
            }

            if ($_FILES['image']['size'] > 5000000) { 
                throw new Exception("L'image est trop volumineuse (max 5MB).");
            }

            $uploadDir = __DIR__ . '/../ressources/images/playlist/';

            $newFilename = uniqid('playlist_') . '.' . $ext;
            $destination = $uploadDir . $newFilename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                $image = 'ressources/images/playlist/' . $newFilename;
            } else {
                throw new Exception("Erreur lors de l'upload de l'image.");
            }
        }

        $sql = "INSERT INTO playlist (nom, image) VALUES (:nom, :image)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'nom' => $nom, 
            'image' => $image
        ]);

        $idNouvellePlaylist = $pdo->lastInsertId();

        $sql = "INSERT INTO user2playlist (id_user, id_pl) VALUES (:id_user, :id_pl)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id_user' => $_SESSION['user']['id'],
            'id_pl' => $idNouvellePlaylist
        ]);
        
        $utilManage = new UtilManage($pdo);
        $nouvellePlaylist = $utilManage->getPlaylistById(
            $idNouvellePlaylist, 
            $_SESSION['user']['id'], 
            $_SESSION['user']['role']
        );
        $_SESSION['user']['current_playlist'] = $nouvellePlaylist;

        $message = "✅ Playlist <strong>" . htmlspecialchars($nom) . "</strong> créée avec succès !";
        $messageType = 'success';

        header("refresh:2;url=playlist.php?id=" . $idNouvellePlaylist);

    } catch (Exception $e) {
        $message = htmlspecialchars($e->getMessage()); // ✅ PROTECTION XSS
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Créer une Playlist</title>
    <link rel="stylesheet" href="../ressources/css/CreaPlaylist.css">
</head>
<body>

<div class="container">
    <h1>🎶 Créer une nouvelle playlist</h1>

    <?php if (!empty($message)): ?>
        <div class="message <?= htmlspecialchars($messageType) ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <form method="post" action="" enctype="multipart/form-data">
        <label for="nom">Nom de la playlist :</label>
        <input type="text" id="nom" name="nom" required placeholder="Ma super playlist">

        <label for="image">Image de couverture (optionnelle) :</label>
        <input type="file" id="image" name="image" accept="image/*">
        <div class="file-info">Formats acceptés : JPG, PNG, GIF, WEBP (max 5MB)</div>

        <button type="submit" name="creer">✨ Créer la playlist</button>
    </form>

    <a href="../index.php" class="btn-back">← Retour à l'accueil</a>
</div>

</body>
</html>