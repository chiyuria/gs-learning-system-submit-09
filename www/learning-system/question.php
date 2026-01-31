<?php
// question.php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../inc/ls/functions.php';
require_once __DIR__ . '/../../inc/ls/auth.php';

require_student();

$userId = (int)($_SESSION['user']['id'] ?? 0);
$pdo = db_conn();

$themeKey = (string)($_GET['theme_key'] ?? 'theme1');
if ($themeKey === '') {
    $themeKey = 'theme1';
}

// Fetch 3 active questions
$sql = "
    SELECT id, title, description
    FROM questions
    WHERE is_active = 1
    ORDER BY display_order ASC
    LIMIT 3
";
$stmt = $pdo->prepare($sql);
if (!$stmt->execute()) {
    sql_error($stmt);
}
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($questions) < 3) {
    exit('Error: need at least 3 active questions.');
}

$questionIds = [];
foreach ($questions as $q) {
    $questionIds[] = (int)$q['id'];
}

// Load existing answers (fixed 3 ids)
$answeredMap = [];
$sql = "
    SELECT question_id, answer_text
    FROM responses
    WHERE user_id = :user_id
      AND question_id IN (:q1, :q2, :q3)
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
$stmt->bindValue(':q1', $questionIds[0], PDO::PARAM_INT);
$stmt->bindValue(':q2', $questionIds[1], PDO::PARAM_INT);
$stmt->bindValue(':q3', $questionIds[2], PDO::PARAM_INT);

if (!$stmt->execute()) {
    sql_error($stmt);
}

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    $qid = (string)$row['question_id'];
    $answeredMap[$qid] = (string)$row['answer_text'];
}

$answeredCount = 0;
foreach ($questionIds as $qidInt) {
    $qid = (string)$qidInt;
    $text = trim($answeredMap[$qid] ?? '');
    if ($text !== '') {
        $answeredCount++;
    }
}

$allAnswered = ($answeredCount === 3);
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Learning System | Questions</title>

    <link rel="stylesheet" href="./assets/css/tokens.css">
    <link rel="stylesheet" href="./assets/css/reset.css">
    <link rel="stylesheet" href="./assets/css/typography.css">
    <link rel="stylesheet" href="./assets/css/layout.css">

    <link rel="stylesheet" href="./assets/css/components/buttons.css">
    <link rel="stylesheet" href="./assets/css/components/forms.css">
    <link rel="stylesheet" href="./assets/css/components/modal.css">
    <link rel="stylesheet" href="./assets/css/components/tags.css">
    <link rel="stylesheet" href="./assets/css/components/toast.css">
    <link rel="stylesheet" href="./assets/css/components/badges.css">

    <link rel="stylesheet" href="./assets/css/scroll.css">
    <link rel="stylesheet" href="./assets/css/utilities.css">
    <link rel="stylesheet" href="./assets/css/pages/mvp.css">
</head>

<body>
<header class="app-header">
    <div class="header-title">Learning System</div>

    <div style="margin-left:auto; display:flex; gap:.8rem; align-items:center;">
        <a href="home.php" class="text-muted" style="font-size:.9rem;">戻る</a>
        <a href="logout_action.php" class="text-muted" style="font-size:.9rem;">ログアウト</a>
    </div>
</header>

<main class="wrap">
    <section class="section">
        <h2 class="col-title">3つの問い</h2>

        <form method="post" action="question_action.php">
            <input type="hidden" name="action" value="save_answers">
            <input type="hidden" name="theme_key" value="<?= h($themeKey) ?>">

            <?php foreach ($questions as $q): ?>
                <?php
                $qid = (string)$q['id'];
                $defaultText = '';
                if (array_key_exists($qid, $answeredMap)) {
                    $defaultText = (string)$answeredMap[$qid];
                }
                ?>
                <div style="margin-top:1.2rem;">
                    <div class="q-title"><?= h($qid) ?>. <?= h((string)$q['title']) ?></div>
                    <div class="q-desc"><?= h((string)$q['description']) ?></div>

                    <!-- Keep label for accessibility -->
                    <label class="sr-only" for="ans_<?= h($qid) ?>">Answer</label>
                    <textarea
                        id="ans_<?= h($qid) ?>"
                        name="answers[<?= h($qid) ?>]"
                        class="input-base input-area"
                        placeholder="ここに書いてください。"
                        required><?= h($defaultText) ?></textarea>
                </div>
            <?php endforeach; ?>

            <div class="btn-row" style="margin-top:1rem;">
                <button type="submit" class="btn btn-primary">3問を保存</button>
            </div>

            <?php if (isset($_GET['saved'])): ?>
                <div class="hint" style="margin-top:.8rem;">保存しました。</div>
            <?php endif; ?>
        </form>
    </section>

    <?php if ($allAnswered): ?>
        <section class="section">
            <h2 class="col-title">次</h2>
            <p class="text-muted" style="margin-top:.6rem;">
                3問回答済みです。次はワークへ進めます。
            </p>
            <div class="btn-row" style="margin-top:.8rem;">
                <a class="btn btn-primary" href="work.php?theme_key=<?= urlencode($themeKey) ?>">ワークへ進む</a>
            </div>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
