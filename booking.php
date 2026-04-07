<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$settings = getAllSettings();
$db = getDB();
$rooms = $db->query("SELECT * FROM rooms WHERE is_active = 1 ORDER BY price ASC")->fetchAll();

$selectedRoom = isset($_GET['room_id']) ? (int)$_GET['room_id'] : null;
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

$success = false;
$error = '';
$bookingRef = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roomId = (int)($_POST['room_id'] ?? 0);
    $guestName = sanitize($_POST['guest_name'] ?? '');
    $guestEmail = sanitize($_POST['guest_email'] ?? '');
    $guestPhone = sanitize($_POST['guest_phone'] ?? '');
    $checkIn = $_POST['check_in'] ?? '';
    $checkOut = $_POST['check_out'] ?? '';
    $guests = (int)($_POST['guests'] ?? 1);
    $special = sanitize($_POST['special_requests'] ?? '');

    if (!$roomId || !$guestName || !$guestEmail || !$checkIn || !$checkOut) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } elseif ($checkIn >= $checkOut) {
        $error = 'วันเช็คเอาท์ต้องหลังจากวันเช็คอิน';
    } elseif ($checkIn < $today) {
        $error = 'ไม่สามารถจองย้อนหลังได้';
    } elseif (!filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'รูปแบบอีเมลไม่ถูกต้อง';
    } else {
        if (!isRoomAvailable($roomId, $checkIn, $checkOut)) {
            $error = 'ห้องพักไม่ว่างในช่วงเวลาที่เลือก กรุณาเลือกวันอื่น';
        } else {
            $room = $db->prepare("SELECT * FROM rooms WHERE id = ?")->execute([$roomId]) ? null : null;
            $stmt = $db->prepare("SELECT * FROM rooms WHERE id = ?");
            $stmt->execute([$roomId]);
            $room = $stmt->fetch();

            $nights = (strtotime($checkOut) - strtotime($checkIn)) / 86400;
            $totalPrice = $room['price'] * $nights;

            $insert = $db->prepare("INSERT INTO bookings (room_id, guest_name, guest_email, guest_phone, check_in, check_out, guests, total_price, special_requests) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert->execute([$roomId, $guestName, $guestEmail, $guestPhone, $checkIn, $checkOut, $guests, $totalPrice, $special]);

            $bookingId = $db->lastInsertId();
            $bookingRef = 'HS' . str_pad($bookingId, 6, '0', STR_PAD_LEFT);

            $db->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?")->execute([$bookingId]);
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>จองที่พัก — <?= sanitize($settings['homestay_name'] ?? 'RueangSonCity') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/homestay/assets/css/style.css">
</head>
<body class="booking-page">

<nav class="navbar scrolled">
  <div class="nav-container">
    <a href="/homestay/" class="nav-brand"><span class="brand-icon">🌿</span><span><?= sanitize($settings['homestay_name'] ?? 'RueangSonCity') ?></span></a>
    <ul class="nav-links">
      <li><a href="/homestay/">หน้าหลัก</a></li>
      <li><a href="/homestay/#rooms">ห้องพัก</a></li>
      <li><a href="/homestay/#pets">Pet Friendly</a></li>
      <li><a href="/homestay/feed.php">รีวิว</a></li>
    </ul>
  </div>
</nav>

<div class="page-hero booking-hero">
  <h1>📅 จองที่พัก</h1>
  <p>เลือกห้องพักและวันที่ต้องการ · สัตว์เลี้ยงมาได้เลย 🐾</p>
</div>

<div class="booking-container">
  <?php if ($success): ?>
  <div class="booking-success">
    <div class="success-icon">✅</div>
    <h2>จองที่พักสำเร็จ!</h2>
    <div class="booking-ref">รหัสการจอง: <strong><?= $bookingRef ?></strong></div>
    <p>เราจะติดต่อกลับเพื่อยืนยันการจองไปที่อีเมล <strong><?= sanitize($guestEmail) ?></strong></p>
    <p>หรือสามารถติดต่อสอบถามได้ที่ <?= sanitize($settings['phone'] ?? '') ?></p>
    <div class="success-actions">
      <a href="/homestay/" class="btn-primary">กลับหน้าหลัก</a>
      <a href="/homestay/booking.php" class="btn-outline-dark">จองเพิ่มเติม</a>
    </div>
  </div>
  <?php else: ?>

  <div class="booking-layout">
    <!-- Booking Form -->
    <div class="booking-form-wrap">
      <h2 class="form-title">ข้อมูลการจอง</h2>
      <?php if ($error): ?>
      <div class="alert-error">⚠️ <?= sanitize($error) ?></div>
      <?php endif; ?>

      <form method="POST" class="booking-form" id="bookingForm">
        <div class="form-group">
          <label>เลือกห้องพัก <span class="required">*</span></label>
          <select name="room_id" id="roomSelect" required onchange="updatePricePreview()">
            <option value="">-- เลือกห้องพัก --</option>
            <?php foreach ($rooms as $r): ?>
            <option value="<?= $r['id'] ?>" data-price="<?= $r['price'] ?>" data-capacity="<?= $r['capacity'] ?>"
              <?= $selectedRoom == $r['id'] ? 'selected' : '' ?>>
              <?= sanitize($r['name']) ?> - <?= formatPrice($r['price']) ?>/คืน (สูงสุด <?= $r['capacity'] ?> คน)
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>วันเช็คอิน <span class="required">*</span></label>
            <input type="date" name="check_in" id="checkIn" min="<?= $today ?>"
              value="<?= isset($_POST['check_in']) ? $_POST['check_in'] : $today ?>" required onchange="updatePricePreview()">
          </div>
          <div class="form-group">
            <label>วันเช็คเอาท์ <span class="required">*</span></label>
            <input type="date" name="check_out" id="checkOut" min="<?= $tomorrow ?>"
              value="<?= isset($_POST['check_out']) ? $_POST['check_out'] : $tomorrow ?>" required onchange="updatePricePreview()">
          </div>
        </div>

        <div class="form-group">
          <label>จำนวนผู้เข้าพัก <span class="required">*</span></label>
          <select name="guests" id="guestsSelect">
            <?php for ($i = 1; $i <= 10; $i++): ?>
            <option value="<?= $i ?>" <?= (isset($_POST['guests']) && $_POST['guests'] == $i) ? 'selected' : ($i==1?'selected':'') ?>><?= $i ?> คน</option>
            <?php endfor; ?>
          </select>
        </div>

        <div id="pricePreview" class="price-preview" style="display:none">
          <div class="price-row">
            <span id="priceDetail"></span>
            <span id="totalPrice" class="total-price"></span>
          </div>
        </div>

        <div class="form-divider">ข้อมูลผู้จอง</div>

        <div class="form-group">
          <label>ชื่อ-นามสกุล <span class="required">*</span></label>
          <input type="text" name="guest_name" placeholder="กรอกชื่อ-นามสกุล" value="<?= isset($_POST['guest_name']) ? sanitize($_POST['guest_name']) : '' ?>" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>อีเมล <span class="required">*</span></label>
            <input type="email" name="guest_email" placeholder="email@example.com" value="<?= isset($_POST['guest_email']) ? sanitize($_POST['guest_email']) : '' ?>" required>
          </div>
          <div class="form-group">
            <label>เบอร์โทรศัพท์</label>
            <input type="tel" name="guest_phone" placeholder="08X-XXX-XXXX" value="<?= isset($_POST['guest_phone']) ? sanitize($_POST['guest_phone']) : '' ?>">
          </div>
        </div>

        <div class="form-group">
          <label>คำขอพิเศษ</label>
          <textarea name="special_requests" rows="3" placeholder="เช่น ต้องการเตียงเสริม, อาหารมังสวิรัติ, แพ้อาหาร..."><?= isset($_POST['special_requests']) ? sanitize($_POST['special_requests']) : '' ?></textarea>
        </div>

        <div class="form-terms">
          <input type="checkbox" id="terms" required>
          <label for="terms">ฉันยอมรับ <a href="#">เงื่อนไขการจอง</a> และ <a href="#">นโยบายการยกเลิก</a></label>
        </div>

        <button type="submit" class="btn-submit-book">ยืนยันการจอง</button>
      </form>
    </div>

    <!-- Sidebar -->
    <div class="booking-sidebar">
      <div class="checkin-card">
        <h3>ข้อมูลการเช็คอิน</h3>
        <div class="info-row"><span>🕐 เช็คอิน</span><strong><?= sanitize($settings['checkin_time'] ?? '14:00') ?> น.</strong></div>
        <div class="info-row"><span>🕛 เช็คเอาท์</span><strong><?= sanitize($settings['checkout_time'] ?? '12:00') ?> น.</strong></div>
      </div>

      <div class="rooms-sidebar">
        <h3>ห้องพักทั้งหมด</h3>
        <?php foreach ($rooms as $r): ?>
        <div class="room-mini-card <?= $selectedRoom == $r['id'] ? 'selected' : '' ?>" onclick="selectRoom(<?= $r['id'] ?>)">
          <div class="room-mini-img">
            <?php if (!empty($r['cover_image'])): ?>
            <img src="/homestay/uploads/rooms/<?= sanitize($r['cover_image']) ?>" alt="">
            <?php else: ?>
            <span>🏠</span>
            <?php endif; ?>
          </div>
          <div class="room-mini-info">
            <strong><?= sanitize($r['name']) ?></strong>
            <span><?= formatPrice($r['price']) ?>/คืน</span>
            <small>👥 สูงสุด <?= $r['capacity'] ?> คน</small>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="contact-card-box">
        <h3>ต้องการความช่วยเหลือ?</h3>
        <a href="tel:<?= sanitize($settings['phone'] ?? '') ?>" class="contact-btn">📞 <?= sanitize($settings['phone'] ?? '') ?></a>
        <a href="https://line.me/ti/p/<?= sanitize($settings['line_id'] ?? '') ?>" class="contact-btn line-btn">💬 LINE: <?= sanitize($settings['line_id'] ?? '') ?></a>
      </div>

    </div>
  </div>
  <?php endif; ?>
</div>

<script>
function selectRoom(id) {
  document.getElementById('roomSelect').value = id;
  document.querySelectorAll('.room-mini-card').forEach(c => c.classList.remove('selected'));
  event.currentTarget.classList.add('selected');
  updatePricePreview();
}

function updatePricePreview() {
  const sel = document.getElementById('roomSelect');
  const opt = sel.options[sel.selectedIndex];
  const checkIn = document.getElementById('checkIn').value;
  const checkOut = document.getElementById('checkOut').value;

  if (!opt.value || !checkIn || !checkOut) {
    document.getElementById('pricePreview').style.display = 'none';
    return;
  }

  const price = parseFloat(opt.dataset.price) || 0;
  const d1 = new Date(checkIn), d2 = new Date(checkOut);
  const nights = Math.round((d2 - d1) / 86400000);

  if (nights <= 0) return;

  const total = price * nights;
  document.getElementById('priceDetail').textContent = `฿${price.toLocaleString()} × ${nights} คืน`;
  document.getElementById('totalPrice').textContent = `รวม ฿${total.toLocaleString()}`;
  document.getElementById('pricePreview').style.display = 'flex';

  // Update check_out min
  const nextDay = new Date(checkIn);
  nextDay.setDate(nextDay.getDate() + 1);
  document.getElementById('checkOut').min = nextDay.toISOString().split('T')[0];
}

// Availability check
document.getElementById('checkIn')?.addEventListener('change', function() {
  const nextDay = new Date(this.value);
  nextDay.setDate(nextDay.getDate() + 1);
  const checkOut = document.getElementById('checkOut');
  checkOut.min = nextDay.toISOString().split('T')[0];
  if (checkOut.value <= this.value) checkOut.value = nextDay.toISOString().split('T')[0];
  updatePricePreview();
});

updatePricePreview();
</script>
</body>
</html>
