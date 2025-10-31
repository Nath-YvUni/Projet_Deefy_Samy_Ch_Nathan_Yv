<?php
// index.php
session_start();

// Inclure l'init pour récupérer $pdo
include __DIR__ . "/Fonctionnalite/Player.php"; 
require_once 'classes/init.php';
require_once 'classes/Utilisateur/UtilManage.php';

use Deefy\Utilisateur\UtilManage;

// Vérifier si l'utilisateur est connecté
$estConnecte = isset($_SESSION['user']);

if($estConnecte) {
    // Rafraîchir les données utilisateur depuis la base
    $utilManage = new UtilManage($pdo);
    $playlists = $utilManage->getPlaylists($_SESSION['user']['id'],$_SESSION['user']['role']);
}


// Récupérer les musiques
$stmt2 = $pdo->query("SELECT * FROM track");
$musics = $stmt2->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Deefy</title>
  <link rel="stylesheet" href="ressources/css/IndexStyle.css">
</head>
<body>
  <!-- Barre latérale -->
  <aside class="sidebar">
    <h2>Bibliothèque</h2>
    <button class="btn" onclick="window.location.href='Fonctionnalite/Ajout_Playlist.php'">+ Créer une playlist</button>
    <p>Vos playlists</p>
    <ul>
    <?php if (empty($playlists)): ?>
        <li style="color: #666;">Aucune playlist pour le moment</li>
    <?php else: ?>
        <?php if ($_SESSION['user']['role'] === 100): ?>
            <strong>ADMIN - Toutes les playlists :</strong>
            <?php foreach ($playlists as $p): ?>
                <li>
                    <a href="./Fonctionnalite/playlist.php?id=<?= $p['id'] ?>" class="playlist-link">
                        <strong><?= htmlspecialchars($p['nom']) ?></strong><br>
                        Utilisateur : <?= htmlspecialchars($p['username'] ?? 'Inconnu') ?>
                    </a>
                    <!-- Bouton Lecture à côté, hors du lien -->
            <form action="./Fonctionnalite/musique.php" method="get" style="margin:0;">
                  <input type="hidden" name="playlist_id" value="<?= $p['id'] ?>">
                  <button type="submit" style="background:none; border:none; cursor:pointer; font-size:16px; color:white;">▶</button>
              </form>

                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <?php foreach ($playlists as $p): ?>
                <li>
                    <a href="./Fonctionnalite/playlist.php?id=<?= $p['id'] ?>" class="playlist-link">
                        <?= htmlspecialchars($p['nom']) ?>
                    </a>
                    <!-- Bouton Lecture à côté, hors du lien -->
            <form action="./Fonctionnalite/musique.php" method="get" style="margin:0;">
                  <input type="hidden" name="playlist_id" value="<?= $p['id'] ?>">
                  <button type="submit" style="background:none; border:none; cursor:pointer; font-size:16px; color:white;">▶</button>
              </form>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</ul>

</aside>



  <!-- Barre du haut -->
  <header class="topbar">
    <div class="logo">
      <img src="ressources/images/Dee_fy.png" alt="Logo Deefy">
      <span>Deefy</span>
    </div>

    <input type="text" placeholder="Rechercher quelque chose...">
    
    
    <?php if ($estConnecte): ?>
    <div class="user-menu">
      <img
        src="<?= htmlspecialchars($_SESSION['user']['avatar'] ?? 'ressources/images/defaut-avatar.png') ?>"
        alt="Photo de profil"
        class="profile-pic"
        id="profileBtn"
      >

      <!-- MENU DÉROULANT -->
      <div class="dropdown-menu" id="dropdownMenu">
        <!-- TODO : Création d'une page profil et d'un lien vers une page pour ce login et s'inscrire mais qui peut aussi servir a se déco -->
        <a href="Fonctionnalite/profile.php">Mon profil</a>
        <hr>
        <a href="Fonctionnalite/Log_Sig.php?action=logout" class="logout">Se déconnecter</a>
      </div>
    </div>
    <?php else: ?>
      <a href="Fonctionnalite/Log_Sig.php" class="login">Se connecter / S'inscrire</a>
    <?php endif; ?>
  </header>

  <!-- Contenu principal -->
  <main class="content">
    <h1>Bienvenue sur Deefy</h1>
    <h2>Dernière playlist écoutée</h2>
<div>
  <?php if ($estConnecte && !empty($_SESSION['user']['current_playlist'])): ?>
      <?php
$img = !empty($_SESSION['user']['current_playlist']['image'])
    ? $_SESSION['user']['current_playlist']['image']
    : './ressources/images/playlist/defautPlaylist.png';
?>
<img src="<?= htmlspecialchars($img) ?>" alt="Cover Playlist" style="width:150px; height:150px;">
        <p>Playlist : <?= htmlspecialchars($_SESSION['user']['current_playlist']['nom']) ?></p>
      <p>Propriétaire : <?= htmlspecialchars($_SESSION['user']['current_playlist']['username'] ?? 'Inconnu') ?></p>
  <?php else: ?>
      <p>Aucune playlist écoutée récemment.</p>
  <?php endif; ?>
</div>
    <h2>Musiques du jour</h2>
    <div class="tracks">
      <?php foreach ($musics as $m): ?>
        <div class="track">
          <img src="<?= $m['cover'] ?>" alt="cover">
          <div class="title"><?= htmlspecialchars($m['titre']) ?></div>
          <div class="artist"><?= htmlspecialchars($m['artiste_album']) ?></div>
          <form action="./Fonctionnalite/musique.php" method="get">
            <input type="hidden" name="titre" value="<?= htmlspecialchars($m['titre']) ?>">
            <input type="hidden" name="artiste" value="<?= htmlspecialchars($m['artiste_album']) ?>">
            <input type="hidden" name="cover" value="<?= htmlspecialchars($m['cover']) ?>">
            <input type="hidden" name="fichier" value="<?= htmlspecialchars($m['filename']) ?>">
        <button type="submit">▶ Lecture</button>
      </form>
        </div>
      <?php endforeach; ?>
    </div>
  </main>


  <script>
const profileBtn = document.getElementById('profileBtn');
const dropdownMenu = document.getElementById('dropdownMenu');

if (profileBtn && dropdownMenu) {
  profileBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    dropdownMenu.classList.toggle('show');
  });

  document.addEventListener('click', (e) => {
    if (!dropdownMenu.contains(e.target) && e.target !== profileBtn) {
      dropdownMenu.classList.remove('show');
    }
  });
}
  </script>
</body>
</html>