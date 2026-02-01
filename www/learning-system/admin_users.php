<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../inc/ls/functions.php';
require_once __DIR__ . '/../../inc/ls/auth.php';

require_admin();

$pdo = db_conn();

$sql = "
  SELECT id, role, name, login_code, created_at
  FROM users
  WHERE role IN ('student','teacher','admin')
  ORDER BY
    FIELD(role,'student','teacher','admin'),
    id ASC
";
$stmt = $pdo->prepare($sql);
if (!$stmt->execute()) {
    sql_error($stmt);
}
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// roleで分ける
$students = [];
$teachers = [];
$admins = [];

foreach ($users as $u) {
    $role = (string)($u['role'] ?? '');
    if ($role === 'student') {
        $students[] = $u;
    }
    if ($role === 'teacher') {
        $teachers[] = $u;
    }
    if ($role === 'admin') {
        $admins[] = $u;
    }
}
?>
<!doctype html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin | User Management</title>

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
            <h2 class="col-title">ユーザー管理</h2>
            <div class="hint">学生・教員・管理者一覧</div>
        </section>

        <?php
        // 同じ表なのであとで関数化検討
        $groups = [
            '学生リスト' => $students,
            '教員リスト' => $teachers,
            '管理者リスト' => $admins,
        ];
        ?>

        <?php foreach ($groups as $label => $list): ?>
            <section class="section">
                <h3 class="col-title"><?= h($label) ?></h3>

                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>氏名</th>
                                <th>User ID</th>
                                <th>作成日</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($list)): ?>
                                <tr>
                                    <td colspan="4" class="text-muted">該当ユーザーがいません</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($list as $u): ?>
                                    <?php
                                    $name = trim((string)($u['name'] ?? ''));
                                    ?>
                                    <tr>
                                        <td><?= h((string)($u['id'] ?? '')) ?></td>
                                        <td>
                                            <?php if ($name !== ''): ?>
                                                <?= h($name) ?>
                                            <?php else: ?>
                                                <span class="text-muted">（未設定）</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= h((string)($u['login_code'] ?? '')) ?></td>
                                        <td class="text-muted"><?= h((string)($u['created_at'] ?? '')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endforeach; ?>

    </main>
</body>

</html>