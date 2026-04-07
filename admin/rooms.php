<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$settings = getAllSettings();
$db = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add' || $action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = sanitize($_POST['name']);
        $desc = sanitize($_POST['description']);
        $price = (float)$_POST['price'];
        $capacity = (int)$_POST['capacity'];
        $amenities = sanitize($_POST['amenities']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($action === 'add') {
            $db->prepare("INSERT INTO rooms (name, description, price, capacity, amenities, is_active) VALUES (?, ?, ?, ?, ?, ?)")
               ->execute([$name, $desc, $price, $capacity, $amenities, $is_active]);
            $message = "เพิ่มห้องพักสำเร็จ";
        } else {
            $db->prepare("UPDATE rooms SET name=?, description=?, price=?, capacity=?, amenities=?, is_active=? WHERE id=?")
               ->execute([$name, $desc, $price, $capacity, $amenities, $is_active, $id]);
            $message = "อัพเดทห้องพักสำเร็จ";
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $db->prepare("DELETE FROM rooms WHERE id = ?")->execute([$id]);
        $message = "ลบห้องพักสำเร็จ";
    } elseif ($action === 'toggle') {
        $id = (int)$_POST['id'];
        $db->prepare("UPDATE rooms SET is_active = 1 - is_active WHERE id = ?")->execute([$id]);
        $message = "อัพเดทสถานะสำเร็จ";
    }
}

$rooms = $db->query("SELECT r.*, COUNT(b.id) as booking_count FROM rooms r LEFT JOIN bookings b ON r.id = b.room_id GROUP BY r.id ORDER BY r.id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ห้องพัก - Admin</title>
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
      <div><h1>🏠 จัดการห้องพัก</h1><p>เพิ่ม แก้ไข จัดการห้องพักทั้งหมด</p></div>
      <button class="btn-primary" onclick="openRoomModal()">+ เพิ่มห้องพัก</button>
    </div>

    <?php if ($message): ?><div class="alert-success">✅ <?= sanitize($message) ?></div><?php endif; ?>

    <div class="rooms-admin-grid">
      <?php foreach ($rooms as $r): ?>
      <div class="room-admin-card <?= !$r['is_active'] ? 'inactive' : '' ?>">
        <div class="room-admin-img">
          <?php if (!empty($r['cover_image'])): ?>
          <img src="/uploads/rooms/<?= sanitize($r['cover_image']) ?>" alt="">
          <?php else: ?>
          <div class="no-room-img">🏠</div>
          <?php endif; ?>
          <?php if (!$r['is_active']): ?><div class="inactive-overlay">ปิดให้บริการ</div><?php endif; ?>
        </div>
        <div class="room-admin-body">
          <h3><?= sanitize($r['name']) ?></h3>
          <p><?= sanitize($r['description']) ?></p>
          <div class="room-admin-meta">
            <span>💰 ฿<?= number_format($r['price']) ?>/คืน</span>
            <span>👥 <?= $r['capacity'] ?> คน</span>
            <span>📅 <?= $r['booking_count'] ?> การจอง</span>
          </div>
          <div class="room-admin-actions">
            <button class="btn-sm btn-info" onclick="openRoomModal(<?= htmlspecialchars(json_encode($r)) ?>)">✏️ แก้ไข</button>
            <a href="/admin/upload.php" class="btn-sm btn-secondary">📷 รูปปก</a>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="toggle">
              <input type="hidden" name="id" value="<?= $r['id'] ?>">
              <button type="submit" class="btn-sm <?= $r['is_active'] ? 'btn-warning' : 'btn-success' ?>">
                <?= $r['is_active'] ? '🔴 ปิด' : '🟢 เปิด' ?>
              </button>
            </form>
            <form method="POST" style="display:inline" onsubmit="return confirm('ลบห้องพักนี้?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $r['id'] ?>">
              <button type="submit" class="btn-sm btn-danger">🗑</button>
            </form>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Room Modal -->
<div class="modal-overlay" id="roomModal" onclick="this.style.display='none'">
  <div class="modal-box" onclick="event.stopPropagation()">
    <div class="modal-header">
      <h3 id="roomModalTitle">เพิ่มห้องพักใหม่</h3>
      <button onclick="document.getElementById('roomModal').style.display='none'">✕</button>
    </div>
    <form method="POST" class="modal-form">
      <input type="hidden" name="action" id="roomAction" value="add">
      <input type="hidden" name="id" id="roomId">
      <div class="form-row">
        <div class="form-group">
          <label>ชื่อห้องพัก *</label>
          <input type="text" name="name" id="roomName" required>
        </div>
        <div class="form-group">
          <label>ราคาต่อคืน (บาท) *</label>
          <input type="number" name="price" id="roomPrice" min="0" step="50" required>
        </div>
      </div>
      <div class="form-group">
        <label>คำอธิบาย</label>
        <textarea name="description" id="roomDesc" rows="3"></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>ความจุสูงสุด (คน)</label>
          <input type="number" name="capacity" id="roomCapacity" min="1" value="2">
        </div>
        <div class="form-group">
          <label>
            <input type="checkbox" name="is_active" id="roomActive" checked value="1"> เปิดให้บริการ
          </label>
        </div>
      </div>
      <div class="form-group">
        <label>สิ่งอำนวยความสะดวก (คั่นด้วยจุลภาค)</label>
        <textarea name="amenities" id="roomAmenities" rows="2" placeholder="แอร์, WiFi, ห้องน้ำในตัว, ทีวี..."></textarea>
      </div>
      <button type="submit" class="btn-submit-book">บันทึกห้องพัก</button>
    </form>
  </div>
</div>

<script>
function openRoomModal(room) {
  if (room) {
    document.getElementById('roomModalTitle').textContent = 'แก้ไขห้องพัก';
    document.getElementById('roomAction').value = 'edit';
    document.getElementById('roomId').value = room.id;
    document.getElementById('roomName').value = room.name;
    document.getElementById('roomPrice').value = room.price;
    document.getElementById('roomDesc').value = room.description || '';
    document.getElementById('roomCapacity').value = room.capacity;
    document.getElementById('roomAmenities').value = room.amenities || '';
    document.getElementById('roomActive').checked = room.is_active == 1;
  } else {
    document.getElementById('roomModalTitle').textContent = 'เพิ่มห้องพักใหม่';
    document.getElementById('roomAction').value = 'add';
    document.getElementById('roomId').value = '';
    document.getElementById('roomName').value = '';
    document.getElementById('roomPrice').value = '';
    document.getElementById('roomDesc').value = '';
    document.getElementById('roomCapacity').value = 2;
    document.getElementById('roomAmenities').value = '';
    document.getElementById('roomActive').checked = true;
  }
  document.getElementById('roomModal').style.display = 'flex';
}
</script>
<script src="/assets/js/admin.js"></script>
</body>
</html>
