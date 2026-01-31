<?php
// work.php
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
    SELECT id, title, description, display_order
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

// Load existing answers for current active top 3 questions
$answeredMap = [];

$sqlAnswered = "
    SELECT r.question_id, r.answer_text
    FROM responses r
    INNER JOIN (
        SELECT id
        FROM questions
        WHERE is_active = 1
        ORDER BY display_order ASC
        LIMIT 3
    ) q ON r.question_id = q.id
    WHERE r.user_id = :user_id
";
$stmt = $pdo->prepare($sqlAnswered);
$stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

if (!$stmt->execute()) {
    sql_error($stmt);
}

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    $qid = (string)$row['question_id'];
    $answeredMap[$qid] = (string)$row['answer_text'];
}

// Count answered for current active top 3 questions
$sqlCount = "
    SELECT COUNT(*) AS cnt
    FROM responses r
    INNER JOIN (
        SELECT id
        FROM questions
        WHERE is_active = 1
        ORDER BY display_order ASC
        LIMIT 3
    ) q ON r.question_id = q.id
    WHERE r.user_id = :user_id
      AND TRIM(r.answer_text) <> ''
";
$stmt = $pdo->prepare($sqlCount);
$stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

if (!$stmt->execute()) {
    sql_error($stmt);
}

$answeredCount = (int)$stmt->fetchColumn();

$allAnswered = ($answeredCount === 3);
$canShowWorkTemplate = $allAnswered;


// Load latest active work template
$workTemplate = [
    'theme_key' => $themeKey,
    'title' => '',
    'body' => '',
];

if ($canShowWorkTemplate) {
    $sql = "
        SELECT theme_key, title, body
        FROM work_templates
        WHERE theme_key = :theme_key
          AND is_active = 1
        ORDER BY id DESC
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':theme_key', $themeKey, PDO::PARAM_STR);

    if (!$stmt->execute()) {
        sql_error($stmt);
    }

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $workTemplate['theme_key'] = (string)$row['theme_key'];
        $workTemplate['title'] = (string)$row['title'];
        $workTemplate['body'] = (string)$row['body'];
    } else {
        $workTemplate['title'] = 'Work (Placeholder)';
        $workTemplate['body'] = "Template not found.\nPlease insert a row into work_templates.";
    }
}

// Load saved work response
$workSavedText = '';
$hasWorkSaved = false;

if ($canShowWorkTemplate) {
    $sql = "
        SELECT work_text
        FROM work_responses
        WHERE user_id = :user_id
          AND theme_key = :theme_key
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':theme_key', $themeKey, PDO::PARAM_STR);

    if (!$stmt->execute()) {
        sql_error($stmt);
    }

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $hasWorkSaved = true;
        $workSavedText = (string)$row['work_text'];
    }
}
?>
<!doctype html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Learning System | Work</title>

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
            <a href="home.php" class="text-muted" style="font-size:.9rem;">ホーム</a>
            <a href="question.php?theme_key=<?= urlencode($themeKey) ?>" class="text-muted" style="font-size:.9rem;">戻る</a>
            <a href="logout_action.php" class="text-muted" style="font-size:.9rem;">ログアウト</a>
        </div>
    </header>

    <main class="wrap">
        <?php if (!$allAnswered): ?>
            <section class="section">
                <p class="text-muted" style="margin:0;">
                    まだ3つの問いが揃っていません。先に回答を完了してください。
                </p>
                <div style="margin-top:.8rem;">
                    <a href="question.php?theme_key=<?= urlencode($themeKey) ?>" class="btn btn-primary">回答ページへ戻る</a>
                </div>
            </section>
        <?php endif; ?>

        <section class="section">
            <h2 class="col-title">あなたの回答</h2>

            <?php foreach ($questions as $index => $q): ?>
                <?php
                $qid = (string)$q['id'];
                $answerText = (string)($answeredMap[$qid] ?? '');
                $answerHtml = '<span class="text-muted">未回答</span>';

                if (trim($answerText) !== '') {
                    $answerHtml = nl2br(h($answerText));
                }
                ?>
                <div class="answer-review-block" style="margin-top:1.2rem;">
                    <div class="q-title">Q<?= (int)($index + 1) ?>. <?= h((string)$q['title']) ?></div>
                    <div class="answer-review" style="margin-top:1rem;">
                        <?= $answerHtml ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="hint" style="margin-top:1rem;">
                フィードバックを参考にしてワークに取り組んでみよう。
            </div>

            <div style="margin-top:.6rem;">
                <a href="question.php?theme_key=<?= urlencode($themeKey) ?>" class="text-muted" style="font-size:.9rem;">回答を修正する</a>
            </div>
        </section>

        <section class="section" aria-label="feedback-sample">
            <h2 class="col-title">フィードバック（サンプル）</h2>

            <p class="text-muted" style="margin:.6rem 0 0;">
                ※ここは表示イメージ用の仮スペースです。
            </p>

            <div style="margin-top:1rem;">
                <div class="q-title">コメント</div>
                <div class="q-desc feedback-body">
                    全体として、現在の考えを具体的な言葉で整理できています。
                    感情や印象だけで終わらせず、内容として書き出せている点は評価できます。

                    Q1について
                    「理解できたこと」を自分の言葉で説明できている点は良い状態です。
                    次のステップとして、
                    ・なぜ理解できたのか
                    ・どの行動が理解につながったのか
                    を分解して考えると、再現性が高まります。

                    Q2について
                    伸ばしたいスキルを明確に言語化できています。
                    今後は、そのスキルを
                    ・どの場面で使いたいか
                    ・どのレベルまでできるようになりたいか
                    まで具体化できると、行動に落とし込みやすくなります。

                    Q3について
                    実行可能な行動を小さく設定できている点は適切です。
                    まずは計画どおりに実行し、結果を振り返ることを優先してください。
                    完璧さよりも、実行と修正を繰り返すことが重要です。

                    まとめ
                    現時点で考えが整理しきれていなくても問題ありません。
                    今回の回答は、状況を把握し次の行動を決めるための十分な材料になっています。
                    この内容をもとに、次の一手を実行してみましょう。
                </div>
            </div>
        </section>

        <?php if ($canShowWorkTemplate): ?>
            <section class="section" id="work-template">
                <h2 class="col-title">ワーク</h2>

                <div class="q-title" style="margin-top:1rem;">
                    <?= h((string)$workTemplate['title']) ?>
                </div>

                <div class="q-desc work-template-body">
                    <?= h((string)$workTemplate['body']) ?>
                </div>

                <div style="margin-top:1.2rem;" id="work-form">
                    <?php if (isset($_GET['error']) && $_GET['error'] === 'empty_work'): ?>
                        <p class="text-muted" style="margin:0;">ワークが未入力です。入力してください。</p>
                    <?php endif; ?>

                    <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_theme'): ?>
                        <p class="text-muted" style="margin:0;">内部エラー（theme_key不正）です。管理者に連絡してください。</p>
                    <?php endif; ?>

                    <form method="post" action="work_action.php" style="margin-top:.8rem;">
                        <input type="hidden" name="theme_key" value="<?= h((string)$workTemplate['theme_key']) ?>">

                        <label class="sr-only" for="work_text">Work input</label>
                        <textarea
                            id="work_text"
                            name="work_text"
                            class="input-base input-area"
                            placeholder="ここにまとめてください。"
                            required><?= h($workSavedText) ?></textarea>

                        <div class="btn-row">
                            <button type="submit" class="btn btn-primary">ワークを保存</button>
                        </div>

                        <?php if (isset($_GET['work_saved'])): ?>
                            <div class="hint" style="margin-top:.8rem;">ワークを保存しました。</div>
                        <?php endif; ?>
                    </form>

                    <?php if ($hasWorkSaved): ?>
                        <div class="hint" style="margin-top:.8rem;">
                            ※保存済みです。上のフォームでいつでも上書きできます。
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>
</body>

</html>