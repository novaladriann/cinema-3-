<?php
session_start();
require 'auth.php';
require '../config/koneksi.php';

$id        = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit    = $id > 0;
$title     = "CINEM4 Admin — " . ($isEdit ? "Edit Promosi" : "Tambah Promosi");
$pageTitle = $isEdit ? "Edit Promosi" : "Tambah Promosi";

$promo = [];
if ($isEdit) {
  $stmt = $conn->prepare("SELECT * FROM promotions WHERE id_promotion = ? LIMIT 1");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $promo = $stmt->get_result()->fetch_assoc() ?? [];
  $stmt->close();
  if (!$promo) { header("Location: promotions.php"); exit; }
}

function makeSlugPromo(string $str): string {
  $str = strtolower(trim($str));
  $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
  $str = preg_replace('/[\s-]+/', '-', $str);
  return trim($str, '-') ?: 'promo-' . time();
}

$errors = [];
$old    = $promo;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $old         = $_POST;
  $titleVal    = trim($_POST['title']       ?? '');
  $slug        = makeSlugPromo($titleVal);
  $description = trim($_POST['description'] ?? '');
  $startDate   = trim($_POST['start_date']  ?? '') ?: null;
  $endDate     = trim($_POST['end_date']    ?? '') ?: null;
  $terms       = trim($_POST['terms']       ?? '');
  $isActive    = isset($_POST['is_active']) ? 1 : 0;
  $image       = trim($_POST['image']       ?? ($promo['image'] ?? ''));

  if ($titleVal === '') $errors[] = 'Judul promosi wajib diisi.';

  /* Cek slug duplikat */
  if (empty($errors)) {
    $chk = $conn->prepare("SELECT id_promotion FROM promotions WHERE slug = ? AND id_promotion != ? LIMIT 1");
    $chk->bind_param("si", $slug, $id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) $slug .= '-' . time();
    $chk->close();
  }

  /* Upload gambar */
  if (!empty($_FILES['image_file']['name'])) {
    $ext     = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp'];
    if (!in_array($ext, $allowed)) {
      $errors[] = 'Format gambar tidak didukung.';
    } else {
      $filename = 'promo-' . $slug . '.' . $ext;
      if (move_uploaded_file($_FILES['image_file']['tmp_name'], '../assets/img/' . $filename)) {
        $image = 'assets/img/' . $filename;
      } else {
        $errors[] = 'Gagal upload gambar.';
      }
    }
  }

  if (empty($errors)) {
    if ($isEdit) {
      $stmt = $conn->prepare("
        UPDATE promotions SET
          title=?, slug=?, description=?, image=?,
          start_date=?, end_date=?, terms=?, is_active=?,
          updated_at=NOW()
        WHERE id_promotion=?
      ");
      $stmt->bind_param("sssssssii",
        $titleVal, $slug, $description, $image,
        $startDate, $endDate, $terms, $isActive, $id
      );
    } else {
      $stmt = $conn->prepare("
        INSERT INTO promotions
          (title, slug, description, image, start_date, end_date, terms, is_active)
        VALUES (?,?,?,?,?,?,?,?)
      ");
      $stmt->bind_param("sssssssi",
        $titleVal, $slug, $description, $image,
        $startDate, $endDate, $terms, $isActive
      );
    }
    if ($stmt->execute()) {
      header("Location: promotions.php?msg=saved");
      exit;
    } else {
      $errors[] = 'Gagal menyimpan: ' . $conn->error;
    }
    $stmt->close();
  }
}

include 'partials/head.php';
include 'partials/sidebar.php';
?>

<div class="adm-main">
  <?php include 'partials/topbar.php'; ?>
  <div class="adm-content">

    <div class="mb-3" style="font-size:13px;color:rgba(255,255,255,.40);">
      <a href="promotions.php" style="color:rgba(255,255,255,.40);text-decoration:none;">Promosi</a>
      <span class="mx-2">/</span>
      <span style="color:rgba(255,255,255,.75);"><?= $isEdit?'Edit':'Tambah' ?></span>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="adm-alert adm-alert-danger mb-3">
        <i class="bi bi-exclamation-circle-fill"></i>
        <div><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <div class="row g-3">

        <div class="col-12 col-lg-8">
          <div class="adm-card">
            <div class="adm-card-header"><div class="adm-card-title">Detail Promosi</div></div>
            <div class="adm-card-body">
              <div class="row g-3">

                <div class="col-12">
                  <label class="adm-form-label">Judul <span style="color:#ff8a95;">*</span></label>
                  <input type="text" name="title" class="adm-form-control" required
                    value="<?= htmlspecialchars($old['title'] ?? '') ?>">
                </div>

                <div class="col-12">
                  <label class="adm-form-label">Deskripsi</label>
                  <textarea name="description" class="adm-form-control" rows="3"
                    ><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
                </div>

                <div class="col-12 col-md-6">
                  <label class="adm-form-label">Tanggal Mulai</label>
                  <input type="date" name="start_date" class="adm-form-control"
                    value="<?= htmlspecialchars($old['start_date'] ?? '') ?>">
                </div>

                <div class="col-12 col-md-6">
                  <label class="adm-form-label">Tanggal Selesai</label>
                  <input type="date" name="end_date" class="adm-form-control"
                    value="<?= htmlspecialchars($old['end_date'] ?? '') ?>">
                </div>

                <div class="col-12">
                  <label class="adm-form-label">Syarat & Ketentuan</label>
                  <textarea name="terms" class="adm-form-control" rows="3"
                    ><?= htmlspecialchars($old['terms'] ?? '') ?></textarea>
                </div>

                <div class="col-12">
                  <label class="adm-form-label">Gambar Promosi</label>
                  <?php if (!empty($old['image'])): ?>
                    <div class="mb-2">
                      <img src="../<?= htmlspecialchars($old['image']) ?>"
                        style="height:80px;border-radius:8px;object-fit:cover;">
                    </div>
                  <?php endif; ?>
                  <input type="file" name="image_file" class="adm-form-control" accept="image/*">
                  <input type="hidden" name="image" value="<?= htmlspecialchars($old['image'] ?? '') ?>">
                  <div style="font-size:11px;color:rgba(255,255,255,.35);margin-top:4px;">
                    Format: jpg/png/webp. Ukuran disarankan 800×400px.
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-4">
          <div class="adm-card mb-3">
            <div class="adm-card-body">
              <label class="d-flex align-items-center gap-2 mb-3" style="cursor:pointer;">
                <input type="checkbox" name="is_active" value="1"
                  <?= !empty($old['is_active']) || !$isEdit ? 'checked' : '' ?>
                  style="width:16px;height:16px;accent-color:var(--c4-primary);">
                <span class="adm-form-label mb-0">Promosi Aktif</span>
              </label>
            </div>
          </div>
          <div class="adm-card">
            <div class="adm-card-body">
              <button type="submit" class="adm-btn adm-btn-primary w-100 mb-2"
                style="justify-content:center;">
                <i class="bi bi-save"></i>
                <?= $isEdit ? 'Simpan Perubahan' : 'Tambah Promosi' ?>
              </button>
              <a href="promotions.php" class="adm-btn adm-btn-outline w-100"
                style="justify-content:center;">Batal</a>
            </div>
          </div>
        </div>

      </div>
    </form>
  </div>
</div>

<?php include 'partials/footer.php'; ?>