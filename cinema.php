<?php
$title = "CINEM4 - Cinema";
$active = "cinema";
include 'partials/head.php';
include 'partials/navbar.php';

include 'data/cinemas.php';

$city = $_GET['city'] ?? 'Cirebon';
$screen = $_GET['screen'] ?? 'All Screen Class';

/* filter berdasarkan kota */
$list = array_values(array_filter($CINEMAS, fn($c) => ($c['city'] ?? '') === $city));
?>

<!-- HERO BANNER -->
<section class="cinema-hero">
  <div class="cinema-hero-bg" style="background-image:url('assets/img/cinema-cirebon.png')"></div>
  <div class="cinema-hero-overlay"></div>

  <div class="container cinema-hero-content">
    <div class="row">
      <div class="col-lg-7">
        <h1 class="display-5 fw-bold mb-2">Cinema</h1>
        <div class="text-secondary">Pilih kota dan temukan cinema di mall terdekat.</div>
      </div>
    </div>
  </div>
</section>

<!-- SEARCH FLOATING -->
<section class="cinema-search">
  <div class="container">
    <div class="card-glass p-3 p-md-3">
      <form class="row g-2 align-items-center" method="get" action="cinema.php">
        <div class="col-12 col-md-5">
          <div class="input-group input-group-lg">
            <span class="input-group-text bg-transparent text-secondary border-secondary">
              <i class="bi bi-geo-alt"></i>
            </span>
            <select class="form-select bg-dark text-light border-secondary" name="city">
              <?php foreach($CITIES as $ct): ?>
                <option value="<?= htmlspecialchars($ct) ?>" <?= $ct===$city?'selected':'' ?>>
                  <?= htmlspecialchars($ct) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="col-12 col-md-5">
          <div class="input-group input-group-lg">
            <span class="input-group-text bg-transparent text-secondary border-secondary">
              <i class="bi bi-aspect-ratio"></i>
            </span>
            <select class="form-select bg-dark text-light border-secondary" name="screen">
              <?php foreach($SCREEN_CLASSES as $sc): ?>
                <option value="<?= htmlspecialchars($sc) ?>" <?= $sc===$screen?'selected':'' ?>>
                  <?= htmlspecialchars($sc) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="col-12 col-md-2 d-grid">
          <button class="btn btn-primary btn-lg rounded-3" type="submit">
            <i class="bi bi-search"></i>
          </button>
        </div>
      </form>
    </div>
  </div>
</section>

<!-- LIST CINEMA -->
<section class="py-5">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
      <div>
        <h2 class="fw-bold mb-1"><?= htmlspecialchars($city) ?></h2>
        <div class="text-secondary">Menampilkan <?= count($list) ?> cinema</div>
      </div>
    </div>

    <?php if(count($list) === 0): ?>
      <div class="alert alert-dark border-secondary">
        Tidak ada cinema untuk kota ini (mock).
      </div>
    <?php endif; ?>

    <div class="row g-4">
      <?php foreach($list as $c): ?>
        <div class="col-12 col-md-6 col-lg-4 reveal d1">
          <div class="card-glass cinema-card h-100">
            <div class="cinema-thumb" style="background-image:url('<?= htmlspecialchars($c['img']) ?>')"></div>

            <div class="p-3">
              <div class="fw-bold text-light fs-5"><?= htmlspecialchars($c['mall']) ?></div>
              <div class="text-secondary small mt-1">
                <i class="bi bi-geo me-1"></i><?= htmlspecialchars($c['address']) ?>
              </div>
              <div class="text-secondary small mt-1">
                <i class="bi bi-building me-1"></i><?= htmlspecialchars($c['code']) ?>
              </div>

              <div class="d-flex gap-2 mt-3">
                <a class="btn btn-primary rounded-pill px-3 btn-sm"
                   href="cinema-detail.php?city=<?= urlencode($city) ?>&mall=<?= urlencode($c['mall']) ?>">
                  Detail
                </a>
                <a class="btn btn-outline-light border-secondary rounded-pill px-3 btn-sm"
                   href="movies.php">
                  Lihat Film
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>

<?php include 'partials/footer.php'; ?>