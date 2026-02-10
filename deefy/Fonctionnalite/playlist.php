<?php
// playlist/Playlist.php
session_start();
require_once '../classes/init.php';
require_once '../classes/Utilisateur/UtilManage.php';

use Deefy\Utilisateur\UtilManage;

$utilManage = new UtilManage($pdo);

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Gestion POST: suppression dans le même fichier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['op']) && $_POST['op'] === 'del') {
    // Sécurité CSRF
    if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $redirect = isset($_POST['redirect']) ? (string)$_POST['redirect'] : '../index.php';
        header('Location: ' . $redirect . (str_contains($redirect, '?') ? '&' : '?') . 'status=csrf');
        exit;
    }

    // Inputs
    $playlistId = filter_input(INPUT_POST, 'playlist_id', FILTER_VALIDATE_INT);
    $trackId    = filter_input(INPUT_POST, 'track_id', FILTER_VALIDATE_INT);
    $redirect   = isset($_POST['redirect']) ? (string)$_POST['redirect'] : '../index.php';

    if (!$playlistId || !$trackId || !isset($_SESSION['user'])) {
        header('Location: ' . $redirect . (str_contains($redirect, '?') ? '&' : '?') . 'status=bad_input');
        exit;
    }

    // Autorisation via UtilManage
    $playlist = $utilManage->getPlaylistById(
        $playlistId,
        $_SESSION['user']['id'],
        $_SESSION['user']['role']
    );
    if (!$playlist) {
        header('Location: ' . $redirect . (str_contains($redirect, '?') ? '&' : '?') . 'status=forbidden');
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Supprimer l'association
        $del = $pdo->prepare('DELETE FROM playlist2track WHERE id_pl = :pl AND id_track = :tr');
        $del->execute([':pl' => $playlistId, ':tr' => $trackId]);

        // Renuméroter l'ordre restant
        $sel = $pdo->prepare('SELECT id_track FROM playlist2track WHERE id_pl = :pl ORDER BY no_piste_dans_liste ASC');
        $sel->execute([':pl' => $playlistId]);
        $leftTracks = $sel->fetchAll(PDO::FETCH_COLUMN);

        $pos = 1;
        $upd = $pdo->prepare('UPDATE playlist2track SET no_piste_dans_liste = :pos WHERE id_pl = :pl AND id_track = :tr');
        foreach ($leftTracks as $tid) {
            $upd->execute([':pos' => $pos++, ':pl' => $playlistId, ':tr' => (int)$tid]);
        }

        $pdo->commit();

        header('Location: ' . $redirect . (str_contains($redirect, '?') ? '&' : '?') . 'status=deleted');
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        header('Location: ' . $redirect . (str_contains($redirect, '?') ? '&' : '?') . 'status=error');
        exit;
    }
}

// ---- GET: affichage playlist ----
if(isset($_GET['id'])) {
    $playlistId = (int)$_GET['id'];
    if (!isset($_SESSION['user'])) {
        die("Accès refusé.");
    }
    $playlist = $utilManage->getPlaylistById($playlistId, $_SESSION['user']['id'], $_SESSION['user']['role']);
    if(!$playlist) die("Playlist introuvable.");
    $_SESSION['user']['current_playlist'] = $playlist;

    // Pistes de la playlist (avec ordre)
    $stmt2 = $pdo->prepare("
        SELECT t.*
        FROM track t
        INNER JOIN playlist2track p2t ON t.id = p2t.id_track
        WHERE p2t.id_pl = :id
        ORDER BY p2t.no_piste_dans_liste ASC
    ");
    $stmt2->execute(['id' => $playlistId]);
    $tracks = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // IDs déjà dans la playlist
    $trackDansPlaylist = array_column($tracks, 'id');

    // Recherche
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
    <link rel="stylesheet" href="../ressources/css/IndexPlaylistStyle.css">
</head>
<body>
    <?php if (isset($_GET['status'])): ?>
        <?php
            $messages = [
                'deleted'   => "Piste supprimée de la playlist.",
                'csrf'      => "Action refusée (CSRF).",
                'forbidden' => "Action non autorisée.",
                'bad_input' => "Paramètres invalides.",
                'error'     => "Une erreur est survenue."
            ];
            $status = $_GET['status'];
        ?>
        <p><?= htmlspecialchars($messages[$status] ?? '') ?></p>
    <?php endif; ?>

    <h2>Playlist : <?= htmlspecialchars($playlist['nom']) ?></h2>
    <p>Propriétaire : <?= htmlspecialchars($playlist['username'] ?? 'Inconnu') ?></p>

    <h3>Pistes :</h3>
    <ul>
        <?php foreach($tracks as $t): ?>
            <li>
                <?= htmlspecialchars($t['titre']) ?> - <?= htmlspecialchars($t['artiste_album']) ?>
                <form action="" method="POST" style="display:inline" onsubmit="return confirm('Supprimer cette piste de la playlist ?');">
                    <input type="hidden" name="op" value="del">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="playlist_id" value="<?= (int)$playlist['id'] ?>">
                    <input type="hidden" name="track_id" value="<?= (int)$t['id'] ?>">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                    <button type="submit">Supprimer</button>
                </form>
            </li>
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
                <?php
                    $cover = $t['cover'];
                    if (preg_match('#/././#', $cover) || preg_match('#^(https?|ftp):#i', $cover)) {
                        $cover = 'ressources/images/default-cover.png';
                    }
                ?>
                <img src="<?= '../' . htmlspecialchars($cover) ?>" alt="cover">

                <h4><?= htmlspecialchars($t['titre']) ?></h4>
                <p><?= htmlspecialchars($t['artiste_album']) ?></p>
                <p><?= htmlspecialchars($t['genre']) ?></p>

                <?php if(in_array($t['id'], $trackDansPlaylist)): ?>
                    <div class="added-check">✅ Ajouté</div>
                    <form action="" method="POST" style="margin-top:6px" onsubmit="return confirm('Supprimer cette piste de la playlist ?');">
                        <input type="hidden" name="op" value="del">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="playlist_id" value="<?= (int)$playlist['id'] ?>">
                        <input type="hidden" name="track_id" value="<?= (int)$t['id'] ?>">
                        <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                        <button type="submit">Supprimer</button>
                    </form>
                <?php else: ?>
                    <form action="ajout_piste.php" method="POST">
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
