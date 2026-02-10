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
     * @return array<string, mixed>|null
     */
    public function getUserByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Récupérer un utilisateur par ID
     * @return array<string, mixed>|null
     */
    public function getUserById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id");
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
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = :username AND email != :email");
        $stmt->execute(['username' => $newUsername, 'email' => $email]);
        if ($stmt->fetch()) {
            throw new Exception("Ce nom d'utilisateur est déjà pris.");
        }

        $stmt = $this->pdo->prepare("UPDATE users SET username = :username WHERE email = :email");
        return $stmt->execute(['username' => $newUsername, 'email' => $email]);
    }

    /**
     * Mettre à jour l'avatar
     */
    public function updateAvatar(string $email, string $avatarPath): bool
    {
        $stmt = $this->pdo->prepare("UPDATE users SET avatar = :avatar WHERE email = :email");
        return $stmt->execute(['avatar' => $avatarPath, 'email' => $email]);
    }

    /**
     * Récupérer les playlists
     * @return array<int, array<string, mixed>>
     */
    public function getPlaylists(int $idUser, int $role): array {
        if ($role === 100) {
            // ADMIN → récupère toutes les playlists triées par utilisateur
            $sql = "
            SELECT p.id, p.nom, u.username, p.image
            FROM playlist p
            LEFT JOIN user2playlist up ON p.id = up.id_pl
            LEFT JOIN users u ON up.id_user = u.id
            ORDER BY u.username ASC, p.nom ASC";
            
            $stmt = $this->pdo->query($sql);
        } else {
            // USER → récupère seulement ses playlists
            $sql = "
            SELECT p.id, p.nom, p.image
            FROM playlist p
            INNER JOIN user2playlist up ON p.id = up.id_pl
            WHERE up.id_user = :idUser
            ORDER BY p.nom ASC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['idUser' => $idUser]);
        }

        return $stmt->fetchAll();
    }

    /**
     * Récupérer une playlist par ID
     * @return array<string, mixed>|false
     */
    public function getPlaylistById(int $playlistId, int $idUser, int $role): array|false {
        if ($role === 100) {
            // ADMIN → toutes les playlists
            $sql = "
                SELECT p.id, p.nom, u.username, p.image
                FROM playlist p
                INNER JOIN user2playlist up ON p.id = up.id_pl
                INNER JOIN users u ON up.id_user = u.id
                WHERE p.id = :playlistId
            ";
        
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['playlistId' => $playlistId]);
        } else {
            // USER → seulement ses playlists
            $sql = "
                SELECT p.id, p.nom, p.image, u.username
                FROM playlist p
                INNER JOIN user2playlist up ON p.id = up.id_pl
                INNER JOIN users u ON up.id_user = u.id
                WHERE up.id_user = :idUser AND p.id = :playlistId
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['idUser' => $idUser, 'playlistId' => $playlistId]);
        }

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}