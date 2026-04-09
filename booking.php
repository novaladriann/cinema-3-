<?php
session_start();

if (!isset($_SESSION['user'])) {
  header("Location: join-us.php?mode=login");
  exit;
}

$title  = "CINEM4 - Booking";
$active = "movies";

include 'partials/head.php';
include 'partials/navbar.php';
require 'config/koneksi.php';

/* ── Ambil schedule dari DB ── */
$scheduleId = (int)($_GET['schedule'] ?? 0);
$slug       = $_GET['slug'] ?? '';

$schedule = null;
$movie    = null;

if ($scheduleId > 0) {
  $stmt = $conn->prepare("
    SELECT s.*, m.title, m.slug, m.genre, m.duration_minute,
           m.poster, m.backdrop, m.rating_age,
           c.name AS cinema_name, c.city AS cinema_city
    FROM schedules s
    JOIN movies m ON m.id_movie = s.id_movie
    JOIN cinemas c ON c.id_cinema = s.id_cinema
    WHERE s.id_schedule = ? AND s.is_active = 1
    LIMIT 1
  ");
  $stmt->bind_param("i", $scheduleId);
  $stmt->execute();
  $schedule = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if ($schedule) {
    $movie = $schedule;
    $slug  = $schedule['slug'];
  }
}

/* Fallback: ambil dari slug jika schedule tidak ditemukan */
if (!$movie && $slug !== '') {
  $stmt = $conn->prepare("SELECT * FROM movies WHERE slug = ? AND is_active = 1 LIMIT 1");
  $stmt->bind_param("s", $slug);
  $stmt->execute();
  $movie = $stmt->get_result()->fetch_assoc();
  $stmt->close();
}

if (!$movie) {
  echo '<div class="container py-5"><div class="alert alert-dark border-secondary">Film tidak ditemukan. <a href="movies.php" class="alert-link">Kembali</a></div></div>';
  include 'partials/footer.php';
  exit;
}

/* ── Info tampilan ── */
$titleM   = $movie['title']           ?? '';
$genre    = $movie['genre']           ?? '';
$poster   = $movie['poster']          ?? '';
$backdrop = !empty($movie['backdrop']) ? $movie['backdrop'] : $poster;
$rating   = $movie['rating_age']      ?? '';

$dur = '';
if (!empty($movie['duration_minute']) && $movie['duration_minute'] > 0) {
  $h   = floor($movie['duration_minute'] / 60);
  $s   = $movie['duration_minute'] % 60;
  $dur = $h > 0 ? "{$h}h {$s}m" : "{$s}m";
}

$cinemaName = $schedule['cinema_name'] ?? '-';
$cinemaCity = $schedule['cinema_city'] ?? '';
$studioName = $schedule['studio_name'] ?? '-';
$showDate   = $schedule ? date('D, d M Y', strtotime($schedule['show_date'])) : '-';
$showTime   = $schedule ? date('H:i', strtotime($schedule['show_time']))       : '-';
$price      = (float)($schedule['price'] ?? 0);
$capacity   = (int)($schedule['seat_capacity'] ?? 40);

/* ── Kursi yang sudah dipesan ── */
$bookedSeats = [];
if ($scheduleId > 0) {
  $qBooked = $conn->prepare("
    SELECT seat_code FROM booking_seats
    WHERE id_schedule = ?
  ");
  $qBooked->bind_param("i", $scheduleId);
  $qBooked->execute();
  $rBooked = $qBooked->get_result();
  while ($row = $rBooked->fetch_assoc()) {
    $bookedSeats[] = $row['seat_code'];
  }
  $qBooked->close();
}

/* ── Generate denah kursi dinamis dari kapasitas ── */
/*
  Kapasitas 40 = 5 baris x 8 kolom
  Kapasitas 60 = 6 baris x 10 kolom
  dst — kita hitung otomatis
*/
$cols     = 8;
$rowCount = (int)ceil($capacity / $cols);
$rowLabels = array_slice(range('A', 'Z'), 0, $rowCount);
$colLabels = range(1, $cols);
?>

<!-- ══ HERO ══ -->
<section class="booking-hero">
  <div class="booking-hero-bg"
    style="background-image:url('<?= htmlspecialchars($backdrop) ?>')"></div>
  <div class="booking-hero-overlay"></div>

  <div class="container booking-hero-content">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
      <div>
        <h1 class="booking-title mb-1"><?= htmlspecialchars($titleM) ?></h1>
        <div class="text-secondary mb-3">
          <?= htmlspecialchars(strtoupper($genre)) ?>
          <?php if ($dur): ?> • <?= htmlspecialchars($dur) ?><?php endif; ?>
            <?php if ($rating): ?> • <?= htmlspecialchars($rating) ?><?php endif; ?>
        </div>
        <div class="booking-meta">
          <div><i class="bi bi-building"></i>
            <?= htmlspecialchars($cinemaName) ?>
            <?php if ($cinemaCity): ?>(<?= htmlspecialchars($cinemaCity) ?>)<?php endif; ?>
          </div>
          <div><i class="bi bi-calendar-event"></i>
            <?= htmlspecialchars($showDate) ?> • <?= htmlspecialchars($showTime) ?>
          </div>
          <div><i class="bi bi-door-open"></i> <?= htmlspecialchars($studioName) ?></div>
          <?php if ($price > 0): ?>
            <div><i class="bi bi-tag"></i>
              Rp <?= number_format($price, 0, ',', '.') ?> / kursi
            </div>
          <?php endif; ?>
        </div>
      </div>

      <a href="movie-detail.php?slug=<?= urlencode($slug) ?>"
        class="btn btn-outline-light border-secondary rounded-pill px-4">
        <i class="bi bi-arrow-left me-1"></i> Back
      </a>
    </div>
  </div>
</section>

<!-- ══ SEAT PICKER ══ -->
<div class="container py-4 pb-5">

  <!-- Notice -->
  <div class="alert alert-dark border-secondary d-flex align-items-center
    justify-content-between gap-3 booking-note mb-4">
    <div class="d-flex align-items-center gap-2">
      <i class="bi bi-exclamation-triangle"></i>
      <span class="small">Tiket yang dibeli tidak dapat diubah atau di-refund.</span>
    </div>
    <button type="button" class="btn btn-sm btn-outline-light border-secondary"
      onclick="this.closest('.booking-note').remove()">✕</button>
  </div>

  <div class="card-glass p-3 p-md-4">

    <!-- ── Ilustrasi Layar ── -->
    <div class="bk-screen-wrap">
      <img src="assets/ui/screen-3.png" alt="Screen" class="bk-screen-img">
    </div>


    <!-- ── Denah Kursi ── -->
    <div class="seat-wrap mx-auto">
      <?php foreach ($rowLabels as $r): ?>
        <div class="seat-row">
          <?php foreach ($colLabels as $c): ?>
            <?php
            $code       = $r . $c;
            $isBooked   = in_array($code, $bookedSeats, true);
            $cls        = 'seat' . ($isBooked ? ' is-booked' : '');
            ?>
            <button type="button"
              class="<?= $cls ?>"
              data-seat="<?= htmlspecialchars($code) ?>"
              <?= $isBooked ? 'disabled' : '' ?>>
              <?= htmlspecialchars($code) ?>
            </button>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Legend -->
    <div class="seat-legend mt-4 d-flex flex-wrap justify-content-center gap-4 small">
      <span class="legend-item">
        <span class="legend-seat available"></span> Available
      </span>
      <span class="legend-item">
        <span class="legend-seat booked"></span> Booked
      </span>
      <span class="legend-item">
        <span class="legend-seat selected"></span> Selected
      </span>
    </div>

    <hr class="border-secondary opacity-25 my-4">

    <!-- Summary + Aksi -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div>
        <div class="text-secondary small mb-1">Kursi dipilih</div>
        <div class="fw-semibold text-light" id="selectedText">—</div>
        <div class="text-secondary small mt-1" id="totalPrice"
          style="color:var(--c4-primary) !important;"></div>
      </div>

      <div class="d-flex gap-2">
        <button class="btn btn-outline-light border-secondary rounded-pill px-4"
          id="clearBtn">Clear</button>
        <a class="btn btn-primary rounded-pill px-4 disabled"
          id="nextBtn" aria-disabled="true"
          href="payment.php?schedule=<?= $scheduleId ?>&slug=<?= urlencode($slug) ?>">
          Lanjut Bayar
        </a>
      </div>
    </div>

  </div>
</div>


<style>
/* ── Ilustrasi Layar ── */
.bk-screen-wrap {
  display: flex;
  justify-content: center;
  margin-bottom: 40px;
  position: relative;
}
/* Glow ambient di belakang layar */
.bk-screen-wrap::before {
  content: "";
  position: absolute;
  top: 15%; left: 50%;
  transform: translateX(-50%);
  width: 70%;
  height: 70%;
  background: radial-gradient(ellipse,
    rgba(31,111,255,.30) 0%,
    rgba(31,111,255,.12) 50%,
    transparent 75%);
  filter: blur(32px);
  z-index: 0;
  pointer-events: none;
}
.bk-screen-img {
  position: relative;
  z-index: 1;
  width: min(450px, 94%);
  height: auto;
  display: block;
  filter:
    drop-shadow(0 0 14px rgba(31,111,255,.75))
    drop-shadow(0 0 36px rgba(31,111,255,.35));
}
 
 
@media (max-width: 767px) {
  .bk-screen-img { width: min(360px, 94%); }
}
</style>

<!-- ══ SCRIPT ══ -->
<script>
  (function() {
    const price = <?= $price ?>;
    const selected = new Set();
    const selectedTx = document.getElementById('selectedText');
    const totalPrTx = document.getElementById('totalPrice');
    const nextBtn = document.getElementById('nextBtn');
    const clearBtn = document.getElementById('clearBtn');
    const scheduleId = <?= $scheduleId ?>;
    const slug = '<?= addslashes($slug) ?>';

    function sync() {
      const seats = Array.from(selected).sort();
      const seatsStr = seats.join(', ');
      selectedTx.textContent = seatsStr || '—';

      if (price > 0 && seats.length > 0) {
        const total = price * seats.length;
        totalPrTx.textContent = seats.length + ' kursi • Rp ' +
          total.toLocaleString('id-ID');
      } else {
        totalPrTx.textContent = '';
      }

      /* Update link next */
      const disabled = seats.length === 0;
      nextBtn.classList.toggle('disabled', disabled);
      nextBtn.setAttribute('aria-disabled', disabled ? 'true' : 'false');

      if (!disabled) {
        nextBtn.href = 'payment.php?schedule=' + scheduleId +
          '&slug=' + encodeURIComponent(slug) +
          '&seats=' + encodeURIComponent(seats.join(','));
      }
    }

    document.querySelectorAll('.seat:not(.is-booked)').forEach(btn => {
      btn.addEventListener('click', function() {
        const code = this.getAttribute('data-seat');
        if (selected.has(code)) {
          selected.delete(code);
          this.classList.remove('is-selected');
        } else {
          selected.add(code);
          this.classList.add('is-selected');
        }
        sync();
      });
    });

    clearBtn.addEventListener('click', function() {
      selected.clear();
      document.querySelectorAll('.seat.is-selected')
        .forEach(b => b.classList.remove('is-selected'));
      sync();
    });

    sync();
  }());
</script>

<?php include 'partials/footer.php'; ?>