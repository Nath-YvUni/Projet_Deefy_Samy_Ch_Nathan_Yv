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

    function getPlaylists(int $idUser, int $role) {
    if ($role === 100) {
        // ADMIN → récupère toutes les playlists triées par utilisateur
        $sql = "
        SELECT p.id, p.nom, u.username
        FROM playlist p
        LEFT JOIN user2playlist up ON p.id = up.id_pl
        LEFT JOIN user u ON up.id_user = u.id
        ORDER BY u.username ASC, p.nom ASC";
        
        $stmt = $this->pdo->query($sql);
    } else {
        // USER → récupère seulement ses playlists
        $sql = "
        SELECT p.id, p.nom
        FROM playlist p
        INNER JOIN user2playlist up ON p.id = up.id_pl
        WHERE up.id_user = :idUser
        ORDER BY p.nom ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['idUser' => $idUser]);
    }

    return $stmt->fetchAll();
}

}