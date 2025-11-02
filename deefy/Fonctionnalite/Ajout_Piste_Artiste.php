<?php
session_start();
require_once '../classes/init.php';

$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $audio_ok = false;
    $filename = '';
    $basename = '';

    if (isset($_FILES['audio']) && $_FILES['audio']['error'] === UPLOAD_ERR_OK) {

    $audio_info = pathinfo($_FILES['audio']['name']);
    $audio_ext = strtolower($audio_info['extension']);

    if ($audio_ext === 'mp3') {

        // ✅ On utilise le nom du titre entré par l'utilisateur
        $titre = $_POST['titre'] ?? 'SansTitre';

        // Nettoyage pour éviter caractères interdits
        $basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $titre);

        $audio_dir = __DIR__ . '/../ressources/audio/';
        if (!is_dir($audio_dir)) mkdir($audio_dir, 0755, true);

        $audio_name = $basename . '.mp3';
        $audio_dest = $audio_dir . $audio_name;

        if (move_uploaded_file($_FILES['audio']['tmp_name'], $audio_dest)) {
            $filename = 'ressources/audio/' . $audio_name;
            $audio_ok = true;
        } else {
            $error = "Erreur lors de l'upload du mp3.";
        }

    } else {
        $error = "Seuls les fichiers MP3 sont autorisés.";
    }

} else {
    $error = "Aucun fichier audio sélectionné.";
}


    $cover_path = 'ressources/images/piste/default_cover.png';
    if ($audio_ok && isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        $allowed_cover = ['jpg','jpeg','png','gif','webp'];
        $cover_info = pathinfo($_FILES['cover']['name']);
        $cover_ext = strtolower($cover_info['extension']);
        $cover_filename = $basename . '.' . $cover_ext;

        $cover_mimes = ['image/jpeg','image/png','image/gif','image/webp'];
        $mime = mime_content_type($_FILES['cover']['tmp_name']);
        if (in_array($cover_ext, $allowed_cover) && in_array($mime, $cover_mimes) && $_FILES['cover']['size'] < 5*1024*1024) {
            $cover_dir = __DIR__ . '/../ressources/images/piste/';
            if (!is_dir($cover_dir)) mkdir($cover_dir, 0755, true);

            $cover_dest = $cover_dir . $cover_filename;
            if (move_uploaded_file($_FILES['cover']['tmp_name'], $cover_dest)) {
                $cover_path = 'ressources/images/piste/' . $cover_filename;
            } else { $error = "Erreur upload cover."; }
        } else { $error = "Format cover non accepté ou trop lourd."; }
    }

    if ($audio_ok && empty($error)) {
        $titre = trim($_POST['titre']);
        $genre = trim($_POST['genre']);
        $artiste_album = trim($_POST['artiste_album']);
        $titre_album = trim($_POST['titre_album']);
        $annee_album = trim($_POST['annee_album']);

        $stmt = $pdo->prepare("INSERT INTO track (titre, genre, filename, artiste_album, titre_album, annee_album, cover)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $titre, $genre, $filename, $artiste_album, $titre_album, $annee_album, $cover_path
        ]);
        $success = "La piste a bien été ajoutée !";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajout Piste Artiste</title>
    <link rel="stylesheet" href="../ressources/css/AjoutPisteArtiste.css">
</head>
<body>
<div class="ajout-piste-container">
    <h1>Ajouter une piste</h1>
    <?php if ($success): ?>
        <div class="message success"><?= htmlspecialchars($success) ?></div>
    <?php elseif ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label for="titre">Titre de la piste (affiché) :</label>
        <input type="text" id="titre" name="titre" required>

        <label for="genre">Genre :</label>
        <input type="text" id="genre" name="genre">

        <label for="audio">Fichier audio (.mp3 uniquement) :</label>
        <input type="file" id="audio" name="audio" accept=".mp3" required>
        <div style="font-size:13px; color:#888; margin-bottom:15px;">
            Le nom du fichier .mp3 uploadé sera utilisé (ex: "nomdelapiste.mp3").
        </div>

        <label for="artiste_album">Artiste album :</label>
        <input type="text" id="artiste_album" name="artiste_album">

        <label for="titre_album">Titre album :</label>
        <input type="text" id="titre_album" name="titre_album">

        <label for="annee_album">Année album :</label>
        <input type="number" id="annee_album" name="annee_album" min="1900" max="2099">

        <label for="cover">Cover (même nom de base que la piste) :</label>
        <input type="file" id="cover" name="cover" accept=".jpg,.jpeg,.png,.gif,.webp">
        <div style="font-size:13px; color:#888; margin-bottom:18px;">
            Le nom de la cover sera "nomdelapiste.jpg/png/gif/webp".
        </div>

        <button type="submit">Ajouter la piste</button>
    </form>
    <a class="btn-back" href="../index.php">Retour à l’accueil</a>
</div>
</body>
</html>
