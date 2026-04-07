<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$settings = getAllSettings();
$db = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    if ($_POST['action'] === 'approve') {
        $db->prepare("UPDATE comments SET is_approved = 1 WHERE id = ?")->execute([$id]);
        $message = "อนุมัติรีวิวสำเร็จ";
    } elseif ($_POST['action'] === 'reject') {
        $db->prepare("UPDATE comments SET is_approved = 0 WHERE id = ?")->execute([$id]);
        $message = "ปฏิเสธรีวิวสำเร็จ";
    } elseif ($_POST['action'] === 'delete') {
        $db->prepare("DELETE FROM comments WHERE id = ?")->execute([$id]);
        $message = "ลบรีวิวสำเร็จ";
    } elseif ($_POST['action'] === 'add') {
        $name = sanitize($_POST['author_name']);
        $content = sanitize($_POST['content']);
        $rating = min(5, max(1, (int)$_POST['rating']));
        $platform = sanitize($_POST['platform']);
        $db->prepare("INSERT INTO comments (author_name, content, rating, platform, is_approved) VALUES (?, ?, ?, ?, 1)")
           ->execute([$name, $content, $rating, $platform]);
        $message = "เพิ่มรีวิวสำเร็จ";
    }
}

$filter = $_GET['filter'] ?? 'all';
$where = $filter === 'pending' ? 'WHERE is_approved = 0' : ($filter === 'approved' ? 'WHERE is_approved = 1' : '');
$comments = $db->query("SELECT * FROM comments $where ORDER BY created_at DESC")->fetchAll();

$counts = [
    'all' => $db->query("SELECT COUNT(*) as c FROM comments")->fetch()['c'],
    'approved' => $db->query("SELECT COUNT(*) as c FROM comments WHERE is_approved = 1")->fetch()['c'],
    'pending' => $db->query("SELECT COUNT(*) as c FROM comments WHERE is_approved = 0")->fetch()['c'],
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>รีวิว - Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/homestay/assets/css/style.css">
<link rel="stylesheet" href="/homestay/assets/css/admin.css">
</head>
<body class="admin-body">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-main">
  <?php include __DIR__ . '/partials/topbar.php'; ?>
  <div class="admin-content">
    <div class="page-header-flex">
      <div>
        <h1>💬 จัดการรีวิว</h1>
        <p>อนุมัติและจัดการรีวิวจากผู้เข้าพัก</p>
      </div>
      <button class="btn-primary" onclick="document.getElementById('addCommentModal').style.display='flex'">+ เพิ่มรีวิว</button>
    </div>

    <?php if ($message): ?><div class="alert-success">✅ <?= sanitize($message) ?></div><?php endif; ?>

    <div class="filter-tabs-admin">
      <a href="?filter=all" class="filter-tab-admin <?= $filter === 'all' ? 'active' : '' ?>">ทั้งหมด (<?= $counts['all'] ?>)</a>
      <a href="?filter=approved" class="filter-tab-admin <?= $filter === 'approved' ? 'active' : '' ?>">อนุมัติแล้ว (<?= $counts['approved'] ?>)</a>
      <a href="?filter=pending" class="filter-tab-admin <?= $filter === 'pending' ? 'active' : '' ?>">รอตรวจสอบ (<?= $counts['pending'] ?>)</a>
    </div>

    <div class="comments-admin-grid">
      <?php foreach ($comments as $c): ?>
      <div class="comment-admin-card <?= !$c['is_approved'] ? 'pending' : '' ?>">
        <div class="comment-admin-header">
          <div class="comment-avatar-md"><?= mb_substr($c['author_name'], 0, 1, 'UTF-8') ?></div>
          <div class="comment-meta">
            <strong><?= sanitize($c['author_name']) ?></strong>
            <span class="platform-chip"><?= platformIcon($c['platform']) ?> <?= platformLabel($c['platform']) ?></span>
            <span class="stars-sm"><?= starRating($c['rating']) ?></span>
          </div>
          <span class="comment-date"><?= date('d/m/Y', strtotime($c['created_at'])) ?></span>
        </div>
        <p class="comment-body-text"><?= sanitize($c['content']) ?></p>

        <?php if (!empty($c['images'])):
          $imgs = json_decode($c['images'], true) ?: [];
          if (!empty($imgs)): ?>
        <div class="comment-images-row">
          <?php foreach ($imgs as $img): ?>
          <img src="/homestay/uploads/gallery/<?= sanitize($img) ?>" alt="" class="comment-img-thumb">
          <?php endforeach; ?>
        </div>
        <?php endif; endif; ?>

        <div class="comment-admin-actions">
          <?php if (!$c['is_approved']): ?>
          <form method="POST" style="display:inline">
            <input type="hidden" name="id" value="<?= $c['id'] ?>">
            <input type="hidden" name="action" value="approve">
            <button type="submit" class="btn-sm btn-success">✓ อนุมัติ</button>
          </form>
          <form method="POST" style="display:inline">
            <input type="hidden" name="id" value="<?= $c['id'] ?>">
            <input type="hidden" name="action" value="reject">
            <button type="submit" class="btn-sm btn-warning">✗ ปฏิเสธ</button>
          </form>
          <?php else: ?>
          <span class="badge-approved">✓ อนุมัติแล้ว</span>
          <?php endif; ?>
          <form method="POST" style="display:inline" onsubmit="return confirm('ลบรีวิวนี้?')">
            <input type="hidden" name="id" value="<?= $c['id'] ?>">
            <input type="hidden" name="action" value="delete">
            <button type="submit" class="btn-sm btn-danger">🗑 ลบ</button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Add Comment Modal -->
<div class="modal-overlay" id="addCommentModal" onclick="this.style.display='none'">
  <div class="modal-box" onclick="event.stopPropagation()">
    <div class="modal-header">
      <h3>เพิ่มรีวิว</h3>
      <button onclick="document.getElementById('addCommentModal').style.display='none'">✕</button>
    </div>
    <form method="POST" class="modal-form">
      <input type="hidden" name="action" value="add">
      <div class="form-row">
        <div class="form-group">
          <label>ชื่อผู้รีวิว</label>
          <input type="text" name="author_name" required>
        </div>
        <div class="form-group">
          <label>Platform</label>
          <select name="platform">
            <option value="website">Website</option>
            <option value="facebook">Facebook</option>
            <option value="google">Google</option>
            <option value="airbnb">Airbnb</option>
            <option value="tripadvisor">TripAdvisor</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>คะแนน</label>
        <select name="rating">
          <option value="5">★★★★★ 5 ดาว</option>
          <option value="4">★★★★☆ 4 ดาว</option>
          <option value="3">★★★☆☆ 3 ดาว</option>
          <option value="2">★★☆☆☆ 2 ดาว</option>
          <option value="1">★☆☆☆☆ 1 ดาว</option>
        </select>
      </div>
      <div class="form-group">
        <label>เนื้อหารีวิว</label>
        <textarea name="content" rows="4" required></textarea>
      </div>
      <button type="submit" class="btn-submit-book">บันทึกรีวิว</button>
    </form>
  </div>
</div>
<script src="/homestay/assets/js/admin.js"></script>
</body>
</html>
