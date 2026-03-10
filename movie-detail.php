<?php
session_start();

$title  = "CINEM4 - Movie Detail";
$active = "movies";
include 'partials/head.php';
include 'partials/navbar.php';

include 'data/movies.php';
$movies = $MOVIES;

$slug = $_GET['slug'] ?? '';
$movie = null;

foreach ($movies as $m) {
  if (($m['slug'] ?? '') === $slug) {
    $movie = $m;
    break;
  }
}

$isLoggedIn = isset($_SESSION['user']);

if ($movie) {
  $genre    = trim($movie['genre']    ?? '');
  $dur      = trim($movie['dur']      ?? '');
  $format   = trim($movie['format']   ?? '');
  $rating   = trim($movie['rating']   ?? '');
  $age      = trim($movie['age']      ?? '');
  $poster   = trim($movie['poster']   ?? '');
  $trailer  = trim($movie['trailer']  ?? '');
  $synopsis = trim($movie['synopsis'] ?? '');
  $isUpcoming = (($movie['status'] ?? 'now') === 'upcoming');
}

// mock jadwal (nanti bisa dari data/jadwal.php)
$schedules = [
  ["date" => "Hari ini", "studio" => "Studio 1", "times" => ["12:30", "15:10", "18:40", "21:15"]],
  ["date" => "Besok",    "studio" => "Studio 2", "times" => ["11:00", "13:45", "16:20", "20:05"]],
];
?>

<div class="container py-5">

  <?php if (!$movie): ?>
    <div class="alert alert-dark border-secondary">
      Film tidak ditemukan.
      <a class="text-decoration-none" href="movies.php">Kembali ke Movies</a>
    </div>
  <?php else: ?>

    <!-- TOP: Poster + Info -->
    <div class="row g-4 align-items-start">
      <div class="col-lg-4 reveal reveal-left">
        <div class="poster-card poster-card--detail">
          <div class="poster-media">
            <img src="<?= htmlspecialchars($poster) ?>" alt="<?= htmlspecialchars($movie['title']) ?>">
          </div>
          <?php if ($age !== ''): ?>
            <div class="poster-badge"><?= htmlspecialchars($age) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="col-lg-8 reveal reveal-right">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
          <div>
            <h1 class="fw-bold mb-2"><?= htmlspecialchars($movie['title']) ?></h1>

            <div class="d-flex flex-wrap gap-2">
              <?php if ($genre !== ''): ?>
                <span class="badge rounded-pill bg-dark text-light border border-secondary"><?= htmlspecialchars($genre) ?></span>
              <?php endif; ?>

              <?php if ($dur !== ''): ?>
                <span class="badge rounded-pill bg-dark text-light border border-secondary"><?= htmlspecialchars($dur) ?></span>
              <?php endif; ?>

              <?php if ($format !== ''): ?>
                <span class="badge rounded-pill bg-dark text-light border border-secondary"><?= htmlspecialchars($format) ?></span>
              <?php endif; ?>

              <?php if ($rating !== ''): ?>
                <span class="badge rounded-pill bg-danger"><?= htmlspecialchars($rating) ?></span>
              <?php endif; ?>
            </div>
          </div>

          <a href="movies.php" class="btn btn-outline-light border-secondary rounded-pill">
            <i class="bi bi-arrow-left me-1"></i> Back
          </a>
        </div>

        <!-- Synopsis -->
        <div class="card-glass p-3 p-md-4 mb-3">
          <div class="fw-semibold mb-2">Sinopsis</div>
          <?php if ($synopsis !== ''): ?>
            <div class="text-secondary" style="line-height:1.7;">
              <?= nl2br(htmlspecialchars($synopsis)) ?>
            </div>
          <?php else: ?>
            <div class="text-secondary fst-italic">Sinopsis belum tersedia.</div>
          <?php endif; ?>
        </div>

        <!-- Actions -->
        <div class="d-flex gap-2 flex-wrap">
          <?php if ($trailer !== ''): ?>
            <button type="button"
              class="btn btn-light rounded-pill px-4 watch-trailer"
              data-bs-toggle="modal"
              data-bs-target="#trailerModal"
              data-trailer="<?= htmlspecialchars($trailer) ?>">
              <i class="bi bi-play-fill me-1"></i> Watch Trailer
            </button>
          <?php endif; ?>

          <?php if ($isUpcoming): ?>
            <button class="btn btn-secondary rounded-pill px-4" disabled>
              <i class="bi bi-clock me-1"></i> Coming Soon
            </button>
          <?php else: ?>
            <!-- lebih UX: scroll ke jadwal -->
            <a href="#jadwal" class="btn btn-primary rounded-pill px-4">
              <i class="bi bi-ticket-perforated me-1"></i> Book Now
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Jadwal (only if not upcoming) -->
    <?php if (!$isUpcoming): ?>
      <div id="jadwal" class="card-glass p-3 p-md-4 mt-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
          <div class="fw-semibold text-light">Jadwal Tayang</div>
          <div class="text-secondary small">Pilih jam untuk lanjut booking</div>
        </div>

        <?php foreach ($schedules as $s): ?>
          <div class="mb-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div class="text-light fw-semibold"><?= htmlspecialchars($s['date']) ?></div>
              <div class="text-secondary small"><?= htmlspecialchars($s['studio']) ?></div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-2">
              <?php foreach ($s['times'] as $t): ?>
                <?php
                  $bookingUrl = "booking.php?slug=" . urlencode($movie['slug'])
                              . "&time=" . urlencode($t)
                              . "&studio=" . urlencode($s['studio']);

                  if (!$isLoggedIn) {
                    $bookingUrl = "join-us.php?mode=login&next=" . urlencode($bookingUrl);
                  }
                ?>
                <a class="btn btn-outline-light border-secondary rounded-pill px-3" href="<?= $bookingUrl ?>">
                  <?= htmlspecialchars($t) ?>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
          <hr class="border-secondary opacity-25">
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  <?php endif; ?>
</div>

<!-- Trailer Modal -->
<div class="modal fade" id="trailerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-dark border-secondary">
      <div class="modal-body p-0 position-relative">
        <button type="button"
          class="btn-close btn-close-white position-absolute end-0 m-3"
          data-bs-dismiss="modal"></button>

        <div class="ratio ratio-16x9">
          <iframe id="trailerFrame" src="" allow="autoplay; encrypted-media" allowfullscreen></iframe>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  const trailerModal = document.getElementById('trailerModal');
  const trailerFrame = document.getElementById('trailerFrame');

  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.watch-trailer');
    if (!btn) return;
    e.preventDefault();

    const url = btn.getAttribute('data-trailer') || '';
    trailerFrame.src = url ? (url + (url.includes('?') ? '&' : '?') + 'autoplay=1') : '';
  }, true);

  trailerModal.addEventListener('hidden.bs.modal', function() {
    trailerFrame.src = '';
  });
</script>

<?php include 'partials/footer.php'; ?>