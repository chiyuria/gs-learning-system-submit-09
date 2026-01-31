<?php
// index.php
declare(strict_types=1);

require_once __DIR__ . '/../../inc/ls/functions.php';

session_start();

$isLoggedIn = isset($_SESSION['user']) && is_array($_SESSION['user']);

if (!$isLoggedIn) {
    require __DIR__ . '/login.php';
    exit;
}

redirect('home.php');
