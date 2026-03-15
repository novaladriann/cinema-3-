<?php
session_start();
require 'auth.php';
require '../config/koneksi.php';

$title     = "CINEM4 Admin — Dashboard";
$pageTitle = "Dashboard";
include 'partials/head.php';
include 'partials/sidebar.php';
?>

<div class="adm-main">
  <?php include 'partials/topbar.php'; ?>

  <div class="adm-content">

    <!-- Greeting -->
    <div class="mb-4">
      <h4 class="fw-bold mb-1">
        Halo, <?= htmlspecialchars(currentAdmin()['name']) ?>! 👋
      </h4>
      <div style="font-size:14px; color:rgba(255,255,255,.45);">
        <?= date('l, d F Y') ?> — Selamat datang di panel admin CINEM4.
      </div>
    </div>

    <?php
    /* ── Stat counts ── */
    $totalMovies    = $conn->query("SELECT COUNT(*) FROM movies WHERE is_active = 1")->fetch_row()[0] ?? 0;
    $nowShowing     = $conn->query("SELECT COUNT(*) FROM movies WHERE status = 'now_showing' AND is_active = 1")->fetch_row()[0] ?? 0;
    $comingSoon     = $conn->query("SELECT COUNT(*) FROM movies WHERE status = 'coming_soon' AND is_active = 1")->fetch_row()[0] ?? 0;
    $totalSchedules = $conn->query("SELECT COUNT(*) FROM schedules WHERE is_active = 1 AND status = 'open' AND show_date >= CURDATE()")->fetch_row()[0] ?? 0;
    $totalBookings  = $conn->query("SELECT COUNT(*) FROM bookings")->fetch_row()[0] ?? 0;
    $paidBookings   = $conn->query("SELECT COUNT(*) FROM bookings WHERE payment_status = 'paid'")->fetch_row()[0] ?? 0;
    $totalUsers     = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0] ?? 0;
    $totalPromos    = $conn->query("SELECT COUNT(*) FROM promotions WHERE is_active = 1")->fetch_row()[0] ?? 0;

    $revenue = $conn->query("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE payment_status = 'paid'")->fetch_row()[0] ?? 0;
    ?>

    <!-- Stat cards -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-lg-3">
        <div class="adm-stat">
          <div class="adm-stat-icon" style="background:rgba(31,111,255,.15); color:var(--c4-primary);">
            <i class="bi bi-film"></i>
          </div>
          <div>
            <div class="adm-stat-val"><?= $totalMovies ?></div>
            <div class="adm-stat-label">Total Film</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="adm-stat">
          <div class="adm-stat-icon" style="background:rgba(25,135,84,.15); color:#6ee7b7;">
            <i class="bi bi-calendar3"></i>
          </div>
          <div>
            <div class="adm-stat-val"><?= $totalSchedules ?></div>
            <div class="adm-stat-label">Jadwal Aktif</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="adm-stat">
          <div class="adm-stat-icon" style="background:rgba(255,193,7,.15); color:#fde68a;">
            <i class="bi bi-ticket-perforated"></i>
          </div>
          <div>
            <div class="adm-stat-val"><?= $totalBookings ?></div>
            <div class="adm-stat-label">Total Booking</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="adm-stat">
          <div class="adm-stat-icon" style="background:rgba(168,85,247,.15); color:#d8b4fe;">
            <i class="bi bi-people"></i>
          </div>
          <div>
            <div class="adm-stat-val"><?= $totalUsers ?></div>
            <div class="adm-stat-label">Users</div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <!-- Revenue -->
      <div class="col-12 col-md-4">
        <div class="adm-stat" style="background:rgba(31,111,255,.08); border-color:rgba(31,111,255,.25);">
          <div class="adm-stat-icon" style="background:rgba(31,111,255,.20); color:var(--c4-primary);">
            <i class="bi bi-cash-stack"></i>
          </div>
          <div>
            <div class="adm-stat-val" style="font-size:18px;">
              Rp <?= number_format((float)$revenue, 0, ',', '.') ?>
            </div>
            <div class="adm-stat-label">Total Pendapatan</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-4">
        <div class="adm-stat">
          <div class="adm-stat-icon" style="background:rgba(25,135,84,.15); color:#6ee7b7;">
            <i class="bi bi-play-circle"></i>
          </div>
          <div>
            <div class="adm-stat-val"><?= $nowShowing ?></div>
            <div class="adm-stat-label">Now Showing</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-4">
        <div class="adm-stat">
          <div class="adm-stat-icon" style="background:rgba(255,193,7,.15); color:#fde68a;">
            <i class="bi bi-clock"></i>
          </div>
          <div>
            <div class="adm-stat-val"><?= $comingSoon ?></div>
            <div class="adm-stat-label">Coming Soon</div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3">

      <!-- Film terbaru -->
      <div class="col-12 col-xl-6">
        <div class="adm-card">
          <div class="adm-card-header">
            <div class="adm-card-title">Film Terbaru</div>
            <a href="movies.php" class="adm-btn adm-btn-outline adm-btn-sm">Lihat Semua</a>
          </div>
          <div style="overflow-x:auto;">
            <table class="adm-table">
              <thead>
                <tr>
                  <th>Judul</th>
                  <th>Genre</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $qM = $conn->query("SELECT title, genre, status FROM movies WHERE is_active = 1 ORDER BY id_movie DESC LIMIT 6");
                while ($row = $qM->fetch_assoc()):
                  $badge = $row['status'] === 'now_showing'
                    ? '<span class="adm-badge adm-badge-green">Now Showing</span>'
                    : '<span class="adm-badge adm-badge-yellow">Coming Soon</span>';
                ?>
                <tr>
                  <td class="fw-semibold" style="color:#fff;"><?= htmlspecialchars($row['title']) ?></td>
                  <td><?= htmlspecialchars($row['genre']) ?></td>
                  <td><?= $badge ?></td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Booking terbaru -->
      <div class="col-12 col-xl-6">
        <div class="adm-card">
          <div class="adm-card-header">
            <div class="adm-card-title">Booking Terbaru</div>
            <a href="bookings.php" class="adm-btn adm-btn-outline adm-btn-sm">Lihat Semua</a>
          </div>
          <div style="overflow-x:auto;">
            <table class="adm-table">
              <thead>
                <tr>
                  <th>Kode</th>
                  <th>User</th>
                  <th>Total</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $qB = $conn->query("
                  SELECT b.booking_code, u.first_name, u.last_name,
                         b.total_amount, b.payment_status
                  FROM bookings b
                  JOIN users u ON u.id_user = b.id_user
                  ORDER BY b.id_booking DESC LIMIT 6
                ");
                if ($qB && $qB->num_rows > 0):
                  while ($row = $qB->fetch_assoc()):
                    $ps = $row['payment_status'];
                    $badgeClass = match($ps) {
                      'paid'      => 'adm-badge-green',
                      'pending'   => 'adm-badge-yellow',
                      'cancelled' => 'adm-badge-gray',
                      default     => 'adm-badge-red',
                    };
                ?>
                <tr>
                  <td style="font-family:monospace; font-size:12px;"><?= htmlspecialchars($row['booking_code']) ?></td>
                  <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                  <td>Rp <?= number_format((float)$row['total_amount'], 0, ',', '.') ?></td>
                  <td><span class="adm-badge <?= $badgeClass ?>"><?= ucfirst($ps) ?></span></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="4" class="text-center" style="color:rgba(255,255,255,.35); padding:24px;">Belum ada booking.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div><!-- /row -->
  </div><!-- /adm-content -->
</div><!-- /adm-main -->

<?php include 'partials/footer.php'; ?>