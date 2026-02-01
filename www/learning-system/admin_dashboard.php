<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../inc/ls/functions.php';
require_once __DIR__ . '/../../inc/ls/auth.php';

require_teacher();

$pdo = db_conn();

// 3問（いま固定）
$sql = "
  SELECT id, title, display_order
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

$q1 = (string)$questions[0]['id'];
$q2 = (string)$questions[1]['id'];
$q3 = (string)$questions[2]['id'];

$themeKey = 'theme1'; // あとでふやしてく

$sql = "
  SELECT
    u.id,
    u.name,
    u.login_code,

    MAX(CASE WHEN r.question_id = :q1 AND TRIM(r.answer_text) <> '' THEN 1 ELSE 0 END) AS q1_done,
    MAX(CASE WHEN r.question_id = :q2 AND TRIM(r.answer_text) <> '' THEN 1 ELSE 0 END) AS q2_done,
    MAX(CASE WHEN r.question_id = :q3 AND TRIM(r.answer_text) <> '' THEN 1 ELSE 0 END) AS q3_done,

    MAX(CASE WHEN wr.theme_key = :theme_key AND TRIM(wr.work_text) <> '' THEN 1 ELSE 0 END) AS work_done

  FROM users u
  LEFT JOIN responses r
    ON r.user_id = u.id
   AND r.question_id IN (:q1, :q2, :q3)
  LEFT JOIN work_responses wr
    ON wr.user_id = u.id
   AND wr.theme_key = :theme_key
  WHERE u.role = 'student'
  GROUP BY u.id, u.name, u.login_code
  ORDER BY u.id ASC
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':q1', $q1, PDO::PARAM_STR);
$stmt->bindValue(':q2', $q2, PDO::PARAM_STR);
$stmt->bindValue(':q3', $q3, PDO::PARAM_STR);
$stmt->bindValue(':theme_key', $themeKey, PDO::PARAM_STR);

if (!$stmt->execute()) {
    sql_error($stmt);
}
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

function yesno(bool $done): string
{
    if ($done) {
        return '✅';
    }
    return '—';
}
?>
<!doctype html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Teacher Dashboard</title>

    <link rel="stylesheet" href="./assets/css/tokens.css">
    <link rel="stylesheet" href="./assets/css/reset.css">
    <link rel="stylesheet" href="./assets/css/typography.css">
    <link rel="stylesheet" href="./assets/css/layout.css">

    <link rel="stylesheet" href="./assets/css/components/buttons.css">
    <link rel="stylesheet" href="./assets/css/components/badges.css">
    <link rel="stylesheet" href="./assets/css/components/forms.css">
    <link rel="stylesheet" href="./assets/css/components/table.css">

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
            <h2 class="col-title">取り組み状況ダッシュボード</h2>
            <div class="hint">学生ごとの設問（3つ）とワークの取り組み状況を一覧で確認できます</div>
        </section>

        <section class="section">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>学生</th>
                            <th>User ID</th>
                            <th><?= h($q1) ?></th>
                            <th><?= h($q2) ?></th>
                            <th><?= h($q3) ?></th>
                            <th>設問</th>
                            <th>Work</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="8" class="text-muted">学生がいません</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $r): ?>
                                <?php
                                $q1done = (int)($r['q1_done'] ?? 0) === 1;
                                $q2done = (int)($r['q2_done'] ?? 0) === 1;
                                $q3done = (int)($r['q3_done'] ?? 0) === 1;

                                $cnt = 0;
                                if ($q1done) {
                                    $cnt++;
                                }
                                if ($q2done) {
                                    $cnt++;
                                }
                                if ($q3done) {
                                    $cnt++;
                                }

                                $workDone = (int)($r['work_done'] ?? 0) === 1;

                                $name = trim((string)($r['name'] ?? ''));
                                ?>

                                <tr>
                                    <td><?= h((string)($r['id'] ?? '')) ?></td>
                                    <td>
                                        <?php if ($name !== ''): ?>
                                            <?= h($name) ?>
                                        <?php else: ?>
                                            <span class="text-muted">（未設定）</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= h((string)($r['login_code'] ?? '')) ?></td>
                                    <td><?= yesno($q1done) ?></td>
                                    <td><?= yesno($q2done) ?></td>
                                    <td><?= yesno($q3done) ?></td>
                                    <td><?= $cnt ?>/3</td>
                                    <td><?= yesno($workDone) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>

</html>