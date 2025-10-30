<?php
session_start();
require_once '../classes/init.php';
require_once '../classes/Utilisateur/UtilManage.php';

use Deefy\Utilisateur\UtilManage;

$utilManage = new UtilManage($pdo);

if(isset($_GET['id'])) {
    $playlistId = (int)$_GET['id'];
    $playlist = $utilManage->getPlaylistById($playlistId, $_SESSION['user']['id'], $_SESSION['user']['role']);
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

    // Créer un tableau des ID des pistes déjà dans la playlist
    $trackDansPlaylist = array_column($tracks, 'id');


    // Gestion de la recherche de piste
    $searchResults = [];
    if(isset($_GET['search']) && !empty($_GET['search'])) {
        $term = '%' . $_GET['search'] . '%';
        $stmt3 = $pdo->prepare("
            SELECT * 
            FROM track 
            WHERE titre LIKE :term OR artiste_album LIKE :term OR genre LIKE :term
            ORDER BY titre ASC
            LIMIT 20
        ");
        $stmt3->execute([':term' => $term]);
        $searchResults = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    }

} else {
    die("Aucune playlist sélectionnée.");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Playlist - <?= htmlspecialchars($playlist['nom']) ?></title>
    <link rel="stylesheet" href="../ressources/css/PlaylistStyle.css?v=1.0">
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
        <form action="" method="GET">
            <input type="hidden" name="id" value="<?= $playlist['id'] ?>">
            
            <label for="search">Rechercher une piste :</label>
            <input type="text" name="search" id="search" placeholder="Titre, artiste ou genre..." required>
            
            <button type="submit">Rechercher</button>
        </form>

        <?php if(!empty($searchResults)): ?>
    <h4>Résultats :</h4>
    <div class="tracks-container">
        <?php foreach($searchResults as $t): ?>
            <div class="track-card">
                <img src="<?= '../' . $t['cover'] ?>" alt="cover">
                <h4><?= htmlspecialchars($t['titre']) ?></h4>
                <p><?= htmlspecialchars($t['artiste_album']) ?></p>
                <p><?= htmlspecialchars($t['genre']) ?></p>
                <?php if(in_array($t['id'], $trackDansPlaylist)): ?>
                <!-- Si déjà dans la playlist -->
                <div class="added-check">
                    ✅ Ajouté
                </div>
                <?php else: ?>
                <form action="Ajout_Piste.php" method="POST">
                    <input type="hidden" name="playlist_id" value="<?= $playlist['id'] ?>">
                    <input type="hidden" name="track_id" value="<?= $t['id'] ?>">
                    <button type="submit">Ajouter</button>
                </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>




    <p><a href="../index.php">← Retour à l'accueil</a></p>
</body>
</html>
