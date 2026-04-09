<?php
session_start();
require 'auth.php';
require '../config/koneksi.php';

$title     = "CINEM4 Admin — Promosi";
$pageTitle = "Manajemen Promosi";

/* ── Hapus ── */
if (isset($_GET['delete'])) {
  $id   = (int)$_GET['delete'];
  $stmt = $conn->prepare("UPDATE promotions SET is_active = 0 WHERE id_promotion = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->close();
  header("Location: promotions.php?msg=deleted");
  exit;
}

$promos = $conn->query("SELECT * FROM promotions ORDER BY id_promotion DESC");

include 'partials/head.php';
include 'partials/sidebar.php';
?>

<div class="adm-main">
  <?php include 'partials/topbar.php'; ?>
  <div class="adm-content">

    <?php if (isset($_GET['msg'])): ?>
      <div class="adm-alert <?= $_GET['msg']==='deleted'?'adm-alert-danger':'adm-alert-success' ?>">
        <i class="bi bi-<?= $_GET['msg']==='deleted'?'trash':'check-circle' ?>-fill"></i>
        <?= $_GET['msg']==='deleted' ? 'Promosi berhasil dihapus.' : 'Promosi berhasil disimpan.' ?>
      </div>
    <?php endif; ?>

    <div class="adm-card">
      <div class="adm-card-header">
        <div class="adm-card-title">Daftar Promosi</div>
        <a href="promo-form.php" class="adm-btn adm-btn-primary">
          <i class="bi bi-plus-lg"></i> Tambah Promosi
        </a>
      </div>
      <div style="overflow-x:auto;">
        <table class="adm-table" data-dt='{}'>
          <thead>
            <tr>
              <th>#</th>
              <th>Gambar</th>
              <th>Judul</th>
              <th>Periode</th>
              <th>Status</th>
              <th style="width:100px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($promos && $promos->num_rows > 0):
              $no = 1;
              while ($p = $promos->fetch_assoc()): ?>
            <tr>
              <td style="color:rgba(255,255,255,.35);"><?= $no++ ?></td>
              <td>
                <?php if (!empty($p['image'])): ?>
                  <img src="../<?= htmlspecialchars($p['image']) ?>"
                    style="height:44px;width:80px;object-fit:cover;border-radius:8px;">
                <?php else: ?>
                  <div style="width:80px;height:44px;background:rgba(255,255,255,.06);
                    border-radius:8px;display:grid;place-items:center;color:rgba(255,255,255,.25);">
                    <i class="bi bi-image"></i>
                  </div>
                <?php endif; ?>
              </td>
              <td class="fw-semibold" style="color:#fff;"><?= htmlspecialchars($p['title']) ?></td>
              <td style="font-size:12px;">
                <?php if ($p['start_date'] && $p['end_date']): ?>
                  <?= date('d M Y', strtotime($p['start_date'])) ?> —
                  <?= date('d M Y', strtotime($p['end_date'])) ?>
                <?php elseif ($p['start_date']): ?>
                  Mulai <?= date('d M Y', strtotime($p['start_date'])) ?>
                <?php else: ?>
                  <span style="color:rgba(255,255,255,.30);">—</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($p['is_active']): ?>
                  <span class="adm-badge adm-badge-green">Aktif</span>
                <?php else: ?>
                  <span class="adm-badge adm-badge-gray">Nonaktif</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="d-flex gap-2">
                  <a href="promo-form.php?id=<?= $p['id_promotion'] ?>"
                     class="adm-btn adm-btn-outline adm-btn-sm">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <a href="promotions.php?delete=<?= $p['id_promotion'] ?>"
                     class="adm-btn adm-btn-danger adm-btn-sm"
                     onclick="return confirm('Hapus promosi ini?')">
                    <i class="bi bi-trash"></i>
                  </a>
                </div>
              </td>
            </tr>
            <?php endwhile; else: ?>
            <tr>
              <td colspan="6" class="text-center"
                style="padding:32px;color:rgba(255,255,255,.35);">
                Belum ada promosi.
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include 'partials/footer.php'; ?>