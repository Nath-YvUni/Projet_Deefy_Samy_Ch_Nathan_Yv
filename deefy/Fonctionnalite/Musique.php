<?php
session_start();
require_once '../classes/init.php'; // ton init pour $pdo

// Récupération sécurisée
$playlistId = $_GET['playlist_id'] ?? null;
$titre = $_GET['titre'] ?? null;
$artiste = $_GET['artiste'] ?? null;
$cover = $_GET['cover'] ?? '../ressources/images/defaut-cover.png';
$fichier = $_GET['fichier'] ?? null;

// Si une playlist est passée, on récupère toutes ses musiques
$tracks = [];
if ($playlistId) {
    // CORRECTION: Jointure avec la table track pour récupérer les infos complètes
    $stmt = $pdo->prepare("
        SELECT t.*, p2t.no_piste_dans_liste 
        FROM playlist2track p2t
        JOIN track t ON p2t.id_track = t.id
        WHERE p2t.id_pl = ?
        ORDER BY p2t.no_piste_dans_liste
    ");
    $stmt->execute([$playlistId]);
    $tracks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($tracks)) {
        // Prendre la première piste de la playlist
        $first = $tracks[0];
        $titre = $first['titre'];
        $artiste = $first['artiste_album'];
        $cover = $first['cover'] ?: '../ressources/images/defaut-cover.png';
        $fichier = $first['filename'];
    }
}

// Vérification du fichier audio
if ($playlistId) {
    // Mode playlist : vérifier qu'il y a au moins une track
    $musicExists = !empty($tracks);
} else {
    // Mode fichier unique : vérifier que le fichier existe
    $musicExists = !empty($fichier) && file_exists("../" . $fichier);
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($titre ?? 'Titre inconnu') ?> - Deefy</title>
<style>
    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #191414, #1db954);
      color: white;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }

    .player {
      background: rgba(0, 0, 0, 0.6);
      padding: 40px;
      border-radius: 20px;
      text-align: center;
      box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
      width: 350px;
    }

    .cover {
      width: 200px;
      height: 200px;
      border-radius: 50%;
      margin-bottom: 25px;
      box-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
      object-fit: cover;
      animation: spin 20s linear infinite paused;
    }

    .cover.playing {
      animation-play-state: running;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    h1 {
      font-size: 22px;
      margin-bottom: 5px;
    }

    h2 {
      font-size: 16px;
      color: #aaa;
      margin-bottom: 20px;
    }

    .controls {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 15px;
      margin-bottom: 20px;
    }

    button {
      background: #1db954;
      border: none;
      border-radius: 50%;
      width: 50px;
      height: 50px;
      color: white;
      font-size: 20px;
      cursor: pointer;
      transition: 0.2s;
    }

    button#play-btn {
      width: 60px;
      height: 60px;
      font-size: 28px;
    }

    button:hover {
      transform: scale(1.1);
      background: #1ed760;
    }

    .progress-container {
      width: 100%;
      height: 6px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 3px;
      margin-top: 20px;
      cursor: pointer;
    }

    .progress {
      height: 6px;
      background: #1db954;
      border-radius: 3px;
      width: 0%;
      transition: width 0.1s linear;
    }

    .time-info {
      display: flex;
      justify-content: space-between;
      font-size: 12px;
      color: #aaa;
      margin-top: 8px;
    }

    a {
      display: inline-block;
      margin-top: 25px;
      color: #1db954;
      text-decoration: none;
    }

    a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
<header class="topbar" style="position:absolute; top:20px; left:30px;">
<a href="../index.php" style="color:white; text-decoration:none;">⬅ Retour</a>
</header>

<?php if ($musicExists): ?>
<div class="player">
    <img src="<?= "../" . htmlspecialchars($cover) ?>" alt="cover" class="cover" id="cover">
    <h1 id="track-title"><?= htmlspecialchars($titre ?? 'Titre inconnu') ?></h1>
    <h2 id="track-artist"><?= htmlspecialchars($artiste ?? 'Artiste inconnu') ?></h2>

    <div class="controls">
        <?php if ($playlistId && count($tracks) > 1): ?>
        <button id="prev-btn">⏮</button>
        <?php endif; ?>
        
        <button id="play-btn">▶</button>
        
        <?php if ($playlistId && count($tracks) > 1): ?>
        <button id="next-btn">⏭</button>
        <?php endif; ?>
    </div>

    <div class="progress-container" id="progress-container">
        <div class="progress" id="progress"></div>
    </div>

    <div class="time-info">
        <span id="current-time">0:00</span>
        <span id="duration">0:00</span>
    </div>

    <audio id="audio" src="<?= '../' . htmlspecialchars($fichier) ?>" preload="metadata"></audio>
</div>

<script>
// Éléments DOM
const audio = document.getElementById('audio');
const playBtn = document.getElementById('play-btn');
const cover = document.getElementById('cover');
const progress = document.getElementById('progress');
const progressContainer = document.getElementById('progress-container');
const currentTimeEl = document.getElementById('current-time');
const durationEl = document.getElementById('duration');
const nextBtn = document.getElementById('next-btn');
const prevBtn = document.getElementById('prev-btn');
const trackTitle = document.getElementById('track-title');
const trackArtist = document.getElementById('track-artist');

let isPlaying = false;

// Playlist depuis PHP
const tracks = <?= json_encode($tracks) ?>;
let currentIndex = 0;

// Debug
console.log('Tracks chargées:', tracks);
console.log('Fichier audio initial:', audio.src);

// Jouer / Pause
playBtn.addEventListener('click', () => {
    if (isPlaying) {
        audio.pause();
        playBtn.textContent = '▶';
        cover.classList.remove('playing');
        isPlaying = false;
    } else {
        audio.play().catch(err => {
            console.error('Erreur de lecture:', err);
            alert('Impossible de lire: ' + audio.src);
        });
        playBtn.textContent = '⏸';
        cover.classList.add('playing');
        isPlaying = true;
    }
});

// Suivant / Précédent
if (nextBtn) {
    nextBtn.addEventListener('click', () => changeTrack(1));
}
if (prevBtn) {
    prevBtn.addEventListener('click', () => changeTrack(-1));
}

function changeTrack(direction) {
    if (tracks.length === 0) return;
    currentIndex = (currentIndex + direction + tracks.length) % tracks.length;
    const track = tracks[currentIndex];
    
    audio.src = '../' + track.filename;
    trackTitle.textContent = track.titre;
    trackArtist.textContent = track.artiste_album || 'Artiste inconnu';
    cover.src = '../' + (track.cover || 'ressources/images/defaut-cover.png');
    
    audio.play().catch(console.error);
    isPlaying = true;
    playBtn.textContent = '⏸';
    cover.classList.add('playing');
}

// Progression
audio.addEventListener('timeupdate', () => {
    const progressPercent = (audio.currentTime / audio.duration) * 100;
    progress.style.width = progressPercent + '%';
    currentTimeEl.textContent = formatTime(audio.currentTime);
});

audio.addEventListener('loadedmetadata', () => {
    durationEl.textContent = formatTime(audio.duration);
});

audio.addEventListener('canplay', () => {
    if (durationEl.textContent === '0:00') {
        durationEl.textContent = formatTime(audio.duration);
    }
});

progressContainer.addEventListener('click', e => {
    const width = progressContainer.clientWidth;
    audio.currentTime = (e.offsetX / width) * audio.duration;
});

// Fin de la musique -> suivant si playlist
audio.addEventListener('ended', () => {
    if (tracks.length > 1) {
        changeTrack(1);
    } else {
        isPlaying = false;
        playBtn.textContent = '▶';
        cover.classList.remove('playing');
        audio.currentTime = 0;
    }
});

function formatTime(seconds) {
    if (isNaN(seconds)) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return mins + ':' + (secs < 10 ? '0' : '') + secs;
}
</script>
<?php else: ?>
<div style="color: #ff4b4b; text-align: center; padding: 40px;">
    <p>Musique introuvable ou chemin invalide</p>
    <pre style="background: rgba(0,0,0,0.5); padding: 20px; border-radius: 10px; text-align: left; margin: 20px auto; max-width: 600px;">
DEBUG INFO:
- Playlist ID: <?= var_export($playlistId, true) ?>

- Tracks trouvées: <?= count($tracks) ?>

- Fichier: <?= htmlspecialchars($fichier ?? 'non défini') ?>

- Music Exists: <?= var_export($musicExists, true) ?>

<?php if (!empty($tracks)): ?>
Première track:
<?= print_r($tracks[0], true) ?>
<?php endif; ?>
    </pre>
</div>
<?php endif; ?>
</body>
</html>