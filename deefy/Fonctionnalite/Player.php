<?php
// Player.php : Composant lecteur audio global

// Tu peux récupérer la playlist/id/musique à lancer depuis le LocalStorage via JS
?>

<div id="global-player" style="
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    z-index: 1000;
    background: rgba(25,20,20,0.95);
    box-shadow: 0 -2px 20px #0009;
    text-align: center;">
  <!-- Le markup du player : tu peux adapter le style ici -->
  <div class="player" style="display:inline-block;">
    <img src="../ressources/images/defaut-cover.png" alt="cover" class="cover" id="cover" style="width:80px; height:80px; border-radius:50%; vertical-align:middle;">
    <span id="track-title" style="font-size:16px; font-weight:600;">Aucune musique</span>
    <span id="track-artist" style="font-size:13px; color:#aaa; margin-left:10px;">-</span>
    <button id="play-btn" style="background:#1db954; border-radius:50%; width:40px; height:40px; margin-left:15px; font-size:20px; color:white; border:none;">▶</button>
    <audio id="audio" src="" preload="metadata" style="display:none;"></audio>
    <div class="progress-container" id="progress-container" style="
        width:40vw; max-width:500px; height:4px; background:rgba(255,255,255,0.2); margin:14px auto; border-radius:2px; display:block; position:relative;">
      <div class="progress" id="progress" style="
          height:4px; background:#1db954; border-radius:2px; width:0%; transition:width 0.1s linear;"></div>
    </div>
    <span id="current-time" style="font-size:12px; color:#aaa">0:00</span> /
    <span id="duration" style="font-size:12px; color:#aaa">0:00</span>
  </div>
</div>

<script>
// Joueur universel utilisant localStorage
const audio = document.getElementById('audio');
const playBtn = document.getElementById('play-btn');
const cover = document.getElementById('cover');
const progress = document.getElementById('progress');
const progressContainer = document.getElementById('progress-container');
const trackTitle = document.getElementById('track-title');
const trackArtist = document.getElementById('track-artist');
const currentTimeEl = document.getElementById('current-time');
const durationEl = document.getElementById('duration');
let isPlaying = false;

// Charge l'état du lecteur si existant
function loadPlayerState() {
    let state = JSON.parse(localStorage.getItem('deefy_player_state'));
    if (state && state.musicFile) {
        audio.src = state.musicFile;
        trackTitle.textContent = state.trackTitle ?? "Musique";
        trackArtist.textContent = state.trackArtist ?? "-";
        cover.src = state.cover ?? "../ressources/images/defaut-cover.png";
        audio.onloadedmetadata = function() {
            audio.currentTime = state.time || 0;
            durationEl.textContent = formatTime(audio.duration);
            currentTimeEl.textContent = formatTime(audio.currentTime);
            if(state.playing) {
                audio.play();
                playBtn.textContent = '⏸';
                cover.classList.add('playing');
                isPlaying = true;
            }
        };
    }
}
window.addEventListener('DOMContentLoaded', loadPlayerState);

// Sauvegarde du state à chaque update
function savePlayerState() {
    localStorage.setItem('deefy_player_state', JSON.stringify({
        time: audio.currentTime,
        playing: isPlaying,
        musicFile: audio.src,
        trackTitle: trackTitle.textContent,
        trackArtist: trackArtist.textContent,
        cover: cover.src
    }));
}
audio.addEventListener('timeupdate', savePlayerState);
audio.addEventListener('play', savePlayerState);
audio.addEventListener('pause', savePlayerState);
audio.addEventListener('ended', savePlayerState);

// Lecteur
playBtn.addEventListener('click', () => {
    if (isPlaying) {
        audio.pause();
        playBtn.textContent = '▶';
        cover.classList.remove('playing');
        isPlaying = false;
    } else {
        audio.play().catch(console.error);
        playBtn.textContent = '⏸';
        cover.classList.add('playing');
        isPlaying = true;
    }
    savePlayerState();
});

// Barre de progression
audio.addEventListener('timeupdate', () => {
    const progressPercent = (audio.currentTime / audio.duration) * 100;
    progress.style.width = progressPercent + '%';
    currentTimeEl.textContent = formatTime(audio.currentTime);
});
progressContainer.addEventListener('click', e => {
    const width = progressContainer.clientWidth;
    audio.currentTime = (e.offsetX / width) * audio.duration;
});

audio.addEventListener('loadedmetadata', () => {
    durationEl.textContent = formatTime(audio.duration);
});

function formatTime(seconds) {
    if (isNaN(seconds)) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return mins + ':' + (secs < 10 ? '0' : '') + secs;
}

// Pour changer la musique depuis n'importe quelle page, fais :
window.changeDeefyTrack = function({titre, artiste, cover, fichier}) {
    audio.src = fichier;
    trackTitle.textContent = titre;
    trackArtist.textContent = artiste;
    cover.src = cover;
    audio.currentTime = 0;
    isPlaying = true;
    playBtn.textContent = '⏸';
    cover.classList.add('playing');
    audio.play().catch(console.error);
    savePlayerState();
};
</script>
