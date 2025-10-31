<?php
session_start();

require_once __DIR__ . '/../classes/init.php';
require_once __DIR__ . '/../classes/Utilisateur/UtilManage.php';

use Deefy\Utilisateur\UtilManage;

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header('Location: ../Fonctionnalite/Log_Sig.php');
    exit;
}

$message = '';
$messageType = '';

// === Création de playlist ===
if (isset($_POST['creer'])) {
    $nom = trim($_POST['nom']);
    $image = "ressources/images/playlist/img_playlist.png"; // image par défaut

    try {
        if (empty($nom)) {
            throw new Exception("⚠️ Le nom de la playlist ne peut pas être vide.");
        }

        // === Gestion de l'upload d'image ===
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) {
                throw new Exception("Format d'image non autorisé. Utilisez JPG, PNG, GIF ou WEBP.");
            }

            if ($_FILES['image']['size'] > 5000000) { // 5MB max
                throw new Exception("L'image est trop volumineuse (max 5MB).");
            }

            $uploadDir = __DIR__ . '../../ressources/images/playlist/';

            // Créer le dossier s'il n'existe pas
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Nom unique
            $newFilename = uniqid('playlist_') . '.' . $ext;
            $destination = $uploadDir . $newFilename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                // Chemin relatif (pour stockage BD)
                $image = 'ressources/images/playlist/' . $newFilename;
            } else {
                throw new Exception("Erreur lors de l'upload de l'image.");
            }
        }

        // === Insertion dans la base avec l'ID utilisateur ===
        $sql = "INSERT INTO playlist (nom, image) VALUES (:nom, :image)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'nom' => $nom, 
            'image' => $image
        ]);

        $sql = "INSERT INTO user2playlist (id_user, id_pl) VALUES (:id_user, :id_pl)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id_user' => $_SESSION['user']['id'],
            'id_pl' => $pdo->lastInsertId()
        ]);

        $idNouvellePlaylist = $pdo->lastInsertId();
        
        // Mettre à jour la session avec la nouvelle playlist
        $utilManage = new UtilManage($pdo);
        $nouvellePlaylist = $utilManage->getPlaylistById(
            $idNouvellePlaylist, 
            $_SESSION['user']['id'], 
            $_SESSION['user']['role']
        );
        $_SESSION['user']['current_playlist'] = $nouvellePlaylist;

        $message = "✅ Playlist <strong>" . htmlspecialchars($nom) . "</strong> créée avec succès !";
        $messageType = 'success';

        // Rediriger vers la page de la playlist après 2 secondes
        header("refresh:2;url=playlist.php?id=" . $idNouvellePlaylist);

    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Créer une Playlist</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            color: #555;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        input[type=text], input[type=file] {
            padding: 12px;
            margin-bottom: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
        }

        input[type=text]:focus {
            outline: none;
            border-color: #667eea;
        }

        input[type=file] {
            padding: 10px;
            cursor: pointer;
        }

        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        button:active {
            transform: translateY(0);
        }

        .btn-back {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            transition: background 0.3s;
        }

        .btn-back:hover {
            background: #5a6268;
        }

        .file-info {
            font-size: 12px;
            color: #666;
            margin-top: -15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>🎶 Créer une nouvelle playlist</h1>

    <?php if (!empty($message)): ?>
        <div class="message <?= $messageType ?>">
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