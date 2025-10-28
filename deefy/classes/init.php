<?php
// classes/init.php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/BDD/Database.php';

use Deefy\BDD\Database;

// Initialisation de la base avec le fichier .ini
try {
    Database::setConfigFile(__DIR__ . '/../ressources/acces/db.ini');
    $pdo = Database::getConnection();
} catch (\Exception $e) {
    // Stop le script si la DB n'est pas accessible
    die("Erreur lors de l'initialisation de la base de données : " . $e->getMessage());
}
