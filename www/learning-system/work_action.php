<?php
session_start();

require_once __DIR__ . '/../../inc/ls/functions.php';
require_once __DIR__ . '/../../inc/ls/auth.php';

require_student();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('work.php');
}

$userId = (int)($_SESSION['user']['id'] ?? 0);
if ($userId <= 0) {
    redirect('index.php');
}

$themeKey = trim($_POST['theme_key'] ?? 'theme1');
$workText = trim($_POST['work_text'] ?? '');

if ($workText === '') {
    redirect('work.php?error=empty_work#work-form');
}

if (
    $themeKey === '' ||
    mb_strlen($themeKey) > 50 ||
    !preg_match('/^[a-zA-Z0-9_-]+$/', $themeKey)
) {
    redirect('work.php?error=invalid_theme#work-form');
}

$pdo = db_conn();

$sql = "
    INSERT INTO work_responses (user_id, theme_key, work_text)
    VALUES (:user_id, :theme_key, :work_text)
    ON DUPLICATE KEY UPDATE
        work_text = VALUES(work_text)
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
$stmt->bindValue(':theme_key', $themeKey, PDO::PARAM_STR);
$stmt->bindValue(':work_text', $workText, PDO::PARAM_STR);

if (!$stmt->execute()) {
    sql_error($stmt);
}

redirect('work.php?work_saved=1#work-form');
