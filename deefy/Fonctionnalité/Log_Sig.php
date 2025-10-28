<?php
session_start();

// Inclure l'init pour récupérer $pdo
require_once __DIR__ . '/../classes/init.php';
require_once __DIR__ . '/../classes/Authentificateur/Auth.php';

use Deefy\Authentificateur\Auth;

$auth = new Auth($pdo);

// Ici tu peux appeler $auth->signin() ou $auth->register()


// --- Déconnexion ---
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['user']);
    session_regenerate_id(true);
    header('Location: ../index.php');
    exit;
}

// --- Si déjà connecté, redirige vers index ---
if (isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}

// --- Traitement des formulaires ---
$flash = ['type' => null, 'msg' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $email = strtolower(trim($_POST['email'] ?? ''));
    $username = trim($_POST['username'] ?? '');
    $passwd = $_POST['passwd'] ?? '';

    try {
        if ($action === 'register') {
            $auth->register($email, $username, $passwd);
            $flash = ['type' => 'success', 'msg' => "Inscription réussie ! Vous pouvez maintenant vous connecter."];
        } 
        elseif ($action === 'login') {
            $user = $auth->signin($email, $passwd);
            
            // Stocker les infos dans la session
            $_SESSION['user'] = [
                'id'       => $user['id'],
                'email'    => $user['email'],
                'username' => $user['username'],
                'avatar'   => $user['avatar'],
                'role'     => $user['role']
            ];
            
            header('Location: ../index.php');
            exit;
        } 
        else {
            throw new Exception("Action invalide.");
        }
    } catch (Exception $e) {
        $flash = ['type' => 'error', 'msg' => $e->getMessage()];
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <title>Connexion / Inscription — Deefy</title>
  <link rel="stylesheet" href="../ressources/css/LogSigStyle.css?v=1">
</head>
<body>
<div class="card">

  <?php if ($flash['type']): ?>
    <div class="flash <?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
  <?php endif; ?>

  <h1>Inscription</h1>
  <p class="lead">Pas encore de compte ? Inscrivez-vous !</p>
  <form method="post">
    <input type="hidden" name="action" value="register">
    <div class="field">
      <label>Nom d'utilisateur</label>
      <input type="text" name="username" required>
    </div>
    <div class="field">
      <label>Email</label>
      <input type="email" name="email" required>
    </div>
    <div class="field">
      <label>Mot de passe (10 caractères minimum)</label>
      <input type="password" name="passwd" required>
    </div>
    <div class="actions">
      <button class="btn" type="submit">S'inscrire</button>
    </div>
  </form>

  <hr>

  <h1>Connexion</h1>
  <p class="lead">Entrez votre email et mot de passe pour vous connecter.</p>
  <form method="post">
    <input type="hidden" name="action" value="login">
    <div class="field">
      <label>Email</label>
      <input type="email" name="email" required>
    </div>
    <div class="field">
      <label>Mot de passe</label>
      <input type="password" name="passwd" required>
    </div>
    <div class="actions">
      <button class="btn" type="submit">Se connecter</button>
    </div>
  </form>

</div>
</body>
</html>