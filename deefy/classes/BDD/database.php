<?php
// classes/BDD/Database.php

namespace Deefy\BDD;

use PDO;
use PDOException;

class Database 
{
    private static ?PDO $instance = null;
    private static string $configFile = '';

    /**
     * Définit le fichier de configuration .ini
     */
    public static function setConfigFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new \Exception("Fichier de configuration introuvable : $filePath");
        }
        self::$configFile = $filePath;
    }

    /**
     * Retourne l'instance PDO
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            if (empty(self::$configFile)) {
                throw new \Exception("Aucun fichier de configuration défini.");
            }

            $config = parse_ini_file(self::$configFile, true);

            if ($config === false) {
                throw new \Exception("Impossible de lire le fichier de configuration : " . self::$configFile);
            }

            $dsn = $config['dsn']['dsn'] ?? '';
            $username = $config['username']['username'] ?? '';
            $password = $config['password']['password'] ?? '';

            try {
                self::$instance = new PDO($dsn, $username, $password);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                throw new \Exception("Erreur de connexion à la base : " . $e->getMessage());
            }
        }

        return self::$instance;
    }
}
