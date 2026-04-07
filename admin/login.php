<?php
require_once __DIR__ . '/../includes/auth.php';

if (isAdminLoggedIn()) {
    header('Location: /admin/');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (adminLogin($username, $password)) {
        header('Location: /admin/');
        exit;
    }
    $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — RueangSonCity</title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-login-page">
<div class="login-wrap">
  <div class="login-card">
    <div class="login-header">
      <div class="login-logo">🌿</div>
      <h1>RueangSonCity</h1>
      <p>เข้าสู่ระบบจัดการ Admin Panel</p>
    </div>
    <?php if ($error): ?>
    <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" class="login-form">
      <div class="form-group">
        <label>ชื่อผู้ใช้</label>
        <input type="text" name="username" placeholder="username" autofocus required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>รหัสผ่าน</label>
        <input type="password" name="password" placeholder="password" required>
      </div>
      <button type="submit" class="btn-submit-book">เข้าสู่ระบบ</button>
    </form>
    <div class="login-hint">
      <small>Default: admin / admin1234</small>
    </div>
    <a href="/" class="back-link">← กลับสู่หน้าเว็บ</a>
  </div>
</div>
</body>
</html>
