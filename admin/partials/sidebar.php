<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
function navItem($href, $icon, $label, $current) {
    $page = basename($href, '.php');
    $active = ($current === $page || ($current === 'index' && $page === 'index')) ? 'active' : '';
    echo "<li><a href=\"$href\" class=\"$active\">$icon <span>$label</span></a></li>";
}
?>
<aside class="admin-sidebar" id="adminSidebar">
  <div class="sidebar-header">
    <a href="/admin/" class="sidebar-brand">
      <span class="brand-icon">🌿</span>
      <span class="brand-text">Homestay Admin</span>
    </a>
    <button class="sidebar-close" onclick="toggleSidebar()">✕</button>
  </div>
  <nav class="sidebar-nav">
    <ul>
      <?php navItem('/admin/index.php', '📊', 'Dashboard', $currentPage); ?>
      <?php navItem('/admin/bookings.php', '📅', 'การจอง', $currentPage); ?>
      <?php navItem('/admin/rooms.php', '🏠', 'ห้องพัก', $currentPage); ?>
      <?php navItem('/admin/upload.php', '📷', 'อัพโหลดรูปภาพ', $currentPage); ?>
      <?php navItem('/admin/gallery.php', '🖼️', 'จัดการแกลเลอรี่', $currentPage); ?>
      <?php navItem('/admin/comments.php', '💬', 'รีวิว & คอมเมนต์', $currentPage); ?>
      <?php navItem('/admin/settings.php', '⚙️', 'ตั้งค่า', $currentPage); ?>
    </ul>
  </nav>
  <div class="sidebar-footer">
    <a href="/" target="_blank" class="sidebar-view-web">🌐 ดูเว็บไซต์</a>
    <a href="/admin/logout.php" class="sidebar-logout">🚪 ออกจากระบบ</a>
  </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
