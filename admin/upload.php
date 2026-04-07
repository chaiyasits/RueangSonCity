<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$settings = getAllSettings();
$db = getDB();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = sanitize($_POST['category'] ?? 'gallery');
    $caption = sanitize($_POST['caption'] ?? '');
    $setHero = isset($_POST['set_hero']) && $_POST['set_hero'] === '1';

    $validCategories = ['gallery', 'rooms', 'environment'];
    if (!in_array($category, $validCategories)) $category = 'gallery';

    $uploaded = 0;
    $errors = [];

    if (!empty($_FILES['images']['name'][0])) {
        $count = count($_FILES['images']['name']);
        for ($i = 0; $i < $count; $i++) {
            $file = [
                'name' => $_FILES['images']['name'][$i],
                'type' => $_FILES['images']['type'][$i],
                'tmp_name' => $_FILES['images']['tmp_name'][$i],
                'error' => $_FILES['images']['error'][$i],
                'size' => $_FILES['images']['size'][$i],
            ];
            if ($file['error'] !== UPLOAD_ERR_OK) continue;

            $result = uploadImage($file, $category);
            if (isset($result['error'])) {
                $errors[] = $file['name'] . ': ' . $result['error'];
            } else {
                $db->prepare("INSERT INTO gallery (filename, caption, category) VALUES (?, ?, ?)")
                   ->execute([$result['filename'], $caption ?: $file['name'], $category]);

                if ($setHero && $i === 0 && $category === 'gallery') {
                    $db->prepare("UPDATE settings SET value = ? WHERE  = 'hero_image'")->execute([$result['filename']]);
                }
                $uploaded++;
            }
        }
    }

    // Handle room cover image
    if (!empty($_FILES['room_cover']['name']) && $_FILES['room_cover']['error'] === UPLOAD_ERR_OK) {
        $roomId = (int)($_POST['room_id'] ?? 0);
        if ($roomId) {
            $result = uploadImage($_FILES['room_cover'], 'rooms');
            if (isset($result['filename'])) {
                $db->prepare("UPDATE rooms SET cover_image = ? WHERE id = ?")->execute([$result['filename'], $roomId]);
                $message = "อัพโหลดรูปปกห้องพักสำเร็จ!";
            }
        }
    }

    if ($uploaded > 0) {
        $message = "อัพโหลด $uploaded รูปสำเร็จ!";
        if (!empty($errors)) $message .= " (มี " . count($errors) . " ไฟล์ที่ผิดพลาด)";
    }
    if (!empty($errors) && $uploaded === 0) {
        $error = implode(', ', $errors);
    }
}

$rooms = $db->query("SELECT * FROM rooms WHERE is_active = 1")->fetchAll();
$recentUploads = $db->query("SELECT * FROM gallery ORDER BY created_at DESC LIMIT 12")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>อัพโหลดรูปภาพ - Admin</title>
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
      <h1>📷 อัพโหลดรูปภาพ</h1>
      <p>จัดการรูปภาพตัวบ้าน สิ่งแวดล้อม และแกลเลอรี่</p>
    </div>

    <?php if ($message): ?><div class="alert-success">✅ <?= sanitize($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert-error">⚠️ <?= sanitize($error) ?></div><?php endif; ?>

    <div class="upload-layout">
      <!-- Upload Gallery/Environment -->
      <div class="upload-card">
        <h3>📸 อัพโหลดรูปทั่วไป / สิ่งแวดล้อม</h3>
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
          <div class="form-group">
            <label>หมวดหมู่รูปภาพ</label>
            <div class="category-tabs">
              <label class="cat-radio">
                <input type="radio" name="category" value="gallery" checked onchange="updateCategory(this)">
                <span>🖼️ แกลเลอรี่ทั่วไป</span>
              </label>
              <label class="cat-radio">
                <input type="radio" name="category" value="environment" onchange="updateCategory(this)">
                <span>🌳 สิ่งแวดล้อมรอบๆ</span>
              </label>
              <label class="cat-radio">
                <input type="radio" name="category" value="rooms" onchange="updateCategory(this)">
                <span>🏠 ห้องพัก</span>
              </label>
            </div>
          </div>

          <div class="form-group">
            <label>คำบรรยาย (ไม่บังคับ)</label>
            <input type="text" name="caption" placeholder="เช่น สวนด้านหน้า, วิวภูเขา, ลำธาร...">
          </div>

          <div class="form-group">
            <label>
              <input type="checkbox" name="set_hero" value="1"> ตั้งเป็นรูป Hero หน้าหลัก (เฉพาะรูปแรกที่อัพโหลด)
            </label>
          </div>

          <div class="form-group">
            <label>เลือกรูปภาพ</label>
            <div class="drop-zone" id="dropZone">
              <input type="file" name="images[]" id="uploadImages" accept="image/*" multiple onchange="previewUploads(this)">
              <label for="uploadImages" class="drop-zone-label">
                <div class="drop-icon">📁</div>
                <div>ลากวางรูปหรือคลิกเพื่อเลือก</div>
                <small>รองรับ JPG, PNG, WebP, GIF (สูงสุด 10MB/ไฟล์)</small>
              </label>
            </div>
            <div id="uploadPreview" class="upload-preview-grid"></div>
          </div>

          <button type="submit" class="btn-submit-book" id="uploadBtn">⬆️ อัพโหลดรูปภาพ</button>
        </form>
      </div>

      <!-- Room Cover -->
      <div class="upload-card">
        <h3>🏠 อัพโหลดรูปปกห้องพัก</h3>
        <form method="POST" enctype="multipart/form-data">
          <div class="form-group">
            <label>เลือกห้องพัก</label>
            <select name="room_id" required>
              <option value="">-- เลือกห้องพัก --</option>
              <?php foreach ($rooms as $r): ?>
              <option value="<?= $r['id'] ?>"><?= sanitize($r['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>รูปปกห้องพัก</label>
            <div class="drop-zone">
              <input type="file" name="room_cover" id="roomCover" accept="image/*" onchange="previewSingle(this, 'roomPreview')">
              <label for="roomCover" class="drop-zone-label">
                <div class="drop-icon">🏠</div>
                <div>เลือกรูปปกห้องพัก</div>
              </label>
            </div>
            <div id="roomPreview"></div>
          </div>
          <button type="submit" class="btn-submit-book">บันทึกรูปปก</button>
        </form>

        <!-- Room cover current -->
        <div class="current-covers">
          <h4>รูปปกปัจจุบัน</h4>
          <div class="covers-grid">
            <?php foreach ($rooms as $r): ?>
            <div class="cover-item">
              <?php if (!empty($r['cover_image'])): ?>
              <img src="/uploads/rooms/<?= sanitize($r['cover_image']) ?>" alt="">
              <?php else: ?>
              <div class="no-cover">ยังไม่มีรูป</div>
              <?php endif; ?>
              <span><?= sanitize($r['name']) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Uploads -->
    <div class="recent-uploads-section">
      <div class="section-header-admin">
        <h3>รูปภาพล่าสุด</h3>
        <a href="/admin/gallery.php" class="card-link">จัดการทั้งหมด →</a>
      </div>
      <?php if (!empty($recentUploads)): ?>
      <div class="upload-thumb-grid">
        <?php foreach ($recentUploads as $img): ?>
        <div class="upload-thumb">
          <img src="/uploads/<?= sanitize($img['category'] === 'environment' ? 'environment' : ($img['category'] === 'rooms' ? 'rooms' : 'gallery')) ?>/<?= sanitize($img['filename']) ?>" alt="">
          <div class="thumb-info">
            <small><?= sanitize($img['category']) ?></small>
            <small><?= date('d/m/y', strtotime($img['created_at'])) ?></small>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <p class="empty-state">ยังไม่มีรูปภาพ</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function previewUploads(input) {
  const wrap = document.getElementById('uploadPreview');
  wrap.innerHTML = '';
  Array.from(input.files).forEach(file => {
    const reader = new FileReader();
    reader.onload = e => {
      const div = document.createElement('div');
      div.className = 'up-prev-item';
      div.innerHTML = `<img src="${e.target.result}"><span>${file.name}</span>`;
      wrap.appendChild(div);
    };
    reader.readAsDataURL(file);
  });
}

function previewSingle(input, targetId) {
  const wrap = document.getElementById(targetId);
  if (!input.files[0]) return;
  const reader = new FileReader();
  reader.onload = e => {
    wrap.innerHTML = `<div class="single-preview"><img src="${e.target.result}"></div>`;
  };
  reader.readAsDataURL(input.files[0]);
}

// Drag & Drop
const dropZone = document.getElementById('dropZone');
if (dropZone) {
  dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
  dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
  dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('drag-over');
    const input = document.getElementById('uploadImages');
    input.files = e.dataTransfer.files;
    previewUploads(input);
  });
}

function updateCategory(radio) {
  // visual update only
}
</script>
<script src="/assets/js/admin.js"></script>
</body>
</html>
