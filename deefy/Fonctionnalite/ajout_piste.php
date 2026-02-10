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
    <link rel="stylesheet" href="../ressources/css/IndexPlaylistStyle.css">
</head>
<body>
    <div class="confirmation-card">
        <?php
        $coverPath = '../ressources/images/piste/default_cover.png';
        if (!empty($track['cover'])) {
            $coverPath = '../' . htmlspecialchars($track['cover']);
        }
        ?>
        
        <img src="<?= $coverPath ?>" 
             alt="<?= htmlspecialchars($track['titre']) ?>"
             onerror="this.src='../ressources/images/piste/default_cover.png">
        
        <h3>Vous venez d'ajouter cette piste !</h3>
        
    
        <p>
            <?= htmlspecialchars($track['titre']) ?> 
            - 
            <?= htmlspecialchars($track['artiste_album']) ?>
        </p>
        
        <a href="../index.php">← Retour à l'accueil</a>
        <a href="playlist.php?id=<?= (int)$playlistId ?>">← Voir la playlist</a>
    </div>
</body>
</html>
