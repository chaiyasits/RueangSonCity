<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$db = getDB();
$settings = getAllSettings();

$totalRooms = $db->query("SELECT COUNT(*) as c FROM rooms WHERE is_active = 1")->fetch()['c'];
$totalBookings = $db->query("SELECT COUNT(*) as c FROM bookings")->fetch()['c'];
$pendingBookings = $db->query("SELECT COUNT(*) as c FROM bookings WHERE status = 'pending'")->fetch()['c'];
$totalRevenue = $db->query("SELECT SUM(total_price) as s FROM bookings WHERE status = 'confirmed'")->fetch()['s'] ?? 0;
$totalComments = $db->query("SELECT COUNT(*) as c FROM comments")->fetch()['c'];
$pendingComments = $db->query("SELECT COUNT(*) as c FROM comments WHERE is_approved = 0")->fetch()['c'];
$totalGallery = $db->query("SELECT COUNT(*) as c FROM gallery")->fetch()['c'];

$recentBookings = $db->query("SELECT b.*, r.name as room_name FROM bookings b LEFT JOIN rooms r ON b.room_id = r.id ORDER BY b.created_at DESC LIMIT 5")->fetchAll();
$recentComments = $db->query("SELECT * FROM comments ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - <?= sanitize($settings['homestay_name'] ?? 'Homestay') ?></title>
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
      <h1>Dashboard</h1>
      <p>ภาพรวมระบบ <?= sanitize($settings['homestay_name'] ?? '') ?></p>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <div class="stat-card blue">
        <div class="stat-icon">📅</div>
        <div class="stat-info">
          <div class="stat-num"><?= $totalBookings ?></div>
          <div class="stat-label">การจองทั้งหมด</div>
          <?php if ($pendingBookings > 0): ?>
          <div class="stat-badge"><?= $pendingBookings ?> รอดำเนินการ</div>
          <?php endif; ?>
        </div>
      </div>
      <div class="stat-card green">
        <div class="stat-icon">💰</div>
        <div class="stat-info">
          <div class="stat-num">฿<?= number_format($totalRevenue) ?></div>
          <div class="stat-label">รายได้รวม</div>
        </div>
      </div>
      <div class="stat-card yellow">
        <div class="stat-icon">💬</div>
        <div class="stat-info">
          <div class="stat-num"><?= $totalComments ?></div>
          <div class="stat-label">รีวิวทั้งหมด</div>
          <?php if ($pendingComments > 0): ?>
          <div class="stat-badge"><?= $pendingComments ?> รอตรวจสอบ</div>
          <?php endif; ?>
        </div>
      </div>
      <div class="stat-card purple">
        <div class="stat-icon">🖼️</div>
        <div class="stat-info">
          <div class="stat-num"><?= $totalGallery ?></div>
          <div class="stat-label">รูปภาพทั้งหมด</div>
        </div>
      </div>
    </div>

    <!-- Recent Bookings & Comments -->
    <div class="dashboard-grid">
      <div class="dashboard-card">
        <div class="card-header">
          <h3>การจองล่าสุด</h3>
          <a href="/admin/bookings.php" class="card-link">ดูทั้งหมด →</a>
        </div>
        <div class="table-wrap">
          <table class="admin-table">
            <thead>
              <tr><th>ชื่อผู้จอง</th><th>ห้องพัก</th><th>เช็คอิน</th><th>สถานะ</th></tr>
            </thead>
            <tbody>
              <?php foreach ($recentBookings as $b): ?>
              <tr>
                <td><?= sanitize($b['guest_name']) ?></td>
                <td><?= sanitize($b['room_name'] ?? '-') ?></td>
                <td><?= formatDate($b['check_in']) ?></td>
                <td><span class="status-badge status-<?= $b['status'] ?>"><?= sanitize($b['status']) ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="dashboard-card">
        <div class="card-header">
          <h3>รีวิวล่าสุด</h3>
          <a href="/admin/comments.php" class="card-link">ดูทั้งหมด →</a>
        </div>
        <div class="recent-comments-list">
          <?php foreach ($recentComments as $c): ?>
          <div class="recent-comment-item">
            <div class="comment-avatar-sm"><?= mb_substr($c['author_name'], 0, 1, 'UTF-8') ?></div>
            <div class="comment-preview">
              <strong><?= sanitize($c['author_name']) ?></strong>
              <span class="stars-sm"><?= starRating($c['rating']) ?></span>
              <p><?= mb_substr(sanitize($c['content']), 0, 80, 'UTF-8') ?>...</p>
            </div>
            <span class="comment-status <?= $c['is_approved'] ? 'approved' : 'pending' ?>">
              <?= $c['is_approved'] ? '✓' : '⏳' ?>
            </span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
      <h3>Quick Actions</h3>
      <div class="quick-action-grid">
        <a href="/admin/upload.php" class="quick-action-btn">📷 อัพโหลดรูปภาพ</a>
        <a href="/admin/bookings.php" class="quick-action-btn">📅 จัดการการจอง</a>
        <a href="/admin/comments.php" class="quick-action-btn">💬 จัดการรีวิว</a>
        <a href="/admin/rooms.php" class="quick-action-btn">🏠 จัดการห้องพัก</a>
        <a href="/admin/settings.php" class="quick-action-btn">⚙️ ตั้งค่าเว็บไซต์</a>
        <a href="/" target="_blank" class="quick-action-btn">🌐 ดูหน้าเว็บ</a>
      </div>
    </div>
  </div>
</div>

<script src="/assets/js/admin.js"></script>
</body>
</html>
