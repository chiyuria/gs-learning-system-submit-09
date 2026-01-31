<?php
session_start();

require_once __DIR__ . '/../../inc/ls/functions.php';
require_once __DIR__ . '/../../inc/ls/auth.php';

require_student();

$userId = (int)$_SESSION['user']['id'];
$pdo = db_conn();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  redirect('question.php');
}

$answers = $_POST['answers'] ?? [];

$pdo->beginTransaction();

$sql = "
  INSERT INTO responses (user_id, question_id, answer_text)
  VALUES (:user_id, :question_id, :answer_text)
  ON DUPLICATE KEY UPDATE
    answer_text = VALUES(answer_text),
    updated_at = CURRENT_TIMESTAMP
";
$stmt = $pdo->prepare($sql);

foreach ($answers as $qid => $text) {
  $text = trim($text);
  if ($text === '') {
    $pdo->rollBack();
    redirect('question.php?error=empty_answer');
  }

  $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
  $stmt->bindValue(':question_id', $qid, PDO::PARAM_STR);
  $stmt->bindValue(':answer_text', $text, PDO::PARAM_STR);
  $stmt->execute();
}

$pdo->commit();

redirect('work.php');
