<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../inc/ls/functions.php';

// ログインしてなければログイン画面
if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    require __DIR__ . '/login.php';
    exit;
}

redirect('home.php');
exit;
