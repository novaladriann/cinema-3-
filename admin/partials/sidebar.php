<?php
$adm       = currentAdmin();
$initials  = strtoupper(substr($adm['name'] ?? 'A', 0, 1));
$adminPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Overlay mobile -->
<div class="adm-overlay" id="admOverlay" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<aside class="adm-sidebar" id="admSidebar">

  <!-- Logo -->
  <a class="adm-logo" href="index.php">
    <img src="../assets/img/logo-cinem4.png" alt="CINEM4" height="32">
    <span class="adm-logo-badge">Admin</span>
  </a>

  <!-- Nav -->
  <nav class="adm-nav">

    <div class="adm-nav-label">Main</div>
    <a href="index.php"
       class="adm-nav-link <?= $adminPage === 'index.php' ? 'active' : '' ?>">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="adm-nav-label mt-2">Konten</div>
    <a href="movies.php"
       class="adm-nav-link <?= $adminPage === 'movies.php' ? 'active' : '' ?>">
      <i class="bi bi-film"></i> Film
    </a>
    <a href="schedules.php"
       class="adm-nav-link <?= $adminPage === 'schedules.php' ? 'active' : '' ?>">
      <i class="bi bi-calendar3"></i> Jadwal
    </a>
    <a href="cinemas.php"
       class="adm-nav-link <?= $adminPage === 'cinemas.php' ? 'active' : '' ?>">
      <i class="bi bi-building"></i> Bioskop
    </a>
    <a href="promotions.php"
       class="adm-nav-link <?= $adminPage === 'promotions.php' ? 'active' : '' ?>">
      <i class="bi bi-tag"></i> Promosi
    </a>

    <div class="adm-nav-label mt-2">Transaksi</div>
    <a href="bookings.php"
       class="adm-nav-link <?= $adminPage === 'bookings.php' ? 'active' : '' ?>">
      <i class="bi bi-ticket-perforated"></i> Booking
    </a>

    <div class="adm-nav-label mt-2">Pengguna</div>
    <a href="users.php"
       class="adm-nav-link <?= $adminPage === 'users.php' ? 'active' : '' ?>">
      <i class="bi bi-people"></i> Users
    </a>

    <?php if (isSuperAdmin()): ?>
    <div class="adm-nav-label mt-2">Pengaturan</div>
    <a href="admins.php"
       class="adm-nav-link <?= $adminPage === 'admins.php' ? 'active' : '' ?>">
      <i class="bi bi-shield-lock"></i> Admin
    </a>
    <?php endif; ?>

    <!-- Link ke halaman publik -->
    <div class="adm-nav-label mt-2">Lainnya</div>
    <a href="../index.php" target="_blank" class="adm-nav-link">
      <i class="bi bi-box-arrow-up-right"></i> Lihat Website
    </a>

  </nav>

  <!-- Footer sidebar: info user + logout -->
  <div class="adm-sidebar-footer">
    <div class="adm-user-info">
      <div class="adm-user-avatar"><?= htmlspecialchars($initials) ?></div>
      <div>
        <div class="adm-user-name"><?= htmlspecialchars($adm['name']) ?></div>
        <div class="adm-user-role"><?= htmlspecialchars(ucfirst($adm['role'])) ?></div>
      </div>
    </div>
    <a href="logout.php" class="adm-logout">
      <i class="bi bi-box-arrow-left"></i> Keluar
    </a>
  </div>

</aside>