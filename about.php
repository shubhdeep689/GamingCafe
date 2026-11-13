<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * About Page - Dynamically White-Labeled
 */

require_once __DIR__ . '/config/config.php';

$brand_name     = get_brand_name();
$brand_short    = get_brand_short();
$brand_tagline  = get_brand_tagline();
$brand_city     = get_brand_city();
$brand_state    = get_brand_state();
$brand_address  = get_brand_address();
$brand_logo     = get_brand_logo();

$page_title = 'About ' . $brand_short . ' | Elite Esports & Gaming Sanctuary in ' . $brand_city;
$page_desc = 'Discover the story behind ' . $brand_name . '. Built for esports players, streamers, and squads in ' . $brand_city . ', ' . $brand_state . '.';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header Banner -->
<section class="py-5 section-gta6-bg position-relative overflow-hidden" data-bg-url="<?= BASE_URL ?>images/gta6_bg.jpeg" style="background-image: linear-gradient(180deg, rgba(9, 11, 16, 0.45) 0%, rgba(9, 11, 16, 0.18) 50%, rgba(9, 11, 16, 0.50) 100%), url('<?= BASE_URL ?>images/gta6_bg.jpeg'); border-bottom: 1px solid rgba(0, 229, 255, 0.35);">
  <div class="container py-4 text-center position-relative z-2">
    <span class="section-subtitle">Our Heritage</span>
    <h1 class="display-5 fw-bold mt-2">About The Sanctuary</h1>
    <p class="text-muted-iron mx-auto" style="max-width: 650px;">
      Forged for gamers who demand uncompromised performance, fair play, and an authentic competitive esports atmosphere in <?= e($brand_city) ?>.
    </p>
  </div>
</section>

<!-- Main Story Section -->
<section class="py-5">
  <div class="container py-4">
    <div class="row align-items-center g-5">
      
      <div class="col-lg-6">
        <span class="section-subtitle">Born From Passion</span>
        <h2 class="display-6 fw-bold mt-2 text-gold">Built For Players, By Gamers</h2>
        <p class="text-muted-iron mt-3">
          For years, gamers in <?= e($brand_city) ?> had to settle for standard internet cyber cafes with lagging networks, outdated hardware, and non-ergonomic chairs. 
        </p>
        <p class="text-muted-iron">
          <strong><?= e($brand_name) ?></strong> was conceived to break that mold. We set out to establish a true gaming temple—where every detail from monitor refresh rates and mouse response times to room acoustics, air conditioning, and RGB ambient illumination is tuned for victory.
        </p>
        <p class="text-muted-iron">
          Whether you are grinding ranks in tactical FPS shooters, practicing with your 5-man tournament roster, or relaxing over a weekend FIFA showdown with friends, <?= e($brand_short) ?> is your home ground.
        </p>

        <div class="p-3 rounded mt-4" style="background: var(--bg-card); border-left: 4px solid var(--gold); border-top: 1px solid var(--border-color); border-right: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);">
          <div class="fst-italic text-light">
            "To provide <?= e($brand_city) ?> with a world-class gaming space that nurtures esports talent, inspires competitive spirit, and builds lasting community camaraderie."
          </div>
          <div class="text-gold small fw-bold mt-2">— The <?= e($brand_short) ?> Creed</div>
        </div>
      </div>

      <div class="col-lg-6 text-center">
        <div class="iron-card p-4 p-md-5">
          <!-- Dynamic Transparent Crest -->
          <img src="<?= $brand_logo ?>" alt="<?= e($brand_name) ?> Crest" class="img-fluid mb-4" style="max-height: 200px; filter: drop-shadow(0 0 25px rgba(214,168,75,0.4));">
          <h4 class="text-gold brand-font mb-2"><?= e($brand_name) ?></h4>
          <p class="text-muted-iron small mb-4">
            <?= e($brand_tagline) ?><br>
            <?= e($brand_address) ?>
          </p>

          <div class="row g-2 text-start">
            <div class="col-6">
              <div class="p-2 rounded bg-black bg-opacity-40 border border-secondary border-opacity-25">
                <span class="text-muted-iron small d-block">Location</span>
                <strong class="text-light"><?= e($brand_city) ?></strong>
              </div>
            </div>
            <div class="col-6">
              <div class="p-2 rounded bg-black bg-opacity-40 border border-secondary border-opacity-25">
                <span class="text-muted-iron small d-block">Core Focus</span>
                <strong class="text-gold">Esports & Community</strong>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- The Three Pillars of Ironclad -->
<section class="py-5 section-cafe-bg position-relative" data-bg-url="<?= BASE_URL ?>images/cafe_bg.jpeg" style="background-image: linear-gradient(180deg, rgba(9, 11, 16, 0.50) 0%, rgba(9, 11, 16, 0.25) 50%, rgba(9, 11, 16, 0.55) 100%), url('<?= BASE_URL ?>images/cafe_bg.jpeg'); border-top: 1px solid rgba(214, 168, 75, 0.35); border-bottom: 1px solid rgba(214, 168, 75, 0.25);">
  <div class="container py-4">
    <div class="text-center mb-5">
      <span class="section-subtitle">Our Core Values</span>
      <h2 class="display-6 fw-bold mt-2">The Three Pillars</h2>
    </div>

    <div class="row g-4">
      <div class="col-md-4">
        <div class="iron-card h-100 text-center p-4">
          <div class="feature-icon-box mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.8rem;">
            <i class="bi bi-controller"></i>
          </div>
          <h3 class="h4 text-gold mb-2">1. Gaming</h3>
          <p class="text-muted-iron small">
            Hardware that never limits your reaction time. High FPS displays, mechanical switches, pro-grade optics, and ultra-fast ping ensuring you play your absolute best.
          </p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="iron-card h-100 text-center p-4">
          <div class="feature-icon-box mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.8rem; color: var(--electric-red); border-color: rgba(230,57,70,0.3); background: rgba(230,57,70,0.08);">
            <i class="bi bi-trophy"></i>
          </div>
          <h3 class="h4 text-red mb-2">2. Competition</h3>
          <p class="text-muted-iron small">
            A battleground for serious tournament play, local LAN brackets, and rank progression. Zero tolerance for unfair play or disruptive conduct.
          </p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="iron-card h-100 text-center p-4">
          <div class="feature-icon-box mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.8rem; color: var(--rgb-cyan); border-color: rgba(0,229,255,0.3); background: rgba(0,229,255,0.08);">
            <i class="bi bi-people"></i>
          </div>
          <h3 class="h4 text-cyan mb-2">3. Community</h3>
          <p class="text-muted-iron small">
            A welcoming sanctuary where gamers forge lifelong friendships, recruit tournament teammates, and celebrate victories together in a respectful, modern space.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Gamer Comfort & Hospitality Amenities -->
<section class="py-5 section-gta6-bg position-relative" data-bg-url="<?= BASE_URL ?>images/gta6_bg.jpeg" style="background-image: linear-gradient(180deg, rgba(9, 11, 16, 0.40) 0%, rgba(9, 11, 16, 0.16) 50%, rgba(9, 11, 16, 0.48) 100%), url('<?= BASE_URL ?>images/gta6_bg.jpeg'); border-bottom: 1px solid rgba(214, 168, 75, 0.3);">
  <div class="container py-4">
    <div class="text-center mb-5">
      <span class="section-subtitle">Comfort &amp; Hospitality</span>
      <h2 class="display-6 fw-bold mt-2">Arena Amenities &amp; Gamer Perks</h2>
      <p class="text-muted-iron mx-auto" style="max-width: 600px;">
        Designed so you can game for hours without friction.
      </p>
    </div>

    <div class="row g-4">
      <div class="col-md-6 col-lg-3">
        <div class="iron-card p-4 h-100 text-center">
          <div class="feature-icon-box mx-auto mb-3 text-cyan" style="width: 50px; height: 50px; font-size: 1.4rem;">
            <i class="bi bi-tv-fill"></i>
          </div>
          <h5 class="text-light fw-bold mb-2">4K + QLED Screens</h5>
          <p class="text-muted-iron small mb-0">Immersive 4K &amp; high-refresh QLED displays across PC rigs and console bays.</p>
        </div>
      </div>

      <div class="col-md-6 col-lg-3">
        <div class="iron-card p-4 h-100 text-center">
          <div class="feature-icon-box mx-auto mb-3 text-gold" style="width: 50px; height: 50px; font-size: 1.4rem;">
            <i class="bi bi-stars"></i>
          </div>
          <h5 class="text-light fw-bold mb-2">Cozy &amp; Aesthetic Vibe</h5>
          <p class="text-muted-iron small mb-0">Full RGB mood lighting, chilled AC, and ergonomic gaming seating for maximum comfort.</p>
        </div>
      </div>

      <div class="col-md-6 col-lg-3">
        <div class="iron-card p-4 h-100 text-center">
          <div class="feature-icon-box mx-auto mb-3 text-red" style="width: 50px; height: 50px; font-size: 1.4rem;">
            <i class="bi bi-cup-straw"></i>
          </div>
          <h5 class="text-light fw-bold mb-2">Beverage Cans &amp; Packed Food</h5>
          <p class="text-muted-iron small mb-0">Chilled energy drinks, cold drink cans, and packaged snacks readily available on-site.</p>
        </div>
      </div>

      <div class="col-md-6 col-lg-3">
        <div class="iron-card p-4 h-100 text-center">
          <div class="feature-icon-box mx-auto mb-3 text-green" style="width: 50px; height: 50px; font-size: 1.4rem;">
            <i class="bi bi-bag-check-fill"></i>
          </div>
          <h5 class="text-light fw-bold mb-2">Outside Food Allowed</h5>
          <p class="text-muted-iron small mb-0">Feel free to bring your favorite outside food, takeout, or pizza with your squad!</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Call to action -->
<section class="py-5 section-cafe-bg position-relative text-center" data-bg-url="<?= BASE_URL ?>images/cafe_bg.jpeg" style="background-image: linear-gradient(180deg, rgba(9, 11, 16, 0.50) 0%, rgba(9, 11, 16, 0.25) 50%, rgba(9, 11, 16, 0.55) 100%), url('<?= BASE_URL ?>images/cafe_bg.jpeg'); border-top: 1px solid rgba(214, 168, 75, 0.35);">
  <div class="container py-4">
    <h2 class="display-6 fw-bold mb-3">Ready to Join the Arena?</h2>
    <p class="text-muted-iron mx-auto mb-4" style="max-width: 550px;">
      Reserve your preferred station online. Fast UPI confirmation and zero waiting in line.
    </p>
    <a href="<?= BASE_URL ?>book.php" class="btn btn-iron btn-iron-primary">
      <i class="bi bi-controller fs-5"></i> Book Your Session
    </a>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

