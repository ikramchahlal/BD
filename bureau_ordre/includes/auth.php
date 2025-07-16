<?php
// Protection contre les accès directs
if (!defined('APP_ROOT')) {
    die('Accès interdit');
}

// Vérification session utilisateur
function checkAuth() {
    if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
        header('Location: login.php');
        exit();
    }
    
    if (isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) === 'login.php') {
        header('Location: dashboard.php');
        exit();
    }
}

checkAuth();