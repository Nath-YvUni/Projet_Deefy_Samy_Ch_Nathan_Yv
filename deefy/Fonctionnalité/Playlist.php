<?php
session_start();
require_once '../classes/init.php';
require_once '../classes/Utilisateur/UtilManage.php';

use Deefy\Utilisateur\UtilManage;

$utilManage = new UtilManage($pdo);

if(isset($_GET['id'])) {
    $playlistId = (int)$_GET['id'];

    // Récupérer la playlist
    $stmt = $pdo->prepare("
        SELECT p.id, p.nom, u.username
        FROM playlist p
        LEFT JOIN user2playlist up ON p.id = up.id_pl
        LEFT JOIN user u ON up.id_user = u.id
        WHERE p.id = :id
    ");
    $stmt->execute(['id' => $playlistId]);
    $playlist = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$playlist) die("Playlist introuvable.");
    $_SESSION['user']['current_playlist'] = $playlist;

    // Récupérer les pistes déjà dans la playlist
    $stmt2 = $pdo->prepare("
        SELECT t.*
        FROM track t
        INNER JOIN playlist2track p2t ON t.id = p2t.id_track
        WHERE p2t.id_pl = :id
        ORDER BY p2t.no_piste_dans_liste ASC
    ");
    $stmt2->execute(['id' => $playlistId]);
    $tracks = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer toutes les musiques disponibles
    $stmt3 = $pdo->query("SELECT id, titre, artiste_album FROM track ORDER BY titre ASC");
    $allTracks = $stmt3->fetchAll(PDO::FETCH_ASSOC);

} else {
    die("Aucune playlist sélectionnée.");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Playlist - <?= htmlspecialchars($playlist['nom']) ?></title>
    <link rel="stylesheet" href="../ressources/css/PlaylistStyle.css">
</head>
<body>
    <h2>Playlist : <?= htmlspecialchars($playlist['nom']) ?></h2>
    <p>Propriétaire : <?= htmlspecialchars($playlist['username'] ?? 'Inconnu') ?></p>

    <h3>Pistes :</h3>
    <ul>
        <?php foreach($tracks as $t): ?>
            <li><?= htmlspecialchars($t['titre']) ?> - <?= htmlspecialchars($t['artiste_album']) ?></li>
        <?php endforeach; ?>
    </ul>

    <h3>Ajouter une piste :</h3>
    <form action="add_track.php" method="POST">
        <input type="hidden" name="playlist_id" value="<?= $playlist['id'] ?>">

        <label for="track">Choisir une musique :</label>
        <select name="track_id" id="track" required>
            <option value="">-- Sélectionner une musique --</option>
            <?php foreach($allTracks as $t): ?>
                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['titre'] . ' - ' . $t['artiste_album']) ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Ajouter</button>
    </form>

    <p><a href="../index.php">← Retour à l'accueil</a></p>
</body>
</html>
