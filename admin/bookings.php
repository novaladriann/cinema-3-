<?php
session_start();
require 'auth.php';
require '../config/koneksi.php';

$title     = "CINEM4 Admin — Booking";
$pageTitle = "Manajemen Booking";

/* ── Update status payment ── */
if (isset($_GET['set_status'])) {
  $bid    = (int)$_GET['id'];
  $status = $_GET['set_status'];
  $allowed = ['pending','paid','failed','expired','cancelled'];
  if (in_array($status, $allowed)) {
    $stmt = $conn->prepare("UPDATE bookings SET payment_status=? WHERE id_booking=?");
    $stmt->bind_param("si", $status, $bid);
    $stmt->execute();
    $stmt->close();
  }
  header("Location: bookings.php");
  exit;
}

/* ── Filter ── */
$search  = trim($_GET['q']      ?? '');
$pStatus = trim($_GET['status'] ?? '');
$dateF   = trim($_GET['date']   ?? '');

$where  = "WHERE 1=1";
$params = [];
$types  = "";

if ($search !== '') {
  $where   .= " AND (b.booking_code LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
  $like     = "%$search%";
  $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
  $types   .= "ssss";
}
if ($pStatus !== '') {
  $where   .= " AND b.payment_status = ?";
  $params[] = $pStatus;
  $types   .= "s";
}
if ($dateF !== '') {
  $where   .= " AND DATE(b.booked_at) = ?";
  $params[] = $dateF;
  $types   .= "s";
}

$sql = "
  SELECT b.*, u.first_name, u.last_name, u.email,
         m.title AS movie_title,
         s.show_date, s.show_time, s.studio_name,
         c.name AS cinema_name
  FROM bookings b
  JOIN users u ON u.id_user = b.id_user
  JOIN schedules s ON s.id_schedule = b.id_schedule
  JOIN movies m ON m.id_movie = s.id_movie
  JOIN cinemas c ON c.id_cinema = s.id_cinema
  $where
  ORDER BY b.id_booking DESC
  LIMIT 100
";

if (count($params) > 0) {
  $stmt = $conn->prepare($sql);
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $bookings = $stmt->get_result();
  $stmt->close();
} else {
  $bookings = $conn->query($sql);
}

include 'partials/head.php';
include 'partials/sidebar.php';
?>

<div class="adm-main">
  <?php include 'partials/topbar.php'; ?>
  <div class="adm-content">

    <div class="adm-card">
      <div class="adm-card-header">
        <div class="adm-card-title">Daftar Booking</div>
      </div>

      <!-- Filter -->
      <div class="adm-card-body" style="padding-bottom:0;">
        <form class="d-flex flex-wrap gap-2" method="get">
          <input type="text" name="q" class="adm-form-control"
            placeholder="Kode / nama / email..."
            value="<?= htmlspecialchars($search) ?>" style="max-width:220px;">
          <select name="status" class="adm-form-control" style="max-width:160px;">
            <option value="">Semua Status</option>
            <?php foreach (['pending','paid','failed','expired','cancelled'] as $st): ?>
              <option value="<?= $st ?>" <?= $pStatus===$st?'selected':'' ?>>
                <?= ucfirst($st) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <input type="date" name="date" class="adm-form-control"
            value="<?= htmlspecialchars($dateF) ?>" style="max-width:160px;">
          <button type="submit" class="adm-btn adm-btn-primary">
            <i class="bi bi-search"></i> Cari
          </button>
          <?php if ($search || $pStatus || $dateF): ?>
            <a href="bookings.php" class="adm-btn adm-btn-outline">Reset</a>
          <?php endif; ?>
        </form>
      </div>

      <div style="overflow-x:auto; margin-top:12px;">
        <table class="adm-table" data-dt='{"searching":false}'>
          <thead>
            <tr>
              <th>#</th>
              <th>Kode Booking</th>
              <th>User</th>
              <th>Film</th>
              <th>Jadwal</th>
              <th>Kursi</th>
              <th>Total</th>
              <th>Bayar</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($bookings && $bookings->num_rows > 0):
              $no = 1;
              while ($b = $bookings->fetch_assoc()):
                $ps = $b['payment_status'];
                $badgeClass = match($ps) {
                  'paid'      => 'adm-badge-green',
                  'pending'   => 'adm-badge-yellow',
                  'cancelled',
                  'expired'   => 'adm-badge-gray',
                  default     => 'adm-badge-red',
                };
            ?>
            <tr>
              <td style="color:rgba(255,255,255,.35);"><?= $no++ ?></td>
              <td style="font-family:monospace;font-size:12px;color:#fff;">
                <?= htmlspecialchars($b['booking_code']) ?>
              </td>
              <td>
                <div style="color:#fff;"><?= htmlspecialchars($b['first_name'].' '.$b['last_name']) ?></div>
                <div style="font-size:11px;color:rgba(255,255,255,.35);"><?= htmlspecialchars($b['email']) ?></div>
              </td>
              <td><?= htmlspecialchars($b['movie_title']) ?></td>
              <td style="font-size:12px;">
                <?= date('d M Y', strtotime($b['show_date'])) ?><br>
                <span style="color:rgba(255,255,255,.45);">
                  <?= date('H:i', strtotime($b['show_time'])) ?> •
                  <?= htmlspecialchars($b['studio_name']) ?>
                </span>
              </td>
              <td><?= $b['total_seats'] ?> kursi</td>
              <td>Rp <?= number_format((float)$b['total_amount'], 0, ',', '.') ?></td>
              <td><span class="adm-badge <?= $badgeClass ?>"><?= ucfirst($ps) ?></span></td>
              <td>
                <div class="dropdown">
                  <button class="adm-btn adm-btn-outline adm-btn-sm dropdown-toggle"
                    data-bs-toggle="dropdown">
                    Ubah
                  </button>
                  <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                    <?php foreach (['paid','pending','cancelled','expired'] as $st): ?>
                      <?php if ($st !== $ps): ?>
                        <li>
                          <a class="dropdown-item"
                            href="bookings.php?set_status=<?= $st ?>&id=<?= $b['id_booking'] ?>">
                            <?= ucfirst($st) ?>
                          </a>
                        </li>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </ul>
                </div>
              </td>
            </tr>
            <?php endwhile; else: ?>
            <tr>
              <td colspan="9" class="text-center"
                style="padding:32px;color:rgba(255,255,255,.35);">
                Tidak ada booking ditemukan.
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