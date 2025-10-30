<?php
session_start();
require_once '../classes/init.php';
require_once '../classes/Utilisateur/UtilManage.php';

use Deefy\Utilisateur\UtilManage;

$utilManage = new UtilManage($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $playlistId = (int)$_POST['playlist_id'];
    $trackId = (int)$_POST['track_id'];

    // Récupérer info de la piste
    $stmt = $pdo->prepare("SELECT * FROM track WHERE id = :id");
    $stmt->execute(['id' => $trackId]);
    $track = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$track) {
        die("Piste introuvable.");
    }

    // Récupérer le prochain numéro de piste
    $stmtMax = $pdo->prepare("SELECT COALESCE(MAX(no_piste_dans_liste),0) AS max_no FROM playlist2track WHERE id_pl = :pl");
    $stmtMax->execute(['pl' => $playlistId]);
    $maxNo = $stmtMax->fetchColumn();
    $nextNo = $maxNo + 1;

    // Insérer la piste
    $stmt2 = $pdo->prepare("INSERT INTO playlist2track (id_pl, id_track, no_piste_dans_liste) VALUES (:pl, :track, :no)");
    $stmt2->execute(['pl' => $playlistId, 'track' => $trackId, 'no' => $nextNo]);

} else {
    die("Accès interdit.");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Piste ajoutée</title>
    <link rel="stylesheet" href="../ressources/css/PlaylistStyle.css">
    <style>
        body { background-color: #121212; color: white; font-family: Arial, sans-serif; padding: 20px; }
        .confirmation-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            background-color: #181818;
            border-radius: 8px;
            padding: 20px;
            width: 250px;
            margin: 40px auto;
            text-align: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.5);
        }
        .confirmation-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        .confirmation-card h3 {
            color: #1db954;
            margin-bottom: 5px;
        }
        .confirmation-card p {
            color: #b3b3b3;
            margin-bottom: 15px;
        }
        .confirmation-card a {
            display: inline-block;
            padding: 8px 16px;
            background-color: #1db954;
            color: white;
            border-radius: 20px;
            text-decoration: none;
            font-weight: bold;
        }
        .confirmation-card a:hover {
            background-color: #1ed760;
        }
    </style>
</head>
<body>
    <div class="confirmation-card">
        <img src="<?= htmlspecialchars('../' . $track['cover'] ?? '../ressources/img/default_track.png') ?>" alt="<?= htmlspecialchars($track['titre']) ?>">
        <h3>Vous venez d'ajouter cette piste !</h3>
        <p><?= htmlspecialchars($track['titre']) ?> - <?= htmlspecialchars($track['artiste_album']) ?></p>
        <a href="../index.php">← Retour à l'accueil</a>
        <a href="playlist.php?id=<?= $playlistId ?>">← Voir la playlist</a>
    </div>
</body>
</html>
