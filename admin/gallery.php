<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$settings = getAllSettings();
$db = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($_POST['action'] === 'delete' && $id) {
        $img = $db->prepare("SELECT * FROM gallery WHERE id = ?");
        $img->execute([$id]);
        $imgData = $img->fetch();
        if ($imgData) {
            $path = __DIR__ . '/../uploads/' . $imgData['category'] . '/' . $imgData['filename'];
            if (file_exists($path)) @unlink($path);
            $db->prepare("DELETE FROM gallery WHERE id = ?")->execute([$id]);
            $message = "ลบรูปภาพสำเร็จ";
        }
    } elseif ($_POST['action'] === 'update_caption' && $id) {
        $db->prepare("UPDATE gallery SET caption = ? WHERE id = ?")->execute([sanitize($_POST['caption']), $id]);
        $message = "อัพเดทคำบรรยายสำเร็จ";
    } elseif ($_POST['action'] === 'set_hero') {
        $db->prepare("UPDATE settings SET value = ? WHERE key = 'hero_image'")->execute([sanitize($_POST['filename'])]);
        $message = "ตั้งเป็นรูป Hero สำเร็จ";
    }
}

$category = $_GET['category'] ?? 'all';
$where = $category !== 'all' ? "WHERE category = " . $db->quote($category) : '';
$images = $db->query("SELECT * FROM gallery $where ORDER BY sort_order ASC, created_at DESC")->fetchAll();
$heroImage = getSetting('hero_image');
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>แกลเลอรี่ - Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-main">
  <?php include __DIR__ . '/partials/topbar.php'; ?>
  <div class="admin-content">
    <div class="page-header-flex">
      <div>
        <h1>🖼️ จัดการแกลเลอรี่</h1>
        <p>รูปภาพทั้งหมดในระบบ</p>
      </div>
      <a href="/admin/upload.php" class="btn-primary">+ อัพโหลดรูปใหม่</a>
    </div>

    <?php if ($message): ?><div class="alert-success">✅ <?= sanitize($message) ?></div><?php endif; ?>

    <div class="filter-tabs-admin">
      <?php foreach (['all'=>'ทั้งหมด', 'gallery'=>'แกลเลอรี่', 'environment'=>'สิ่งแวดล้อม', 'rooms'=>'ห้องพัก'] as $k => $label): ?>
      <a href="?category=<?= $k ?>" class="filter-tab-admin <?= $category === $k ? 'active' : '' ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </div>

    <?php if (empty($images)): ?>
    <div class="empty-state-admin">
      <p>ยังไม่มีรูปภาพในหมวดนี้</p>
      <a href="/admin/upload.php" class="btn-primary">อัพโหลดรูปภาพ</a>
    </div>
    <?php else: ?>
    <div class="gallery-admin-grid">
      <?php foreach ($images as $img): ?>
      <?php
        $subDir = $img['category'] === 'environment' ? 'environment' : ($img['category'] === 'rooms' ? 'rooms' : 'gallery');
        $isHero = $img['filename'] === $heroImage;
      ?>
      <div class="gallery-admin-item <?= $isHero ? 'is-hero' : '' ?>">
        <?php if ($isHero): ?><div class="hero-badge-admin">🌟 Hero</div><?php endif; ?>
        <img src="/uploads/<?= $subDir ?>/<?= sanitize($img['filename']) ?>" alt="" loading="lazy">
        <div class="gallery-item-overlay">
          <div class="gallery-item-actions">
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="set_hero">
              <input type="hidden" name="filename" value="<?= sanitize($img['filename']) ?>">
              <button type="submit" class="btn-xs" title="ตั้งเป็น Hero">🌟</button>
            </form>
            <button class="btn-xs" onclick="editCaption(<?= $img['id'] ?>, '<?= addslashes(sanitize($img['caption'] ?? '')) ?>')" title="แก้คำบรรยาย">✏️</button>
            <form method="POST" style="display:inline" onsubmit="return confirm('ลบรูปนี้?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $img['id'] ?>">
              <button type="submit" class="btn-xs btn-xs-danger" title="ลบ">🗑</button>
            </form>
          </div>
        </div>
        <div class="gallery-item-info">
          <span class="img-category"><?= sanitize($img['category']) ?></span>
          <?php if (!empty($img['caption'])): ?>
          <p class="img-caption"><?= sanitize($img['caption']) ?></p>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Edit Caption Modal -->
<div class="modal-overlay" id="captionModal" onclick="this.style.display='none'">
  <div class="modal-box small" onclick="event.stopPropagation()">
    <div class="modal-header">
      <h3>แก้ไขคำบรรยาย</h3>
      <button onclick="document.getElementById('captionModal').style.display='none'">✕</button>
    </div>
    <form method="POST" class="modal-form">
      <input type="hidden" name="action" value="update_caption">
      <input type="hidden" name="id" id="captionImgId">
      <div class="form-group">
        <label>คำบรรยาย</label>
        <input type="text" name="caption" id="captionInput">
      </div>
      <button type="submit" class="btn-submit-book">บันทึก</button>
    </form>
  </div>
</div>

<script>
function editCaption(id, caption) {
  document.getElementById('captionImgId').value = id;
  document.getElementById('captionInput').value = caption;
  document.getElementById('captionModal').style.display = 'flex';
}
</script>
<script src="/assets/js/admin.js"></script>
</body>
</html>
