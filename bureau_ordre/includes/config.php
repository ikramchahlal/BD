<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration de la base de données (paramètres par défaut WAMP)
define('DB_HOST', 'localhost');
define('DB_NAME', 'bureau_ordre');
define('DB_USER', 'root');
define('DB_PASS', '');


// Configuration de l'application
define('APP_NAME', 'Bureau d\'Ordre');
define('APP_ROOT', dirname(dirname(__FILE__)));
define('URL_ROOT', 'http://localhost/bureau_ordre');
define('UPLOAD_DIR', 'uploads');


// Démarrer la session
session_start();

// Inclure les classes et fonctions
require_once APP_ROOT . '/classes/Database.php';
require_once APP_ROOT . '/classes/User.php';
require_once APP_ROOT . '/classes/Document.php';
require_once APP_ROOT . '/functions/functions.php';

// Initialiser la base de données
try {
    $db = new Database();
    $user = new User($db);
    $document = new Document($db);
} catch(PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Vérifier si le dossier upload existe
if(!is_dir(UPLOAD_DIR)) {
    if(!mkdir(UPLOAD_DIR, 0777, true)) {
        die("Erreur: Impossible de créer le dossier 'uploads'");
    }
}