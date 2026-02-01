<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../inc/ls/functions.php';
require_once __DIR__ . '/../../inc/ls/auth.php';

// POST以外は戻す
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
    exit;
}

$loginCode = trim((string)($_POST['login_code'] ?? ''));
$password  = (string)($_POST['password_hash'] ?? '');

if ($loginCode === '' || $password === '') {
    redirect('index.php');
    exit;
}

$pdo = db_conn();

$sql = "
  SELECT *
  FROM users
  WHERE login_code = :login_code
  LIMIT 1
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':login_code', $loginCode, PDO::PARAM_STR);

if (!$stmt->execute()) {
    sql_error($stmt);
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    redirect('index.php');
    exit;
}

// password_hashがNULLのユーザーは弾く
$hash = (string)($user['password_hash'] ?? '');
if ($hash === '' || !password_verify($password, $hash)) {
    redirect('index.php');
    exit;
}

login_success($user);

redirect('home.php');
exit;
