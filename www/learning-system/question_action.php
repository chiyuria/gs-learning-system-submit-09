<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../inc/ls/functions.php';
require_once __DIR__ . '/../../inc/ls/auth.php';
require_once __DIR__ . '/../../inc/ls/AiClient.php';
require_once __DIR__ . '/../../inc/ls/PromptBuilder.php';

require_student();

$userId = (int)($_SESSION['user']['id'] ?? 0);
if ($userId <= 0) {
  redirect('login.php');
  exit;
}

$pdo = db_conn();

// POST以外は戻す
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  redirect('question.php');
  exit;
}

// theme_key（あとでふやす）
$themeKey = trim((string)($_POST['theme_key'] ?? 'theme1'));
if ($themeKey === '') {
  $themeKey = 'theme1';
}
if (mb_strlen($themeKey) > 50) {
  $themeKey = 'theme1';
}
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $themeKey)) {
  $themeKey = 'theme1';
}

// 回答保存
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
  $text = trim((string)$text);

  if ($text === '') {
    $pdo->rollBack();
    redirect('question.php?error=empty_answer&theme_key=' . urlencode($themeKey));
    exit;
  }

  $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
  $stmt->bindValue(':question_id', (string)$qid, PDO::PARAM_STR);
  $stmt->bindValue(':answer_text', $text, PDO::PARAM_STR);
  $stmt->execute();
}

$pdo->commit();

// AIは1回だけ（テストアカウント公開用に制限しとく）
$sqlCheck = "
  SELECT id
  FROM ai_requests
  WHERE user_id = :user_id
    AND theme_key = :theme_key
    AND status = 'done'
  LIMIT 1
";
$stmt = $pdo->prepare($sqlCheck);
$stmt->execute([
  ':user_id' => $userId,
  ':theme_key' => $themeKey,
]);

$alreadyGenerated = $stmt->fetch(PDO::FETCH_ASSOC);

if ($alreadyGenerated) {
  redirect('work.php?theme_key=' . urlencode($themeKey));
  exit;
}

// AI run
try {
  // QAとる（いまは3問固定。あとで設定化）
  $sqlQa = "
    SELECT
      q.id AS question_id,
      q.title,
      q.description,
      q.display_order,
      r.answer_text
    FROM questions q
    LEFT JOIN responses r
      ON r.question_id = q.id
      AND r.user_id = :user_id
    WHERE q.is_active = 1
    ORDER BY q.display_order ASC
    LIMIT 3
  ";
  $stmt = $pdo->prepare($sqlQa);
  $stmt->execute([':user_id' => $userId]);
  $qaRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // prompt材料（あとでふやす）
  $basicJson = [
    'mentor_role' => 'student_mentor', // あとでつかう
  ];
  $fewshotJson = []; // まだつかわない

  $meta = [
    'user_id' => $userId,
    'theme_key' => $themeKey,
    'prompt_version' => 'v1', // あとで上げる
  ];

  $prompt = PromptBuilder::build($basicJson, $fewshotJson, $qaRows, $meta);

  // request保存
  $sqlInsertRequest = "
    INSERT INTO ai_requests (user_id, theme_key, prompt_version, input_payload_json, status)
    VALUES (:user_id, :theme_key, 'v1', :payload, 'pending')
  ";
  $stmt = $pdo->prepare($sqlInsertRequest);
  $stmt->execute([
    ':user_id' => $userId,
    ':theme_key' => $themeKey,
    ':payload' => $prompt,
  ]);

  $requestId = (int)$pdo->lastInsertId();

  // Gemini
  $aiConfig = require __DIR__ . '/../../config/ls/ai.php';
  $client = new AiClient($aiConfig);
  $result = $client->generate($prompt);

  // output保存
  $sqlInsertOutput = "
    INSERT INTO ai_outputs (request_id, output_md, raw_response_json)
    VALUES (:request_id, :output_md, :raw_json)
  ";
  $stmt = $pdo->prepare($sqlInsertOutput);
  $stmt->execute([
    ':request_id' => $requestId,
    ':output_md' => (string)$result['text'],
    ':raw_json' => json_encode($result['raw'], JSON_UNESCAPED_UNICODE),
  ]);

  // done
  $stmt = $pdo->prepare("UPDATE ai_requests SET status = 'done' WHERE id = :id");
  $stmt->execute([':id' => $requestId]);
} catch (Throwable $e) {
  // AIこけてもワークに進める
  if (isset($requestId) && $requestId > 0) {
    $stmt = $pdo->prepare("
      UPDATE ai_requests
      SET status = 'error',
          error_message = :msg
      WHERE id = :id
    ");
    $stmt->execute([
      ':id' => $requestId,
      ':msg' => $e->getMessage(),
    ]);
  }
}

redirect('work.php?theme_key=' . urlencode($themeKey));
exit;
