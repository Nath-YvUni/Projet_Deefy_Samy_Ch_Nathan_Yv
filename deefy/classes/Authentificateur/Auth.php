<?php
// classes/Auth/AuthProvider.php

namespace Deefy\Authentificateur;

use Deefy\BDD\Database;
use PDO;
use Exception;

/**
 * Classe pour gérer l'authentification (login/register)
 */
class Auth {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Connexion utilisateur
     * @throws Exception Si les identifiants sont incorrects
     */
    public function signin(string $email, string $passwd): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM user WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($passwd, $user['passwd'])) {
            throw new Exception("Identifiants incorrects.");
        }

        return $user;
    }

    /**
     * Inscription utilisateur
     * @throws Exception Si validation échoue ou si l'email/username existe déjà
     */
    public function register(string $email, string $username, string $passwd): void
    {
        if (strlen($passwd) < 10) {
            throw new Exception("Le mot de passe doit contenir au moins 10 caractères.");
        }

        // Vérifier si l'email ou username existe déjà
        $stmt = $this->pdo->prepare("SELECT * FROM user WHERE email = :email OR username = :username");
        $stmt->execute(['email' => $email, 'username' => $username]);
        if ($stmt->fetch()) {
            throw new Exception("Cet email ou nom d'utilisateur est déjà utilisé.");
        }

        // Créer l'utilisateur
        $hash = password_hash($passwd, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare(
            "INSERT INTO user (email, username, passwd, avatar, role) 
             VALUES (:email, :username, :passwd, 'ressources/images/defaut-avatar.png', 1)"
        );
        $stmt->execute([
            'email' => $email, 
            'username' => $username, 
            'passwd' => $hash
        ]);
    }
}