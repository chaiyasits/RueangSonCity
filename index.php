<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$settings = getAllSettings();
$db = getDB();

$rooms    = $db->query("SELECT * FROM rooms WHERE is_active = 1 ORDER BY price ASC")->fetchAll();
$comments = $db->query("SELECT * FROM comments WHERE is_approved = 1 ORDER BY created_at DESC LIMIT 6")->fetchAll();
$gallery  = $db->query("SELECT * FROM gallery WHERE category IN ('gallery','environment') ORDER BY category DESC, sort_order ASC, created_at DESC")->fetchAll();
$envPics  = $db->query("SELECT * FROM gallery WHERE category = 'environment' ORDER BY sort_order ASC LIMIT 4")->fetchAll();
$avgRating = $db->query("SELECT AVG(rating) as avg, COUNT(*) as total FROM comments WHERE is_approved = 1")->fetch();
$platforms = $db->query("SELECT platform, COUNT(*) as cnt FROM comments WHERE is_approved = 1 GROUP BY platform ORDER BY cnt DESC")->fetchAll();

$heroImage = $settings['hero_image'] ?? '';
$siteName  = sanitize($settings['homestay_name'] ?? 'RueangSonCity');

// Helper: resolve gallery image path
function galPath($img)
{
    $dir = $img['category'] === 'environment' ? 'environment'
         : ($img['category'] === 'rooms' ? 'rooms'
         : 'gallery');
    return '/uploads/' . $dir . '/' . $img['filename'];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $siteName ?> — Pet Friendly Homestay กรุงเทพฯ</title>
<meta name="description" content="ที่พัก Pet Friendly ใจกลางกรุงเทพฯ รับทั้งหมา แมว และสัตว์เลี้ยง มีคอกเต่า บรรยากาศร่มรื่น">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<!-- ====== NAVBAR ====== -->
<nav class="navbar" id="navbar">
  <div class="nav-container">
    <a href="/" class="nav-brand">
      <span class="brand-icon">🌿</span>
      <span><?= $siteName ?></span>
    </a>
    <button class="nav-toggle" id="navToggle" aria-label="menu">☰</button>
    <ul class="nav-links" id="navLinks">
      <li><a href="#about">เกี่ยวกับเรา</a></li>
      <li><a href="#pets">Pet Friendly</a></li>
      <li><a href="#rooms">ห้องพัก</a></li>
      <li><a href="#amenities">สิ่งอำนวยฯ</a></li>
      <li><a href="#gallery">แกลเลอรี่</a></li>
      <li><a href="#location">ที่ตั้ง</a></li>
      <li><a href="#reviews">รีวิว</a></li>
      <li><a href="/booking.php" class="btn-nav-book">จองที่พัก</a></li>
    </ul>
  </div>
</nav>

<!-- ====== HERO ====== -->
<section class="hero" id="hero">
  <div class="hero-bg">
    <?php if (!empty($heroImage)): ?>
      <img src="/uploads/gallery/<?= sanitize($heroImage) ?>" alt="<?= $siteName ?>" class="hero-bg-img">
    <?php else: ?>
      <div class="hero-gradient"></div>
    <?php endif; ?>
    <div class="hero-overlay"></div>
    <div class="hero-pattern"></div>
  </div>

  <div class="hero-content">
    <div class="hero-tag">
      <span class="hero-tag-dot"></span>
      หลักสอง บางแค กรุงเทพฯ
    </div>
    <h1 class="hero-title">
      <span class="accent-word">Rueang</span>Son<br>City
    </h1>
    <p class="hero-sub">ที่พักส่วนตัวใจกลางเมือง บรรยากาศร่มรื่น สัตว์เลี้ยงมาได้ทุกตัว</p>
    <div class="hero-pets">
      🐕 Dog Friendly &nbsp;·&nbsp; 🐱 Cat Friendly &nbsp;·&nbsp; 🐢 มีคอกเต่า
    </div>
    <div class="hero-actions">
      <a href="/booking.php" class="btn-primary">จองที่พัก →</a>
      <a href="#gallery" class="btn-outline">ชมรูปภาพ</a>
    </div>
  </div>

  <div class="hero-scroll">
    <span>เลื่อนลง</span>
    <div class="scroll-line"></div>
  </div>
</section>

<!-- ====== QUICK STATS ====== -->
<div class="quick-stats-bar">
  <div class="container">
    <div class="qstat-inner">
      <div class="qstat-item">
        <span class="qstat-num"><?= count($rooms) ?>+</span>
        <span class="qstat-label">ห้องพัก</span>
      </div>
      <div class="qstat-div"></div>
      <div class="qstat-item">
        <span class="qstat-num">🐢🐱🐕</span>
        <span class="qstat-label">Pet Friendly</span>
      </div>
      <div class="qstat-div"></div>
      <div class="qstat-item">
        <span class="qstat-num"><?= number_format($avgRating['avg'] ?? 5, 1) ?>★</span>
        <span class="qstat-label">คะแนนเฉลี่ย</span>
      </div>
      <div class="qstat-div"></div>
      <div class="qstat-item">
        <span class="qstat-num"><?= $avgRating['total'] ?? 0 ?>+</span>
        <span class="qstat-label">รีวิว</span>
      </div>
      <div class="qstat-div"></div>
      <div class="qstat-item">
        <span class="qstat-num">999/1</span>
        <span class="qstat-label">หลักสอง บางแค</span>
      </div>
    </div>
  </div>
</div>

<!-- ====== ABOUT ====== -->
<section class="section about-section" id="about">
  <div class="container">
    <div class="about-grid">
      <div class="about-text">
        <div class="section-eyebrow">เกี่ยวกับเรา</div>
        <h2 class="about-title">บ้านพักเล็กๆ<br>ที่เต็มไปด้วย<br><span class="hl">ความอบอุ่น</span></h2>
        <p class="about-desc">
          RueangSonCity คือที่พักส่วนตัวย่านบางแค กรุงเทพฯ ที่เปิดต้อนรับทั้งคุณ
          และสัตว์เลี้ยงสุดที่รัก ท่ามกลางสวนร่มรื่น เงียบสงบ ห่างจากความวุ่นวาย
          แต่เดินทางสะดวก ใกล้ BTS และทางด่วน
        </p>
        <p class="about-desc">
          เจ้าของบ้านเป็นคนรักสัตว์ตัวยง บ้านมีเต่าน่ารัก แมว และหมาที่จะมาต้อนรับคุณ
          บรรยากาศสบายๆ แบบบ้านๆ แต่ครบครัน
        </p>
        <div class="about-chips">
          <span class="chip">🌿 สวนร่มรื่น</span>
          <span class="chip">📶 WiFi เร็ว</span>
          <span class="chip">🚗 ที่จอดรถ</span>
          <span class="chip">🐾 Pet Friendly</span>
          <span class="chip">🏙️ ใกล้ BTS</span>
          <span class="chip">🧹 สะอาด</span>
        </div>
        <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:center">
          <a href="/booking.php" class="btn-primary">จองที่พักเลย</a>
          <a href="#contact" class="btn-outline-dark">ติดต่อเรา</a>
        </div>
      </div>

      <div class="about-img-grid">
        <?php
        // Show up to 4 environment/gallery pics, fallback to SVG placeholder cards
        $aboutPics = !empty($envPics) ? $envPics : array_slice($gallery, 0, 4);
$fallbackEmojis = ['🏡','🌿','🐢','🐱'];
$fallbackLabels = ['ตัวบ้าน','สวนหย่อม','คอกเต่า','น้องแมว'];
$fallbackColors = [['#0d9488','#0891b2'],['#059669','#0d9488'],['#065f46','#0f766e'],['#7c3aed','#0d9488']];
for ($i = 0; $i < 4; $i++):
    $cls = ($i === 1) ? 'about-img-cell' : 'about-img-cell';
    ?>
        <div class="about-img-cell">
          <?php if (isset($aboutPics[$i])): ?>
            <img src="<?= galPath($aboutPics[$i]) ?>" alt="<?= sanitize($aboutPics[$i]['caption'] ?? '') ?>" loading="lazy">
          <?php else: [$c1,$c2] = $fallbackColors[$i]; ?>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 200">
              <defs><linearGradient id="ab<?= $i ?>" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="<?= $c1 ?>"/><stop offset="100%" stop-color="<?= $c2 ?>"/></linearGradient><pattern id="dp<?= $i ?>" width="16" height="16" patternUnits="userSpaceOnUse"><circle cx="2" cy="2" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs>
              <rect width="400" height="200" fill="url(#ab<?= $i ?>)"/>
              <rect width="400" height="200" fill="url(#dp<?= $i ?>)"/>
              <text x="200" y="90" text-anchor="middle" font-size="48" font-family="Segoe UI Emoji,sans-serif"><?= $fallbackEmojis[$i] ?></text>
              <text x="200" y="130" text-anchor="middle" font-size="16" font-weight="600" font-family="Arial,sans-serif" fill="rgba(255,255,255,0.9)"><?= $fallbackLabels[$i] ?></text>
              <text x="200" y="155" text-anchor="middle" font-size="11" font-family="Arial,sans-serif" fill="rgba(255,255,255,0.5)">Mockup · อัพโหลดผ่าน Admin</text>
            </svg>
          <?php endif; ?>
        </div>
        <?php endfor; ?>
      </div>
    </div>
  </div>
</section>

<!-- ====== PET FRIENDLY ====== -->
<section class="section pets-section" id="pets">
  <div class="container" style="position:relative;z-index:1">
    <div class="section-header">
      <div class="section-eyebrow">🐾 Pet Friendly</div>
      <h2 class="section-title">สัตว์เลี้ยง<span class="hl" style="color:var(--accent-light)">มาได้เลย!</span></h2>
      <p class="section-desc" style="color:rgba(255,255,255,.7)">
        เราเปิดต้อนรับน้องๆ ทุกตัว บ้านของเราออกแบบมาเพื่อคุณและสัตว์เลี้ยง
      </p>
      <div class="divider-line" style="background:linear-gradient(90deg,var(--accent-light),var(--secondary-light))"></div>
    </div>

    <div class="pets-grid">
      <div class="pet-card">
        <span class="pet-icon">🐕</span>
        <h3>Dog Friendly</h3>
        <p>น้องหมาเข้าพักได้ มีพื้นที่วิ่งเล่นในสวน ไม่ต้องกลัวว่าน้องจะเบื่อ เจ้าของบ้านรักหมาเหมือนกัน</p>
      </div>
      <div class="pet-card">
        <span class="pet-icon">🐱</span>
        <h3>Cat Friendly</h3>
        <p>น้องแมวมาได้เลย มีที่นั่งเล่นเพียบ บ้านออกแบบมาให้แมวสะดวกสบาย มีทั้งแมวบ้านที่รอต้อนรับ</p>
      </div>
      <div class="pet-card">
        <span class="pet-icon">🐢</span>
        <h3>คอกเต่าสุดน่ารัก</h3>
        <p>ไฮไลต์ของบ้าน! คอกเต่าขนาดใหญ่ มีเต่าน่ารักหลายตัว เด็กๆ และผู้ใหญ่ต่างหลงรักเจ้าเต่าทุกตัว</p>
      </div>
    </div>

    <div class="pet-rules">
      <div class="pet-rules-title">📋 กฎสัตว์เลี้ยง (Pet Policy)</div>
      <div class="pet-rule-item">✅ สัตว์เลี้ยงทุกชนิดยินดีต้อนรับ</div>
      <div class="pet-rule-item">✅ ไม่มีค่าธรรมเนียมเพิ่มสำหรับสัตว์เลี้ยง</div>
      <div class="pet-rule-item">✅ มีพื้นที่ส่วนตัวสำหรับสัตว์เลี้ยง</div>
      <div class="pet-rule-item">⚠️ กรุณาดูแลสัตว์เลี้ยงของตัวเอง</div>
      <div class="pet-rule-item">⚠️ ห้ามปล่อยสัตว์เลี้ยงเข้าคอกเต่าโดยไม่ได้รับอนุญาต</div>
      <div class="pet-rule-item">💡 แจ้งประเภทและจำนวนสัตว์เลี้ยงตอนจอง</div>
    </div>
  </div>
</section>

<!-- ====== ROOMS ====== -->
<section class="section rooms-section" id="rooms">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow">ห้องพัก</div>
      <h2 class="section-title">เลือกห้องที่ <span class="hl">ใช่สำหรับคุณ</span></h2>
      <p class="section-desc">ห้องพักทุกห้องออกแบบมาเพื่อคุณและสัตว์เลี้ยง สะอาด สบาย ครบครัน</p>
      <div class="divider-line"></div>
    </div>
    <div class="rooms-grid">
      <?php foreach ($rooms as $room): ?>
      <div class="room-card">
        <div class="room-img-wrap">
          <?php if (!empty($room['cover_image'])): ?>
            <img src="/uploads/rooms/<?= sanitize($room['cover_image']) ?>" alt="<?= sanitize($room['name']) ?>" class="room-img">
          <?php else: ?>
            <div class="room-img-placeholder">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 560">
                <defs><linearGradient id="rg<?= $room['id'] ?>" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#0f766e"/><stop offset="100%" stop-color="#0891b2"/></linearGradient><pattern id="rp<?= $room['id'] ?>" width="24" height="24" patternUnits="userSpaceOnUse"><circle cx="2" cy="2" r="1" fill="rgba(255,255,255,0.08)"/></pattern></defs>
                <rect width="800" height="560" fill="url(#rg<?= $room['id'] ?>)"/>
                <rect width="800" height="560" fill="url(#rp<?= $room['id'] ?>)"/>
                <text x="400" y="240" text-anchor="middle" font-size="80" font-family="Segoe UI Emoji,sans-serif">🏠</text>
                <text x="400" y="305" text-anchor="middle" font-size="22" font-weight="600" font-family="Arial,sans-serif" fill="rgba(255,255,255,0.9)"><?= sanitize($room['name']) ?></text>
                <text x="400" y="335" text-anchor="middle" font-size="13" font-family="Arial,sans-serif" fill="rgba(255,255,255,0.5)">Mockup · อัพโหลดรูปจริงผ่าน Admin</text>
              </svg>
            </div>
          <?php endif; ?>
          <div class="room-price-tag">฿<?= number_format($room['price']) ?><small>/คืน</small></div>
        </div>
        <div class="room-body">
          <h3 class="room-name"><?= sanitize($room['name']) ?></h3>
          <p class="room-desc"><?= sanitize($room['description']) ?></p>
          <div class="room-capacity">👥 รองรับสูงสุด <?= $room['capacity'] ?> คน</div>
          <?php if (!empty($room['amenities'])): ?>
          <div class="room-amenities">
            <?php foreach (explode(',', $room['amenities']) as $a): ?>
            <span class="amenity-tag"><?= sanitize(trim($a)) ?></span>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
          <a href="/booking.php?room_id=<?= $room['id'] ?>" class="btn-book-room">จองห้องนี้ →</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ====== AMENITIES ====== -->
<section class="section amenities-section" id="amenities">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow">🏡 สิ่งอำนวยความสะดวก</div>
      <h2 class="section-title">ครบครัน <span class="hl">ทุกความต้องการ</span></h2>
      <p class="section-desc">RueangSonCity เตรียมทุกอย่างไว้ให้คุณพร้อม ไม่ต้องพกอะไรมาเพิ่ม</p>
      <div class="divider-line"></div>
    </div>
    <div class="amenities-grid">

      <!-- ครัว -->
      <div class="amenity-card featured">
        <div class="amenity-icon-wrap">
          <span class="amenity-big-icon">🍳</span>
        </div>
        <h3>ครัวครบครัน</h3>
        <p>ครัวขนาดใหญ่ พร้อมอุปกรณ์ทำอาหารครบ เตาแก๊ส ตู้เย็น อ่างล้างจาน ทำอาหารเองได้เลย</p>
        <ul class="amenity-list">
          <li>✓ เตาแก๊ส</li>
          <li>✓ ตู้เย็น</li>
          <li>✓ อุปกรณ์ครัวครบ</li>
          <li>✓ ไมโครเวฟ</li>
        </ul>
      </div>

      <!-- เครื่องชงกาแฟ -->
      <div class="amenity-card">
        <div class="amenity-icon-wrap">
          <span class="amenity-big-icon">☕</span>
        </div>
        <h3>เครื่องชงกาแฟ</h3>
        <p>เครื่องชงกาแฟคุณภาพดี สำหรับเริ่มต้นเช้าวันใหม่ บรรยากาศดีๆ กาแฟหอมๆ</p>
        <ul class="amenity-list">
          <li>✓ เครื่องชงกาแฟ</li>
          <li>✓ กาแฟและชาให้ฟรี</li>
          <li>✓ แก้วและถ้วยพร้อม</li>
        </ul>
      </div>

      <!-- เครื่องกรองน้ำ -->
      <div class="amenity-card">
        <div class="amenity-icon-wrap">
          <span class="amenity-big-icon">💧</span>
        </div>
        <h3>เครื่องกรองน้ำ</h3>
        <p>น้ำดื่มสะอาด ผ่านระบบกรองคุณภาพสูง ดื่มได้ตรงจากก๊อก ปลอดภัย ไม่ต้องซื้อน้ำ</p>
        <ul class="amenity-list">
          <li>✓ เครื่องกรองน้ำ RO</li>
          <li>✓ น้ำดื่มฟรีตลอด</li>
          <li>✓ น้ำเย็น-ร้อน</li>
        </ul>
      </div>

      <!-- เครื่องซักผ้า -->
      <div class="amenity-card">
        <div class="amenity-icon-wrap">
          <span class="amenity-big-icon">👕</span>
        </div>
        <h3>เครื่องซักผ้า</h3>
        <p>เครื่องซักผ้าฝาหน้า พร้อมผงซักฟอก เหมาะสำหรับพักยาว ซักผ้าเองได้สะดวก</p>
        <ul class="amenity-list">
          <li>✓ เครื่องซักผ้าฝาหน้า</li>
          <li>✓ ผงซักฟอกให้</li>
          <li>✓ ราวตากผ้า</li>
        </ul>
      </div>

    </div>

    <!-- Other amenities chips -->
    <div class="amenities-extras">
      <div class="extras-title">สิ่งอำนวยความสะดวกอื่นๆ</div>
      <div class="extras-chips">
        <span class="extra-chip">📶 WiFi ความเร็วสูง</span>
        <span class="extra-chip">❄️ แอร์ทุกห้อง</span>
        <span class="extra-chip">📺 Smart TV</span>
        <span class="extra-chip">🚗 ที่จอดรถ</span>
        <span class="extra-chip">🔒 กล้องวงจรปิด</span>
        <span class="extra-chip">🛁 ห้องน้ำในตัว</span>
        <span class="extra-chip">🧻 ผ้าเช็ดตัวให้</span>
        <span class="extra-chip">🪥 สบู่ แชมพู ให้</span>
        <span class="extra-chip">🌿 สวนหย่อม</span>
        <span class="extra-chip">💡 ไฟฟ้ารวม</span>
        <span class="extra-chip">💦 น้ำรวม</span>
        <span class="extra-chip">🐾 Pet Friendly</span>
      </div>
    </div>
  </div>
</section>

<!-- ====== GALLERY ====== -->
<section class="section gallery-section" id="gallery">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow">แกลเลอรี่</div>
      <h2 class="section-title">ภาพ<span class="hl">บรรยากาศ</span>ที่พัก</h2>
      <p class="section-desc">ชมรูปภาพจริงของ RueangSonCity ทั้งตัวบ้านและน้องสัตว์เลี้ยง</p>
      <div class="divider-line"></div>
    </div>

    <div class="gallery-filters">
      <button class="gal-filter active" data-cat="all">ทั้งหมด</button>
      <button class="gal-filter" data-cat="gallery">ตัวบ้าน</button>
      <button class="gal-filter" data-cat="environment">สัตว์เลี้ยง & สวน</button>
    </div>

    <?php if (!empty($gallery)): ?>
    <div class="gallery-count">
      แสดง <strong><?= count($gallery) ?></strong> รูปภาพทั้งหมด
    </div>
    <div class="gallery-masonry" id="galleryGrid">
      <?php foreach ($gallery as $img): ?>
      <div class="gal-item" data-cat="<?= sanitize($img['category']) ?>"
           onclick="openLightbox('<?= galPath($img) ?>', '<?= sanitize($img['caption'] ?? '') ?>')">
        <img src="<?= galPath($img) ?>" alt="<?= sanitize($img['caption'] ?? '') ?>" loading="lazy">
        <div class="gal-overlay">
          <div class="gal-overlay-inner">
            <?php if (!empty($img['caption'])): ?><span class="gal-caption-text"><?= sanitize($img['caption']) ?></span><?php endif; ?>
            <span class="gal-cat-tag"><?= $img['category'] === 'environment' ? '🌿 สัตว์เลี้ยง' : '🏠 บ้าน' ?></span>
          </div>
        </div>
        <div class="gal-view-btn">🔍</div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="gallery-empty">
      <p>📷 ยังไม่มีรูปภาพ · <a href="/admin/upload.php">อัพโหลดผ่าน Admin Panel</a></p>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- LIGHTBOX -->
<div class="lightbox" id="lightbox" onclick="closeLightbox()">
  <button class="lightbox-close" onclick="closeLightbox()">✕</button>
  <div class="lightbox-content" onclick="event.stopPropagation()">
    <img id="lightboxImg" src="" alt="">
    <p id="lightboxCaption"></p>
  </div>
</div>

<!-- ====== LOCATION ====== -->
<section class="section location-section" id="location">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow">📍 ที่ตั้ง</div>
      <h2 class="section-title">หาเรา<span class="hl">ได้ง่าย</span></h2>
      <p class="section-desc">หลักสอง บางแค กรุงเทพฯ — เดินทางสะดวก ใกล้ทางด่วนและ BTS</p>
      <div class="divider-line"></div>
    </div>
    <div class="location-grid">
      <div class="location-info">
        <div class="loc-card">
          <span class="loc-icon">🏠</span>
          <div>
            <h4>ที่อยู่</h4>
            <p><?= sanitize($settings['address'] ?? '999/1 หลักสอง บางแค กรุงเทพฯ 10160') ?></p>
          </div>
        </div>
        <div class="loc-card">
          <span class="loc-icon">📞</span>
          <div>
            <h4>โทรศัพท์</h4>
            <a href="tel:<?= sanitize($settings['phone'] ?? '') ?>"><?= sanitize($settings['phone'] ?? '') ?></a>
          </div>
        </div>
        <div class="loc-card">
          <span class="loc-icon">💬</span>
          <div>
            <h4>LINE ID</h4>
            <p><?= sanitize($settings['line_id'] ?? '') ?></p>
          </div>
        </div>
        <div class="loc-card">
          <span class="loc-icon">🕐</span>
          <div>
            <h4>เวลาเช็คอิน / เช็คเอาท์</h4>
            <p>Check-in <?= sanitize($settings['checkin_time'] ?? '14:00') ?> น. · Check-out <?= sanitize($settings['checkout_time'] ?? '12:00') ?> น.</p>
          </div>
        </div>
        <div class="loc-card" style="flex-direction:column;gap:.75rem">
          <div style="display:flex;gap:.75rem">
            <span class="loc-icon">🚌</span>
            <div>
              <h4>การเดินทาง</h4>
              <p>BTS / MRT บางแค · ทางด่วนเส้นบางนา-ตราด</p>
            </div>
          </div>
          <div class="transport-tags">
            <span class="transport-tag">🚇 BTS ใกล้บ้าน</span>
            <span class="transport-tag">🚗 ทางด่วน 5 นาที</span>
            <span class="transport-tag">🏪 ตลาด 2 นาที</span>
            <span class="transport-tag">🏬 Big C 10 นาที</span>
          </div>
        </div>
      </div>

      <div class="map-embed">
        <iframe
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3876.6!2d100.39227300000001!3d13.686481!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x30e2bd7880569d85%3A0xf41929654ef8aae4!2sLak%20Song%2C%20Bang%20Khae%2C%20Bangkok%2010160!5e0!3m2!1sth!2sth!4v1680000000000!5m2!1sth!2sth"
          allowfullscreen="" loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"
          title="RueangSonCity Location"></iframe>
      </div>
    </div>
  </div>
</section>

<!-- ====== REVIEWS ====== -->
<section class="section reviews-section" id="reviews">
  <div class="container">
    <div class="section-header">
      <div class="section-eyebrow">รีวิว</div>
      <h2 class="section-title">เสียงจาก<span class="hl">แขกผู้เข้าพัก</span></h2>
      <div class="divider-line"></div>
    </div>

    <div class="reviews-summary">
      <div style="text-align:center">
        <div class="rs-num"><?= number_format($avgRating['avg'] ?? 5, 1) ?></div>
        <div class="rs-stars">★★★★★</div>
        <div class="rs-label"><?= $avgRating['total'] ?> รีวิว</div>
      </div>
      <div class="rs-divider"></div>
      <div>
        <div style="font-size:.8rem;color:var(--text-muted);font-weight:600;margin-bottom:.6rem;text-transform:uppercase;letter-spacing:.5px">Platform</div>
        <div class="rs-platforms">
          <?php foreach ($platforms as $p): ?>
          <span class="rs-platform-chip">
            <?= platformIcon($p['platform']) ?> <?= platformLabel($p['platform']) ?>
            <strong style="color:var(--primary)"><?= $p['cnt'] ?></strong>
          </span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="reviews-grid">
      <?php foreach ($comments as $c): ?>
      <div class="review-card">
        <div class="review-header">
          <div class="reviewer-avatar"><?= mb_substr($c['author_name'], 0, 1, 'UTF-8') ?></div>
          <div class="reviewer-info">
            <strong><?= sanitize($c['author_name']) ?></strong>
            <div class="platform-badge">
              <span><?= platformIcon($c['platform']) ?></span>
              <span><?= platformLabel($c['platform']) ?></span>
            </div>
          </div>
          <div class="review-rating"><?= starRating($c['rating']) ?></div>
        </div>
        <p class="review-text"><?= sanitize($c['content']) ?></p>
        <div class="review-footer">
          <span class="review-time"><?= timeAgo($c['created_at']) ?></span>
          <button class="like-btn" onclick="likeComment(<?= $c['id'] ?>, this)">❤️ <?= $c['likes'] ?></button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="reviews-actions">
      <a href="/feed.php" class="btn-outline-dark">ดูรีวิวทั้งหมด</a>
      <a href="/feed.php#write-review" class="btn-primary">✏️ เขียนรีวิว</a>
    </div>
  </div>
</section>

<!-- ====== CONTACT CTA ====== -->
<section class="section contact-section" id="contact">
  <div class="container" style="position:relative;z-index:1">
    <div class="section-header">
      <div class="section-eyebrow">ติดต่อเรา</div>
      <h2 class="section-title" style="color:var(--white)">พร้อมต้อนรับคุณ<span style="color:var(--accent-light)">ทุกวัน</span></h2>
      <div class="divider-line" style="background:linear-gradient(90deg,var(--accent-light),var(--secondary-light))"></div>
    </div>

    <div class="contact-cards">
      <div class="contact-card">
        <span class="contact-card-icon">📞</span>
        <h4>โทรศัพท์</h4>
        <a href="tel:<?= sanitize($settings['phone'] ?? '') ?>"><?= sanitize($settings['phone'] ?? '') ?></a>
      </div>
      <div class="contact-card">
        <span class="contact-card-icon">💬</span>
        <h4>LINE</h4>
        <p><?= sanitize($settings['line_id'] ?? '') ?></p>
      </div>
      <div class="contact-card">
        <span class="contact-card-icon">📧</span>
        <h4>อีเมล</h4>
        <a href="mailto:<?= sanitize($settings['email'] ?? '') ?>"><?= sanitize($settings['email'] ?? '') ?></a>
      </div>
      <div class="contact-card">
        <span class="contact-card-icon">📍</span>
        <h4>ที่อยู่</h4>
        <p style="font-size:.82rem">999/1 หลักสอง บางแค กรุงเทพฯ 10160</p>
      </div>
    </div>

    <div class="contact-cta">
      <p>จองที่พักล่วงหน้าเพื่อความมั่นใจ หรือสอบถามข้อมูลเพิ่มเติมได้เลย</p>
      <div class="contact-btns">
        <a href="/booking.php" class="btn-cta-light">📅 จองที่พักเลย</a>
        <a href="https://line.me/ti/p/<?= sanitize($settings['line_id'] ?? '') ?>" class="btn-cta-line" target="_blank">💬 LINE: <?= sanitize($settings['line_id'] ?? '') ?></a>
      </div>
    </div>
  </div>
</section>

<!-- ====== FOOTER ====== -->
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="footer-logo">🌿 <?= $siteName ?></div>
        <p class="footer-desc"><?= sanitize($settings['tagline'] ?? '') ?></p>
        <div class="footer-social">
          <?php if (!empty($settings['facebook'])): ?>
          <a href="<?= sanitize($settings['facebook']) ?>" class="footer-soc-btn" target="_blank">f</a>
          <?php endif; ?>
          <?php if (!empty($settings['instagram'])): ?>
          <a href="<?= sanitize($settings['instagram']) ?>" class="footer-soc-btn" target="_blank">ig</a>
          <?php endif; ?>
        </div>
      </div>
      <div>
        <h4>เมนู</h4>
        <ul>
          <li><a href="#rooms">ห้องพัก</a></li>
          <li><a href="/booking.php">จองที่พัก</a></li>
          <li><a href="#pets">Pet Friendly</a></li>
          <li><a href="#amenities">สิ่งอำนวยความสะดวก</a></li>
          <li><a href="/feed.php">รีวิว</a></li>
          <li><a href="#gallery">แกลเลอรี่</a></li>
        </ul>
      </div>
      <div>
        <h4>ติดต่อ</h4>
        <ul>
          <li><a href="tel:<?= sanitize($settings['phone'] ?? '') ?>"><?= sanitize($settings['phone'] ?? '') ?></a></li>
          <li><a href="mailto:<?= sanitize($settings['email'] ?? '') ?>"><?= sanitize($settings['email'] ?? '') ?></a></li>
          <li><a href="#">LINE: <?= sanitize($settings['line_id'] ?? '') ?></a></li>
          <li><a href="#location">999/1 หลักสอง บางแค กรุงเทพฯ</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      © <?= date('Y') ?> <?= $siteName ?>. All rights reserved. ·
      <a href="/admin/">Admin</a> ·
      <a href="/feed.php">รีวิว</a>
    </div>
  </div>
</footer>

<!-- QUICK STATS CSS (inline since it's layout-specific) -->
<style>
.quick-stats-bar {
  background: var(--white);
  border-bottom: 1px solid var(--border-light);
  padding: 1.1rem 0;
  position: sticky; top: 60px; z-index: 90;
  box-shadow: var(--shadow-sm);
}
.qstat-inner {
  display: flex; align-items: center; justify-content: center;
  gap: 1.5rem; flex-wrap: wrap;
}
.qstat-item { text-align: center; }
.qstat-num { display: block; font-size: 1.1rem; font-weight: 800; color: var(--primary); line-height: 1.2; }
.qstat-label { font-size: .72rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: .4px; }
.qstat-div { width: 1px; height: 32px; background: var(--border); }
@media(max-width:640px){ .qstat-inner{gap:.875rem} .qstat-div{display:none} }
</style>

<script src="/assets/js/main.js"></script>
<script>
// Lightbox
function openLightbox(src, caption) {
  document.getElementById('lightboxImg').src = src;
  document.getElementById('lightboxCaption').textContent = caption;
  document.getElementById('lightbox').classList.add('active');
  document.body.style.overflow = 'hidden';
}
function closeLightbox() {
  document.getElementById('lightbox').classList.remove('active');
  document.body.style.overflow = '';
}

// Gallery filter
document.querySelectorAll('.gal-filter').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.gal-filter').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const cat = btn.dataset.cat;
    let shown = 0;
    document.querySelectorAll('#galleryGrid .gal-item').forEach(item => {
      const vis = cat === 'all' || item.dataset.cat === cat;
      item.style.display = vis ? '' : 'none';
      if (vis) shown++;
    });
  });
});

// Like
async function likeComment(id, btn) {
  try {
    const res = await fetch('/api/comments.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({action:'like', id})
    });
    const data = await res.json();
    if (data.likes !== undefined) btn.innerHTML = '❤️ ' + data.likes;
  } catch(e){}
}
</script>
</body>
</html>
