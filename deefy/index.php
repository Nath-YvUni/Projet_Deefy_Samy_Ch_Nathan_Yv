<?php
// index.php
session_start();

// Inclure l'init pour récupérer $pdo
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
  <link rel="stylesheet" href="ressources/css/IndexStyle.css?=v=1.0">
</head>
<body>
  <!-- Barre latérale -->
  <aside class="sidebar">
    <h2>Bibliothèque</h2>
    <button class="btn">+ Créer une playlist</button>
    <p>Vos playlists</p>
    <ul>
    <?php if (empty($playlists)): ?>
        <li style="color: #666;">Aucune playlist pour le moment</li>
    <?php else: ?>
        <?php if ($_SESSION['user']['role'] === 100): ?>
            <strong>ADMIN - Toutes les playlists :</strong>
            <?php foreach ($playlists as $p): ?>
                <li>
                    <a href="./Fonctionnalité/playlist.php?id=<?= $p['id'] ?>" class="playlist-link">
                        <strong><?= htmlspecialchars($p['nom']) ?></strong><br>
                        Utilisateur : <?= htmlspecialchars($p['username'] ?? 'Inconnu') ?>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <?php foreach ($playlists as $p): ?>
                <li>
                    <a href="./Fonctionnalité/playlist.php?id=<?= $p['id'] ?>" class="playlist-link">
                        <?= htmlspecialchars($p['nom']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</ul>

</aside>



  <!-- Barre du haut -->
  <header class="topbar">
    <div class="logo">
      <img src="ressources/images/Deefy.png" alt="Logo Deefy">
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
        <a href="Fonctionnalité/profile.php">Mon profil</a>
        <hr>
        <a href="Fonctionnalité/Log_Sig.php?action=logout" class="logout">Se déconnecter</a>
      </div>
    </div>
    <?php else: ?>
      <a href="Fonctionnalité/Log_Sig.php" class="login">Se connecter / S'inscrire</a>
    <?php endif; ?>
  </header>

  <!-- Contenu principal -->
  <main class="content">
    <h2>Musiques du jour</h2>
    <div class="tracks">
      <?php foreach ($musics as $m): ?>
        <div class="track">
          <img src="<?= $m['cover'] ?>" alt="cover">
          <div class="title"><?= htmlspecialchars($m['title']) ?></div>
          <div class="artist"><?= htmlspecialchars($m['artist']) ?></div>
          <button>▶ Lecture</button>
        </div>
      <?php endforeach; ?>
    </div>
  </main>

  <!-- Lecteur -->
  <footer class="player">
    <div class="song-info">
      <p><strong>MUSIQUE</strong><br>Play et autre</p>
    </div>
    <div class="controls">
      <button>⏮</button>
      <button>⏯</button>
      <button>⏭</button>
    </div>
  </footer>

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