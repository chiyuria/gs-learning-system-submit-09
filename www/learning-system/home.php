<?php
// home.php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../inc/ls/functions.php';
require_once __DIR__ . '/../../inc/ls/auth.php';

require_login();

$user = $_SESSION['user'] ?? null;
$role = is_array($user) ? (string)($user['role'] ?? 'guest') : 'guest';

$pdo = db_conn();

$themes = [];
if ($role === 'student') {
    // Fetch latest active template per theme_key
    $sql = "
        SELECT wt.theme_key, wt.title, wt.body
        FROM work_templates wt
        INNER JOIN (
            SELECT theme_key, MAX(id) AS max_id
            FROM work_templates
            WHERE is_active = 1
            GROUP BY theme_key
        ) latest
            ON wt.theme_key = latest.theme_key
            AND wt.id = latest.max_id
        ORDER BY wt.theme_key ASC
    ";

    $stmt = $pdo->prepare($sql);
    if (!$stmt->execute()) {
        sql_error($stmt);
    }

    $themes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Learning System | Home</title>

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
            <a href="logout_action.php" class="text-muted" style="font-size:.9rem;">ログアウト</a>
        </div>
    </header>

    <main class="wrap">
        <?php if ($role === 'student'): ?>
            <section class="section">
                <h2 class="col-title">ワーク一覧</h2>
                <div class="hint" style="margin-top:.6rem;">
                    テーマを選ぶと、3つの問いに進んで取り組みが開始されます。
                </div>

                <?php if (empty($themes)): ?>
                    <p class="text-muted" style="margin-top:1rem;">取り組み可能なワークがありません。</p>
                <?php else: ?>
                    <div class="table-wrap" style="margin-top:1rem;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width:10%;">No.</th>
                                    <th>タイトル</th>
                                    <th style="width:15%;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($themes as $theme): ?>
                                    <tr>
                                        <td>
                                            <span class="badge badge-outline badge-sm">
                                                <?= h((string)$theme['theme_key']) ?>
                                            </span>
                                        </td>
                                        <td><?= h((string)$theme['title']) ?></td>
                                        <td>
                                            <a
                                                class="btn btn-primary btn-sm"
                                                href="question.php?theme_key=<?= urlencode((string)$theme['theme_key']) ?>">
                                                ワークに取り組む
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        <?php else: ?>
            <section class="section">
                <h2 class="col-title">メニュー</h2>
                <div class="btn-row" style="margin-top:1rem;">
                    <a class="btn btn-primary" href="admin_dashboard.php">管理ダッシュボード</a>
                    <?php if ($role === 'admin'): ?>
                        <a class="btn btn-ghost" href="admin_users.php">ユーザー管理</a>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>
</body>

</html>