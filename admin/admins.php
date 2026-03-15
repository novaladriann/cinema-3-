<?php
session_start();
require 'auth.php';
require '../config/koneksi.php';

/* Hanya superadmin yang boleh akses */
if (!isSuperAdmin()) {
  header("Location: index.php");
  exit;
}

$title     = "CINEM4 Admin — Kelola Admin";
$pageTitle = "Kelola Admin";

/* ── Hapus ── */
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  if ($id === (int)currentAdmin()['id']) {
    header("Location: admins.php?msg=self_delete");
    exit;
  }
  $stmt = $conn->prepare("DELETE FROM admins WHERE id_admin = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->close();
  header("Location: admins.php?msg=deleted");
  exit;
}

/* ── Toggle aktif ── */
if (isset($_GET['toggle'])) {
  $id = (int)$_GET['toggle'];
  if ($id !== (int)currentAdmin()['id']) {
    $stmt = $conn->prepare("UPDATE admins SET is_active = IF(is_active=1,0,1) WHERE id_admin=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
  }
  header("Location: admins.php");
  exit;
}

/* ── Ambil data edit ── */
$editId   = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editData = null;
if ($editId > 0) {
  $stmt = $conn->prepare("SELECT * FROM admins WHERE id_admin = ? LIMIT 1");
  $stmt->bind_param("i", $editId);
  $stmt->execute();
  $editData = $stmt->get_result()->fetch_assoc();
  $stmt->close();
}

$errors = [];

/* ── Proses form ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $postId   = (int)($_POST['id']      ?? 0);
  $name     = trim($_POST['name']     ?? '');
  $email    = trim($_POST['email']    ?? '');
  $role     = trim($_POST['role']     ?? 'admin');
  $password = trim($_POST['password'] ?? '');
  $isActive = isset($_POST['is_active']) ? 1 : 0;

  if ($name === '')  $errors[] = 'Nama wajib diisi.';
  if ($email === '') $errors[] = 'Email wajib diisi.';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
  if ($postId === 0 && $password === '') $errors[] = 'Password wajib diisi untuk admin baru.';
  if ($password !== '' && strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';

  /* Cek email duplikat */
  if (empty($errors)) {
    $chk = $conn->prepare("SELECT id_admin FROM admins WHERE email = ? AND id_admin != ? LIMIT 1");
    $chk->bind_param("si", $email, $postId);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) $errors[] = 'Email sudah digunakan admin lain.';
    $chk->close();
  }

  if (empty($errors)) {
    if ($postId > 0) {
      /* Edit — password opsional */
      if ($password !== '') {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE admins SET name=?,email=?,role=?,password=?,is_active=? WHERE id_admin=?");
        $stmt->bind_param("ssssii", $name, $email, $role, $hash, $isActive, $postId);
      } else {
        $stmt = $conn->prepare("UPDATE admins SET name=?,email=?,role=?,is_active=? WHERE id_admin=?");
        $stmt->bind_param("sssii", $name, $email, $role, $isActive, $postId);
      }
    } else {
      /* Tambah baru */
      $hash = password_hash($password, PASSWORD_BCRYPT);
      $stmt = $conn->prepare("INSERT INTO admins (name,email,password,role,is_active) VALUES (?,?,?,?,1)");
      $stmt->bind_param("ssss", $name, $email, $hash, $role);
    }

    if ($stmt->execute()) {
      header("Location: admins.php?msg=saved");
      exit;
    } else {
      $errors[] = 'Gagal menyimpan: ' . $conn->error;
    }
    $stmt->close();
  }
}

$admins = $conn->query("SELECT * FROM admins ORDER BY id_admin ASC");

include 'partials/head.php';
include 'partials/sidebar.php';
?>

<div class="adm-main">
  <?php include 'partials/topbar.php'; ?>
  <div class="adm-content">

    <?php if (isset($_GET['msg'])): ?>
      <?php
        $msgMap = [
          'saved'       => ['success', 'check-circle',      'Admin berhasil disimpan.'],
          'deleted'     => ['danger',  'trash',              'Admin berhasil dihapus.'],
          'self_delete' => ['danger',  'exclamation-circle', 'Tidak bisa menghapus akun sendiri.'],
        ];
        [$type, $icon, $text] = $msgMap[$_GET['msg']] ?? ['success','check-circle','OK'];
      ?>
      <div class="adm-alert adm-alert-<?= $type ?> mb-3">
        <i class="bi bi-<?= $icon ?>-fill"></i> <?= $text ?>
      </div>
    <?php endif; ?>

    <div class="row g-3">

      <!-- Form tambah/edit -->
      <div class="col-12 col-lg-4">
        <div class="adm-card">
          <div class="adm-card-header">
            <div class="adm-card-title">
              <?= $editData ? 'Edit Admin' : 'Tambah Admin' ?>
            </div>
          </div>
          <div class="adm-card-body">

            <?php if (!empty($errors)): ?>
              <div class="adm-alert adm-alert-danger mb-3">
                <i class="bi bi-exclamation-circle-fill"></i>
                <div><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
              </div>
            <?php endif; ?>

            <form method="post">
              <input type="hidden" name="id" value="<?= $editData['id_admin'] ?? 0 ?>">

              <div class="mb-3">
                <label class="adm-form-label">Nama <span style="color:#ff8a95;">*</span></label>
                <input type="text" name="name" class="adm-form-control" required
                  value="<?= htmlspecialchars($editData['name'] ?? $_POST['name'] ?? '') ?>">
              </div>

              <div class="mb-3">
                <label class="adm-form-label">Email <span style="color:#ff8a95;">*</span></label>
                <input type="email" name="email" class="adm-form-control" required
                  value="<?= htmlspecialchars($editData['email'] ?? $_POST['email'] ?? '') ?>">
              </div>

              <div class="mb-3">
                <label class="adm-form-label">Role</label>
                <select name="role" class="adm-form-control">
                  <option value="admin"
                    <?= ($editData['role'] ?? 'admin') === 'admin' ? 'selected' : '' ?>>
                    Admin
                  </option>
                  <option value="superadmin"
                    <?= ($editData['role'] ?? '') === 'superadmin' ? 'selected' : '' ?>>
                    Super Admin
                  </option>
                </select>
              </div>

              <div class="mb-3">
                <label class="adm-form-label">
                  Password
                  <?php if ($editData): ?>
                    <span style="color:rgba(255,255,255,.35);font-weight:400;">
                      (kosongkan jika tidak diubah)
                    </span>
                  <?php else: ?>
                    <span style="color:#ff8a95;">*</span>
                  <?php endif; ?>
                </label>
                <div style="position:relative;">
                  <input type="password" name="password" id="passInput"
                    class="adm-form-control" autocomplete="new-password"
                    placeholder="<?= $editData ? '••••••••' : 'Min. 6 karakter' ?>"
                    style="padding-right:42px;">
                  <button type="button" onclick="togglePass()"
                    style="position:absolute;top:50%;right:12px;transform:translateY(-50%);
                    background:none;border:none;color:rgba(255,255,255,.4);cursor:pointer;">
                    <i class="bi bi-eye" id="passIcon"></i>
                  </button>
                </div>
              </div>

              <?php if ($editData): ?>
              <div class="mb-3">
                <label class="d-flex align-items-center gap-2" style="cursor:pointer;">
                  <input type="checkbox" name="is_active" value="1"
                    <?= ($editData['is_active'] ?? 1) ? 'checked' : '' ?>
                    style="width:16px;height:16px;accent-color:var(--c4-primary);">
                  <span class="adm-form-label mb-0">Akun Aktif</span>
                </label>
              </div>
              <?php endif; ?>

              <div class="d-flex gap-2">
                <button type="submit" class="adm-btn adm-btn-primary">
                  <i class="bi bi-save"></i>
                  <?= $editData ? 'Simpan' : 'Tambah' ?>
                </button>
                <?php if ($editData): ?>
                  <a href="admins.php" class="adm-btn adm-btn-outline">Batal</a>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Tabel admin -->
      <div class="col-12 col-lg-8">
        <div class="adm-card">
          <div class="adm-card-header">
            <div class="adm-card-title">Daftar Admin</div>
          </div>
          <div style="overflow-x:auto;">
            <table class="adm-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Nama</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Last Login</th>
                  <th>Status</th>
                  <th style="width:120px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($admins && $admins->num_rows > 0):
                  $no = 1;
                  $me = (int)currentAdmin()['id'];
                  while ($a = $admins->fetch_assoc()): ?>
                <tr>
                  <td style="color:rgba(255,255,255,.35);"><?= $no++ ?></td>
                  <td>
                    <div class="fw-semibold" style="color:#fff;">
                      <?= htmlspecialchars($a['name']) ?>
                      <?php if ($a['id_admin'] === $me): ?>
                        <span class="adm-badge adm-badge-blue ms-1">Anda</span>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td style="font-size:13px;"><?= htmlspecialchars($a['email']) ?></td>
                  <td>
                    <?php if ($a['role'] === 'superadmin'): ?>
                      <span class="adm-badge adm-badge-blue">Super Admin</span>
                    <?php else: ?>
                      <span class="adm-badge adm-badge-gray">Admin</span>
                    <?php endif; ?>
                  </td>
                  <td style="font-size:12px;color:rgba(255,255,255,.45);">
                    <?= $a['last_login']
                      ? date('d M Y H:i', strtotime($a['last_login']))
                      : '—' ?>
                  </td>
                  <td>
                    <?php if ($a['is_active']): ?>
                      <span class="adm-badge adm-badge-green">Aktif</span>
                    <?php else: ?>
                      <span class="adm-badge adm-badge-red">Nonaktif</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="d-flex gap-2">
                      <a href="admins.php?edit=<?= $a['id_admin'] ?>"
                         class="adm-btn adm-btn-outline adm-btn-sm" title="Edit">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <?php if ($a['id_admin'] !== $me): ?>
                        <a href="admins.php?toggle=<?= $a['id_admin'] ?>"
                           class="adm-btn adm-btn-outline adm-btn-sm"
                           title="<?= $a['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>">
                          <i class="bi bi-<?= $a['is_active'] ? 'pause-circle' : 'play-circle' ?>"></i>
                        </a>
                        <a href="admins.php?delete=<?= $a['id_admin'] ?>"
                           class="adm-btn adm-btn-danger adm-btn-sm"
                           title="Hapus"
                           onclick="return confirm('Hapus admin ini? Tindakan ini tidak bisa dibatalkan.')">
                          <i class="bi bi-trash"></i>
                        </a>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
                <?php endwhile; else: ?>
                <tr>
                  <td colspan="7" class="text-center"
                    style="padding:32px;color:rgba(255,255,255,.35);">
                    Belum ada admin.
                  </td>
                </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      

    </div>
  </div>
</div>

<script>
function togglePass() {
  var inp  = document.getElementById('passInput');
  var icon = document.getElementById('passIcon');
  if (inp.type === 'password') {
    inp.type = 'text';
    icon.className = 'bi bi-eye-slash';
  } else {
    inp.type = 'password';
    icon.className = 'bi bi-eye';
  }
}
</script>

<?php include 'partials/footer.php'; ?>