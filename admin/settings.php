<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$db = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['homestay_name','tagline','address','phone','email','line_id','facebook','instagram','checkin_time','checkout_time'];
    $stmt = $db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)");
    foreach ($fields as $f) {
        $val = sanitize($_POST[$f] ?? '');
        $stmt->execute([$f, $val]);
    }

    // Change password
    if (!empty($_POST['new_password'])) {
        if ($_POST['new_password'] === $_POST['confirm_password']) {
            $hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $db->prepare("UPDATE admin_users SET password = ? WHERE id = ?")
               ->execute([$hash, $_SESSION['admin_id']]);
            $message = "บันทึกการตั้งค่าและเปลี่ยนรหัสผ่านสำเร็จ";
        } else {
            $message = "⚠️ รหัสผ่านไม่ตรงกัน";
        }
    } else {
        $message = "บันทึกการตั้งค่าสำเร็จ";
    }
}

$settings = getAllSettings();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ตั้งค่า - Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-main">
  <?php include __DIR__ . '/partials/topbar.php'; ?>
  <div class="admin-content">
    <div class="page-header">
      <h1>⚙️ ตั้งค่าเว็บไซต์</h1>
      <p>ข้อมูลทั่วไปและการตั้งค่าระบบ</p>
    </div>

    <?php if ($message): ?><div class="alert-success">✅ <?= sanitize($message) ?></div><?php endif; ?>

    <form method="POST" class="settings-form">
      <div class="settings-section">
        <h3>ข้อมูลทั่วไป</h3>
        <div class="form-row">
          <div class="form-group">
            <label>ชื่อ Homestay</label>
            <input type="text" name="homestay_name" value="<?= sanitize($settings['homestay_name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Tagline / คำโปรย</label>
            <input type="text" name="tagline" value="<?= sanitize($settings['tagline'] ?? '') ?>">
          </div>
        </div>
        <div class="form-group">
          <label>ที่อยู่</label>
          <textarea name="address" rows="2"><?= sanitize($settings['address'] ?? '') ?></textarea>
        </div>
      </div>

      <div class="settings-section">
        <h3>ช่องทางติดต่อ</h3>
        <div class="form-row">
          <div class="form-group">
            <label>เบอร์โทรศัพท์</label>
            <input type="text" name="phone" value="<?= sanitize($settings['phone'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>อีเมล</label>
            <input type="email" name="email" value="<?= sanitize($settings['email'] ?? '') ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>LINE ID</label>
            <input type="text" name="line_id" value="<?= sanitize($settings['line_id'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Facebook URL</label>
            <input type="url" name="facebook" value="<?= sanitize($settings['facebook'] ?? '') ?>">
          </div>
        </div>
        <div class="form-group">
          <label>Instagram URL</label>
          <input type="url" name="instagram" value="<?= sanitize($settings['instagram'] ?? '') ?>">
        </div>
      </div>

      <div class="settings-section">
        <h3>เวลาเช็คอิน / เช็คเอาท์</h3>
        <div class="form-row">
          <div class="form-group">
            <label>เวลาเช็คอิน</label>
            <input type="time" name="checkin_time" value="<?= sanitize($settings['checkin_time'] ?? '14:00') ?>">
          </div>
          <div class="form-group">
            <label>เวลาเช็คเอาท์</label>
            <input type="time" name="checkout_time" value="<?= sanitize($settings['checkout_time'] ?? '12:00') ?>">
          </div>
        </div>
      </div>

      <div class="settings-section">
        <h3>เปลี่ยนรหัสผ่าน Admin</h3>
        <div class="form-row">
          <div class="form-group">
            <label>รหัสผ่านใหม่</label>
            <input type="password" name="new_password" placeholder="เว้นว่างถ้าไม่ต้องการเปลี่ยน">
          </div>
          <div class="form-group">
            <label>ยืนยันรหัสผ่าน</label>
            <input type="password" name="confirm_password" placeholder="กรอกรหัสผ่านอีกครั้ง">
          </div>
        </div>
      </div>

      <button type="submit" class="btn-submit-book">💾 บันทึกการตั้งค่า</button>
    </form>
  </div>
</div>
<script src="/assets/js/admin.js"></script>
</body>
</html>
