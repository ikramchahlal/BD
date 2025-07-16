<?php
require_once 'includes/config.php';

// Détruire la session
$_SESSION = array();
session_destroy();

// Redirection vers login
header('Location: login.php');
exit();