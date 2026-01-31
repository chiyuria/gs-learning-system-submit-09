<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../inc/ls/functions.php';
require_once __DIR__ . '/../../inc/ls/auth.php';

// admin専用
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
if (!$stmt->execute()) { sql_error($stmt); }
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// roleごとに分割
$students = [];
$teachers = [];
$admins = [];

foreach ($users as $u) {
  $role = (string)($u['role'] ?? '');
  if ($role === 'student') $students[] = $u;
  if ($role === 'teacher') $teachers[] = $u;
  if ($role === 'admin')   $admins[] = $u;
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

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

  <title>Admin | User Management</title>
</head>

<body>
<header class="app-header">
  <div class="header-title">Learning System</div>
  <div style="margin-left:auto;">
    <a href="home.php" class="text-muted">戻る</a>
    <a href="logout_action.php" class="text-muted" style="margin-left:.8rem;">ログアウト</a>
  </div>
</header>

<main class="wrap">

  <section class="section">
    <h2 class="col-title">ユーザー管理</h2>
    <div class="hint">学生・教員・管理者の一覧（パスワードは表示しません）</div>
  </section>

  <!-- 学生 -->
  <section class="section">
    <h3 class="col-title">学生リスト</h3>
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
          <?php if (empty($students)): ?>
            <tr><td colspan="4" class="text-muted">学生がいません</td></tr>
          <?php else: ?>
            <?php foreach ($students as $u): ?>
              <tr>
                <td><?= h((string)$u['id']) ?></td>
                <td>
                  <?php if (h((string)($u['name'] ?? '')) !== ''): ?>
                    <?= h((string)$u['name']) ?>
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

  <!-- 教員 -->
  <section class="section">
    <h3 class="col-title">教員リスト</h3>
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
          <?php if (empty($teachers)): ?>
            <tr><td colspan="4" class="text-muted">教員がいません</td></tr>
          <?php else: ?>
            <?php foreach ($teachers as $u): ?>
              <tr>
                <td><?= h((string)$u['id']) ?></td>
                <td>
                  <?php if (h((string)($u['name'] ?? '')) !== ''): ?>
                    <?= h((string)$u['name']) ?>
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

  <!-- 管理者 -->
  <section class="section">
    <h3 class="col-title">管理者リスト</h3>
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
          <?php if (empty($admins)): ?>
            <tr><td colspan="4" class="text-muted">管理者がいません</td></tr>
          <?php else: ?>
            <?php foreach ($admins as $u): ?>
              <tr>
                <td><?= h((string)$u['id']) ?></td>
                <td>
                  <?php if (h((string)($u['name'] ?? '')) !== ''): ?>
                    <?= h((string)$u['name']) ?>
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

</main>
</body>
</html>
