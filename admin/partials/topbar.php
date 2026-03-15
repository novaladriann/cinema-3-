<?php if (!isset($pageTitle)) $pageTitle = 'Dashboard'; ?>
<div class="adm-topbar">
  <div class="d-flex align-items-center gap-3">
    <button class="adm-hamburger" onclick="openSidebar()">
      <i class="bi bi-list"></i>
    </button>
    <div class="adm-topbar-title"><?= htmlspecialchars($pageTitle) ?></div>
  </div>
  <div class="adm-topbar-right">
    <span class="adm-topbar-name d-none d-md-inline">
      <?= htmlspecialchars(currentAdmin()['name'] ?? '') ?>
    </span>
    <a href="logout.php" class="adm-btn adm-btn-outline adm-btn-sm">
      <i class="bi bi-box-arrow-left"></i>
      <span class="d-none d-md-inline">Logout</span>
    </a>
  </div>
</div>