<?php

namespace Deefy\Utilisateur;

use Deefy\BDD\Database;
use PDO;
use Exception;

/**
 * Classe pour gérer le profil utilisateur (avatar, username, playlists)
 */
class UtilManage
{
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Récupérer les informations complètes d'un utilisateur
     */
    public function getUserByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM user WHERE email = :email");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Récupérer un utilisateur par ID
     */
    public function getUserById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM user WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Mettre à jour le nom d'utilisateur
     * @throws Exception Si le username est trop court ou déjà pris
     */
    public function updateUsername(string $email, string $newUsername): bool
    {
        if (strlen($newUsername) < 3) {
            throw new Exception("Le nom d'utilisateur doit contenir au moins 3 caractères.");
        }

        // Vérifier si le username est déjà pris
        $stmt = $this->pdo->prepare("SELECT * FROM user WHERE username = :username AND email != :email");
        $stmt->execute(['username' => $newUsername, 'email' => $email]);
        if ($stmt->fetch()) {
            throw new Exception("Ce nom d'utilisateur est déjà pris.");
        }

        $stmt = $this->pdo->prepare("UPDATE user SET username = :username WHERE email = :email");
        return $stmt->execute(['username' => $newUsername, 'email' => $email]);
    }

    /**
     * Mettre à jour l'avatar
     */
    public function updateAvatar(string $email, string $avatarPath): bool
    {
        $stmt = $this->pdo->prepare("UPDATE user SET avatar = :avatar WHERE email = :email");
        return $stmt->execute(['avatar' => $avatarPath, 'email' => $email]);
    }
}