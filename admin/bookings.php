<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$settings = getAllSettings();
$db = getDB();
$message = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id = (int)$_POST['id'];
    if ($_POST['action'] === 'update_status' && in_array($_POST['status'], ['pending','confirmed','cancelled','completed'])) {
        $db->prepare("UPDATE bookings SET status = ? WHERE id = ?")->execute([$_POST['status'], $id]);
        $message = "อัพเดทสถานะสำเร็จ";
    } elseif ($_POST['action'] === 'delete') {
        $db->prepare("DELETE FROM bookings WHERE id = ?")->execute([$id]);
        $message = "ลบการจองสำเร็จ";
    }
}

$status = $_GET['status'] ?? 'all';
$search = sanitize($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = ["1=1"];
if ($status !== 'all') $where[] = "b.status = " . $db->quote($status);
if ($search) $where[] = "(b.guest_name LIKE " . $db->quote("%$search%") . " OR b.guest_email LIKE " . $db->quote("%$search%") . ")";
$whereStr = implode(' AND ', $where);

$total = $db->query("SELECT COUNT(*) as c FROM bookings b WHERE $whereStr")->fetch()['c'];
$totalPages = ceil($total / $perPage);

$bookings = $db->query("SELECT b.*, r.name as room_name FROM bookings b LEFT JOIN rooms r ON b.room_id = r.id WHERE $whereStr ORDER BY b.created_at DESC LIMIT $perPage OFFSET $offset")->fetchAll();

$statusCounts = [];
foreach (['all','pending','confirmed','cancelled','completed'] as $s) {
    $w = $s !== 'all' ? "WHERE status = '$s'" : '';
    $statusCounts[$s] = $db->query("SELECT COUNT(*) as c FROM bookings $w")->fetch()['c'];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>การจอง - Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/homestay/assets/css/style.css">
<link rel="stylesheet" href="/homestay/assets/css/admin.css">
</head>
<body class="admin-body">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-main">
  <?php include __DIR__ . '/partials/topbar.php'; ?>
  <div class="admin-content">
    <div class="page-header">
      <h1>📅 จัดการการจอง</h1>
      <p>รายการจองทั้งหมด</p>
    </div>

    <?php if ($message): ?><div class="alert-success">✅ <?= sanitize($message) ?></div><?php endif; ?>

    <!-- Status Filter Tabs -->
    <div class="filter-tabs-admin">
      <?php foreach ($statusCounts as $s => $cnt): ?>
      <a href="?status=<?= $s ?>&search=<?= urlencode($search) ?>" class="filter-tab-admin <?= $status === $s ? 'active' : '' ?>">
        <?php
        $labels = ['all'=>'ทั้งหมด','pending'=>'รอดำเนินการ','confirmed'=>'ยืนยันแล้ว','cancelled'=>'ยกเลิก','completed'=>'เสร็จสิ้น'];
        echo ($labels[$s] ?? $s) . " ($cnt)";
        ?>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Search -->
    <form method="GET" class="search-bar">
      <input type="hidden" name="status" value="<?= sanitize($status) ?>">
      <input type="text" name="search" placeholder="ค้นหาชื่อ, อีเมล..." value="<?= sanitize($search) ?>">
      <button type="submit">🔍 ค้นหา</button>
    </form>

    <!-- Table -->
    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>#</th>
            <th>ชื่อผู้จอง</th>
            <th>ห้องพัก</th>
            <th>เช็คอิน</th>
            <th>เช็คเอาท์</th>
            <th>คืน</th>
            <th>ยอดรวม</th>
            <th>สถานะ</th>
            <th>จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $b): ?>
          <tr>
            <td>HS<?= str_pad($b['id'], 6, '0', STR_PAD_LEFT) ?></td>
            <td>
              <strong><?= sanitize($b['guest_name']) ?></strong><br>
              <small><?= sanitize($b['guest_email']) ?></small><br>
              <?php if ($b['guest_phone']): ?><small>📞 <?= sanitize($b['guest_phone']) ?></small><?php endif; ?>
            </td>
            <td><?= sanitize($b['room_name'] ?? '-') ?></td>
            <td><?= formatDate($b['check_in']) ?></td>
            <td><?= formatDate($b['check_out']) ?></td>
            <td><?= (strtotime($b['check_out']) - strtotime($b['check_in'])) / 86400 ?></td>
            <td><?= formatPrice($b['total_price']) ?></td>
            <td><span class="status-badge status-<?= $b['status'] ?>"><?= sanitize($b['status']) ?></span></td>
            <td>
              <div class="action-btns">
                <button class="btn-sm btn-info" onclick="showBookingDetail(<?= htmlspecialchars(json_encode($b)) ?>)">👁</button>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="id" value="<?= $b['id'] ?>">
                  <input type="hidden" name="action" value="update_status">
                  <select name="status" onchange="this.form.submit()" class="status-select">
                    <?php foreach (['pending','confirmed','cancelled','completed'] as $s): ?>
                    <option value="<?= $s ?>" <?= $b['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
                <form method="POST" style="display:inline" onsubmit="return confirm('ลบการจองนี้?')">
                  <input type="hidden" name="id" value="<?= $b['id'] ?>">
                  <input type="hidden" name="action" value="delete">
                  <button type="submit" class="btn-sm btn-danger">🗑</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <a href="?status=<?= $status ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>" class="page-btn <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Booking Detail Modal -->
<div class="modal-overlay" id="bookingModal" onclick="this.style.display='none'">
  <div class="modal-box" onclick="event.stopPropagation()">
    <div class="modal-header">
      <h3>รายละเอียดการจอง</h3>
      <button onclick="document.getElementById('bookingModal').style.display='none'">✕</button>
    </div>
    <div class="modal-body" id="bookingDetail"></div>
  </div>
</div>

<script>
function showBookingDetail(b) {
  const nights = Math.round((new Date(b.check_out) - new Date(b.check_in)) / 86400000);
  document.getElementById('bookingDetail').innerHTML = `
    <div class="detail-grid">
      <div class="detail-row"><span>รหัสการจอง</span><strong>HS${String(b.id).padStart(6,'0')}</strong></div>
      <div class="detail-row"><span>ชื่อผู้จอง</span><strong>${b.guest_name}</strong></div>
      <div class="detail-row"><span>อีเมล</span><strong>${b.guest_email}</strong></div>
      <div class="detail-row"><span>โทรศัพท์</span><strong>${b.guest_phone || '-'}</strong></div>
      <div class="detail-row"><span>ห้องพัก</span><strong>${b.room_name || '-'}</strong></div>
      <div class="detail-row"><span>เช็คอิน</span><strong>${b.check_in}</strong></div>
      <div class="detail-row"><span>เช็คเอาท์</span><strong>${b.check_out}</strong></div>
      <div class="detail-row"><span>จำนวนคืน</span><strong>${nights} คืน</strong></div>
      <div class="detail-row"><span>จำนวนผู้เข้าพัก</span><strong>${b.guests} คน</strong></div>
      <div class="detail-row"><span>ยอดรวม</span><strong>฿${parseFloat(b.total_price).toLocaleString()}</strong></div>
      <div class="detail-row"><span>สถานะ</span><strong>${b.status}</strong></div>
      ${b.special_requests ? `<div class="detail-row full"><span>คำขอพิเศษ</span><p>${b.special_requests}</p></div>` : ''}
      <div class="detail-row"><span>วันที่จอง</span><strong>${b.created_at}</strong></div>
    </div>
  `;
  document.getElementById('bookingModal').style.display = 'flex';
}
</script>
<script src="/homestay/assets/js/admin.js"></script>
</body>
</html>
