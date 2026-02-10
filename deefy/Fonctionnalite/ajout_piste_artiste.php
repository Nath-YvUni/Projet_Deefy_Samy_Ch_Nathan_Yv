<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
require_once '../classes/init.php';

// --------------------
// Dossiers
// --------------------
$tmpDir   = __DIR__ . '/../ressources/audio/tmp/';     // stockage temporaire des chunks
$finalDir = __DIR__ . '/../ressources/audio/';         // stockage final des MP3
$coverDir = __DIR__ . '/../ressources/images/piste/';  // stockage des covers

// --------------------
// TRAITEMENT DES CHUNKS
// --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['chunk'])) {
    header('Content-Type: application/json');

    // Vérifier permissions
    if (!is_writable($tmpDir)) {
        echo json_encode(['error' => 'Permissions insuffisantes. Veuillez rendre writable ressources/audio/tmp/']);
        exit;
    }

    $filename = $_POST['filename'] ?? 'file.mp3';
    $index = intval($_POST['index'] ?? 0);
    $cleanFilename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $filename);

    // Chemin temporaire du chunk
    $chunkPath = $tmpDir . $cleanFilename . ".part$index";

    if (move_uploaded_file($_FILES['chunk']['tmp_name'], $chunkPath)) {
        echo json_encode(['success'=>true, 'chunk'=>$index]);
    } else {
        echo json_encode([
            'error'=>'Impossible de sauvegarder le chunk. Vérifiez les permissions du dossier tmp.', 
            'chunk'=>$index
        ]);
    }
    exit;
}

// --------------------
// TRAITEMENT FINAL
// --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filename']) && !isset($_FILES['chunk'])) {
    header('Content-Type: application/json');

    try {
        // Vérifier permissions des dossiers finaux
        if (!is_writable($finalDir) || !is_writable($coverDir)) {
            throw new Exception("Permissions insuffisantes. Rendre writable ressources/audio/ et ressources/images/piste/");
        }

        // Récupération du titre entré par l'utilisateur
        $titre = trim($_POST['titre'] ?? 'SansTitre');

        // Nettoyage pour que le nom du fichier final soit correct
        $basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $titre);

        $filename = $_POST['filename'];
        $cleanFilename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $filename);

        // Récupération des chunks
        $searchPattern = $tmpDir . $cleanFilename . '.part*';
        $chunks = glob($searchPattern);

        if (!$chunks || count($chunks) === 0) {
            throw new Exception("Chunks manquants");
        }

        // Chemin final du MP3
        $finalPath = $finalDir . $basename . '.mp3';
        $audio_path = 'ressources/audio/' . $basename . '.mp3';

        // Concaténation des chunks
        natsort($chunks); // ordre naturel
        $out = fopen($finalPath, 'wb');
        if (!$out) throw new Exception("Impossible de créer le fichier final");

        foreach ($chunks as $chunkFile) {
            $in = fopen($chunkFile, 'rb');
            if (!$in) {
                fclose($out);
                throw new Exception("Impossible d'ouvrir un chunk");
            }
            stream_copy_to_stream($in, $out);
            fclose($in);
            unlink($chunkFile); // suppression du chunk après copie
        }
        fclose($out);

        // Gestion de la cover
        $cover_path = 'ressources/images/piste/default_cover.png';
        if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
            $coverInfo = pathinfo($_FILES['cover']['name']);
            $coverExt = strtolower($coverInfo['extension'] ?? 'jpg');
            $coverFilename = $basename . '.' . $coverExt; // même nom de base que le MP3
            $coverDest = $coverDir . $coverFilename;

            if (move_uploaded_file($_FILES['cover']['tmp_name'], $coverDest)) {
                $cover_path = 'ressources/images/piste/' . $coverFilename;
            }
        }

        // Autres infos
        $genre = trim($_POST['genre'] ?? '');
        $artiste_album = trim($_POST['artiste_album'] ?? '');
        $titre_album = trim($_POST['titre_album'] ?? '');
        $annee_album = trim($_POST['annee_album'] ?? '');

        // Insertion en BDD
        $stmt = $pdo->prepare("INSERT INTO track (titre, genre, filename, artiste_album, titre_album, annee_album, cover)
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([$titre, $genre, $audio_path, $artiste_album, $titre_album, $annee_album, $cover_path]);

        if (!$result) throw new Exception("Erreur insertion BDD");

        echo json_encode(['success'=>true]);

    } catch (Exception $e) {
        echo json_encode(['error'=>$e->getMessage()]);
    }
    exit;
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

    <form id="uploadForm" enctype="multipart/form-data">
        <label for="titre">Titre de la piste :</label>
        <input type="text" id="titre" name="titre" required>

        <label for="genre">Genre :</label>
        <input type="text" id="genre" name="genre">

        <label for="audio">Fichier audio (.mp3) :</label>
        <input type="file" id="audio" accept=".mp3" required>

        <label for="artiste_album">Artiste album :</label>
        <input type="text" id="artiste_album" name="artiste_album">

        <label for="titre_album">Titre album :</label>
        <input type="text" id="titre_album" name="titre_album">

        <label for="annee_album">Année album :</label>
        <input type="number" id="annee_album" name="annee_album" min="1900" max="2099">

        <label for="cover">Cover :</label>
        <input type="file" id="cover" name="cover" accept=".jpg,.jpeg,.png,.gif,.webp">

        <button type="submit">Ajouter la piste</button>
        <button type="button" class="btn-secondary" onclick="window.location.href='../index.php'">Annuler</button>
    </form>

    <div id="progress" style="margin-top:10px;"></div>
</div>

<script>
const CHUNK_SIZE = 1024*1024;

document.getElementById('uploadForm').addEventListener('submit', async e => {
    e.preventDefault();

    try {
        const file = document.getElementById('audio').files[0];
        if (!file) return alert("Sélectionnez un fichier audio.");
        
        const filename = file.name;
        const totalChunks = Math.ceil(file.size / CHUNK_SIZE);

        for (let i = 0; i < totalChunks; i++) {
            const start = i * CHUNK_SIZE;
            const end = Math.min(file.size, start + CHUNK_SIZE);
            const chunk = file.slice(start, end);

            const formData = new FormData();
            formData.append('chunk', chunk);
            formData.append('filename', filename);
            formData.append('index', i);

            const res = await fetch('', { method:'POST', body: formData });
            const text = await res.text();
            
            if (!res.ok) {
                console.error('Erreur serveur chunk', i, ':', text);
                return alert("Erreur serveur chunk " + i);
            }
            
            const chunkResult = JSON.parse(text);
            
            if (chunkResult.error) {
                console.error('Erreur chunk:', chunkResult);
                if (chunkResult.error.includes('setup.php')) {
                    alert("Configuration nécessaire. Redirection vers setup.php...");
                    window.location.href = '../setup.php';
                    return;
                }
                return alert("Erreur: " + chunkResult.error);
            }

            document.getElementById('progress').innerText = `Upload ${i+1}/${totalChunks} chunks...`;
        }

        const infoForm = new FormData();
        infoForm.append('titre', document.getElementById('titre').value);
        infoForm.append('genre', document.getElementById('genre').value);
        infoForm.append('artiste_album', document.getElementById('artiste_album').value);
        infoForm.append('titre_album', document.getElementById('titre_album').value);
        infoForm.append('annee_album', document.getElementById('annee_album').value);
        const coverFile = document.getElementById('cover').files[0];
        if (coverFile) infoForm.append('cover', coverFile);
        infoForm.append('filename', filename);

        const res2 = await fetch('', { method:'POST', body: infoForm });
        const text2 = await res2.text();
        
        if (!res2.ok) {
            console.error('Erreur serveur final:', text2);
            return alert("Erreur serveur lors de la finalisation");
        }
        
        const json = JSON.parse(text2);
        
        if (json.success) {
            alert("Upload terminé !");
            window.location.href = '../index.php';
        } else {
            if (json.error && json.error.includes('setup.php')) {
                alert("Configuration nécessaire. Redirection vers setup.php...");
                window.location.href = '../setup.php';
                return;
            }
            alert("Erreur : " + (json.error || 'Erreur inconnue'));
        }
    } catch (error) {
        console.error('Exception:', error);
        alert('Erreur : ' + error.message);
    }
});
</script>
</body>
</html>