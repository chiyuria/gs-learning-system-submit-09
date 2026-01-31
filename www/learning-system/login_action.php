<?php
session_start();

require_once __DIR__ . '/../../inc/ls/functions.php';
require_once __DIR__ . '/../../inc/ls/auth.php';

$loginCode = trim($_POST['login_code'] ?? '');
$password  = $_POST['password_hash'] ?? '';

if ($loginCode === '' || $password === '') {
    redirect('index.php');
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
}

// Password Verify
if (!password_verify($password, $user['password_hash'])) {
    redirect('index.php');
}

login_success($user);

redirect('home.php');
