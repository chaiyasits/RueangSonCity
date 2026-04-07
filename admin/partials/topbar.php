<?php
require_once __DIR__ . '/../../includes/auth.php';
startSession();
?>
<header class="admin-topbar">
  <button class="topbar-toggle" onclick="toggleSidebar()">☰</button>
  <div class="topbar-right">
    <a href="/" target="_blank" class="topbar-link">🌐 ดูเว็บ</a>
    <div class="topbar-user">
      <span>👤 <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></span>
    </div>
    <a href="/admin/logout.php" class="topbar-logout">ออกจากระบบ</a>
  </div>
</header>
