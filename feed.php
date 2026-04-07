<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$settings = getAllSettings();
$db = getDB();

$platform = $_GET['platform'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;
$offset = ($page - 1) * $perPage;

$where = $platform !== 'all' ? "WHERE is_approved = 1 AND platform = " . $db->quote($platform) : "WHERE is_approved = 1";
$total = $db->query("SELECT COUNT(*) as c FROM comments $where")->fetch()['c'];
$totalPages = ceil($total / $perPage);

$comments = $db->query("SELECT * FROM comments $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset")->fetchAll();

$platforms = $db->query("SELECT platform, COUNT(*) as cnt FROM comments WHERE is_approved = 1 GROUP BY platform")->fetchAll();
$avgRating = $db->query("SELECT AVG(rating) as avg, COUNT(*) as total FROM comments WHERE is_approved = 1")->fetch();

// Handle new comment POST
$postSuccess = false;
$postError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_comment') {
    $name = sanitize($_POST['author_name'] ?? '');
    $content = sanitize($_POST['content'] ?? '');
    $rating = min(5, max(1, (int)($_POST['rating'] ?? 5)));
    $commentPlatform = sanitize($_POST['platform'] ?? 'website');

    if (!$name || !$content) {
        $postError = 'กรุณากรอกชื่อและเนื้อหารีวิว';
    } elseif (strlen($content) < 10) {
        $postError = 'รีวิวต้องมีความยาวอย่างน้อย 10 ตัวอักษร';
    } else {
        $images = [];
        if (!empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $result = uploadImage([
                        'name' => $_FILES['images']['name'][$i],
                        'type' => $_FILES['images']['type'][$i],
                        'tmp_name' => $tmp,
                        'error' => $_FILES['images']['error'][$i],
                        'size' => $_FILES['images']['size'][$i],
                    ], 'gallery');
                    if (isset($result['filename'])) $images[] = $result['filename'];
                }
            }
        }

        $db->prepare("INSERT INTO comments (author_name, content, rating, platform, images) VALUES (?, ?, ?, ?, ?)")
           ->execute([$name, $content, $rating, $commentPlatform, json_encode($images)]);
        $postSuccess = true;
        header('Location: /homestay/feed.php?posted=1#reviews-list');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>รีวิว & Community — <?= sanitize($settings['homestay_name'] ?? 'RueangSonCity') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/homestay/assets/css/style.css">
</head>
<body class="feed-page">

<nav class="navbar scrolled">
  <div class="nav-container">
    <a href="/homestay/" class="nav-brand"><span class="brand-icon">🌿</span><span><?= sanitize($settings['homestay_name'] ?? 'RueangSonCity') ?></span></a>
    <ul class="nav-links">
      <li><a href="/homestay/">หน้าหลัก</a></li>
      <li><a href="/homestay/#rooms">ห้องพัก</a></li>
      <li><a href="/homestay/#pets">Pet Friendly</a></li>
      <li><a href="/homestay/booking.php" class="btn-nav-book">จองที่พัก</a></li>
    </ul>
  </div>
</nav>

<div class="page-hero feed-hero">
  <h1>💬 รีวิว & Community</h1>
  <p>แชร์ประสบการณ์การพักที่ RueangSonCity — Pet Friendly Homestay กรุงเทพฯ</p>
</div>

<div class="feed-container">

  <!-- Stats Bar -->
  <div class="feed-stats-bar">
    <div class="feed-stat-item">
      <span class="feed-stat-num"><?= number_format($avgRating['avg'] ?? 5, 1) ?></span>
      <div>
        <div class="stars-med">★★★★★</div>
        <small>คะแนนเฉลี่ย</small>
      </div>
    </div>
    <div class="feed-stat-divider"></div>
    <div class="feed-stat-item">
      <span class="feed-stat-num"><?= $avgRating['total'] ?></span>
      <small>รีวิวทั้งหมด</small>
    </div>
    <div class="feed-stat-divider"></div>
    <div class="feed-stat-item">
      <span class="feed-stat-num"><?= count($platforms) ?></span>
      <small>Platform</small>
    </div>
    <div class="feed-write-btn-wrap">
      <a href="#write-review" class="btn-primary">✏️ เขียนรีวิว</a>
    </div>
  </div>

  <!-- Filter Tabs -->
  <div class="feed-filters" id="reviews-list">
    <a href="?platform=all" class="filter-tab <?= $platform === 'all' ? 'active' : '' ?>">ทั้งหมด (<?= $total ?>)</a>
    <?php foreach ($platforms as $p): ?>
    <a href="?platform=<?= $p['platform'] ?>" class="filter-tab <?= $platform === $p['platform'] ? 'active' : '' ?>">
      <?= platformIcon($p['platform']) ?> <?= platformLabel($p['platform']) ?> (<?= $p['cnt'] ?>)
    </a>
    <?php endforeach; ?>
  </div>

  <?php if (isset($_GET['posted'])): ?>
  <div class="alert-success">✅ ขอบคุณสำหรับรีวิว! รีวิวของคุณจะแสดงหลังจากผ่านการตรวจสอบ</div>
  <?php endif; ?>

  <!-- Reviews Grid -->
  <div class="reviews-feed-grid">
    <?php foreach ($comments as $c): ?>
    <div class="feed-card">
      <div class="feed-card-header">
        <div class="feed-avatar"><?= mb_substr($c['author_name'], 0, 1, 'UTF-8') ?></div>
        <div class="feed-user-info">
          <strong><?= sanitize($c['author_name']) ?></strong>
          <span class="feed-time"><?= timeAgo($c['created_at']) ?></span>
        </div>
        <div class="feed-platform-badge">
          <span><?= platformIcon($c['platform']) ?></span>
          <span class="platform-name"><?= platformLabel($c['platform']) ?></span>
        </div>
      </div>

      <div class="feed-rating"><?= starRating($c['rating']) ?></div>
      <p class="feed-content"><?= nl2br(sanitize($c['content'])) ?></p>

      <?php if (!empty($c['images'])):
        $imgs = json_decode($c['images'], true) ?: [];
        if (!empty($imgs)): ?>
      <div class="feed-images">
        <?php foreach (array_slice($imgs, 0, 3) as $img): ?>
        <div class="feed-img-thumb" onclick="openImgModal('/homestay/uploads/gallery/<?= sanitize($img) ?>')">
          <img src="/homestay/uploads/gallery/<?= sanitize($img) ?>" alt="">
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; endif; ?>

      <div class="feed-actions">
        <button class="like-btn" onclick="likeComment(<?= $c['id'] ?>, this)">❤️ <?= $c['likes'] ?></button>
        <button class="share-btn" onclick="shareReview('<?= sanitize($c['author_name']) ?>')">🔗 แชร์</button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a href="?platform=<?= $platform ?>&page=<?= $i ?>" class="page-btn <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>

  <!-- Write Review Form -->
  <div class="write-review-section" id="write-review">
    <div class="write-review-card">
      <h2>✏️ แบ่งปันประสบการณ์ของคุณ</h2>
      <p>รีวิวของคุณช่วยให้ผู้อื่นตัดสินใจได้ง่ายขึ้น</p>

      <?php if ($postError): ?>
      <div class="alert-error">⚠️ <?= sanitize($postError) ?></div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data" class="review-form">
        <input type="hidden" name="action" value="add_comment">

        <div class="form-row">
          <div class="form-group">
            <label>ชื่อของคุณ <span class="required">*</span></label>
            <input type="text" name="author_name" placeholder="ชื่อ-นามสกุล" required>
          </div>
          <div class="form-group">
            <label>Platform</label>
            <select name="platform">
              <option value="website">Website</option>
              <option value="facebook">Facebook</option>
              <option value="google">Google Reviews</option>
              <option value="airbnb">Airbnb</option>
              <option value="tripadvisor">TripAdvisor</option>
              <option value="instagram">Instagram</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>คะแนน <span class="required">*</span></label>
          <div class="star-picker" id="starPicker">
            <?php for ($i = 1; $i <= 5; $i++): ?>
            <span class="star-btn" data-val="<?= $i ?>" onclick="setRating(<?= $i ?>)">★</span>
            <?php endfor; ?>
          </div>
          <input type="hidden" name="rating" id="ratingInput" value="5">
        </div>

        <div class="form-group">
          <label>รีวิวของคุณ <span class="required">*</span></label>
          <textarea name="content" rows="5" placeholder="เล่าประสบการณ์การเข้าพัก สิ่งที่ประทับใจ หรือข้อเสนอแนะ..." required minlength="10"></textarea>
        </div>

        <div class="form-group">
          <label>รูปภาพ (ถ้ามี)</label>
          <div class="file-upload-area" id="fileUploadArea">
            <input type="file" name="images[]" id="reviewImages" accept="image/*" multiple onchange="previewImages(this)">
            <label for="reviewImages" class="file-upload-label">
              <span class="upload-icon">📷</span>
              <span>คลิกเพื่อเลือกรูป หรือลากวางที่นี่</span>
              <small>JPG, PNG, WebP (สูงสุด 5 รูป, ขนาดไม่เกิน 10MB/รูป)</small>
            </label>
          </div>
          <div id="imagePreviewWrap" class="image-preview-wrap"></div>
        </div>

        <button type="submit" class="btn-submit-review">ส่งรีวิว</button>
      </form>
    </div>
  </div>
</div>

<!-- Image Modal -->
<div class="lightbox" id="imgModal" onclick="document.getElementById('imgModal').classList.remove('active')">
  <button class="lightbox-close">✕</button>
  <div class="lightbox-content" onclick="event.stopPropagation()">
    <img id="modalImg" src="" alt="">
  </div>
</div>

<footer class="footer">
  <div class="container">
    <div class="footer-bottom">
      © <?= date('Y') ?> <?= sanitize($settings['homestay_name'] ?? 'RueangSonCity') ?> ·
      <a href="/homestay/">หน้าหลัก</a> ·
      <a href="/homestay/booking.php">จองที่พัก</a> ·
      <a href="/homestay/#pets">Pet Friendly</a>
    </div>
  </div>
</footer>

<script>
let currentRating = 5;
function setRating(val) {
  currentRating = val;
  document.getElementById('ratingInput').value = val;
  document.querySelectorAll('.star-btn').forEach((s, i) => {
    s.classList.toggle('active', i < val);
  });
}
setRating(5);

function previewImages(input) {
  const wrap = document.getElementById('imagePreviewWrap');
  wrap.innerHTML = '';
  if (input.files.length > 5) {
    alert('สามารถเลือกได้สูงสุด 5 รูป');
    return;
  }
  Array.from(input.files).forEach(file => {
    const reader = new FileReader();
    reader.onload = e => {
      const div = document.createElement('div');
      div.className = 'preview-thumb';
      div.innerHTML = `<img src="${e.target.result}" alt="">`;
      wrap.appendChild(div);
    };
    reader.readAsDataURL(file);
  });
}

function openImgModal(src) {
  document.getElementById('modalImg').src = src;
  document.getElementById('imgModal').classList.add('active');
}

async function likeComment(id, btn) {
  try {
    const res = await fetch('/homestay/api/comments.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({action: 'like', id})
    });
    const data = await res.json();
    if (data.likes !== undefined) btn.innerHTML = '❤️ ' + data.likes;
  } catch(e) {}
}

function shareReview(name) {
  const text = `รีวิว ${name} จาก <?= sanitize($settings['homestay_name'] ?? '') ?>`;
  if (navigator.share) {
    navigator.share({title: text, url: window.location.href});
  } else {
    navigator.clipboard?.writeText(window.location.href);
    alert('คัดลอกลิงก์แล้ว!');
  }
}
</script>
</body>
</html>
