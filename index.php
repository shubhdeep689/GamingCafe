<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Official Homepage - Dynamically White-Labeled
 */

require_once __DIR__ . '/config/config.php';

$brand_name     = get_brand_name();
$brand_short    = get_brand_short();
$brand_tagline  = get_brand_tagline();
$brand_city     = get_brand_city();
$brand_state    = get_brand_state();
$brand_address  = get_brand_address();
$brand_logo     = get_brand_logo();
$brand_video    = get_brand_video();

$page_title = e($brand_name) . ' | Gaming Cafe in ' . e($brand_city);
$page_desc = 'Enter the Arena at ' . e($brand_name) . ' in ' . e($brand_city) . '. Elite PC gaming rigs, 4K console lounges, competitive multiplayer tournaments, and instant online session bookings.';

require_once __DIR__ . '/includes/header.php';

$db = get_db();

// 1. Fetch Active Gaming Stations
$stations_stmt = $db->query("
    SELECT s.*, c.name AS category_name, c.icon AS category_icon 
    FROM stations s 
    JOIN station_categories c ON s.category_id = c.id 
    WHERE s.status != 'inactive' 
    ORDER BY s.display_order ASC 
    LIMIT 6
");
$stations = $stations_stmt->fetchAll();

// 2. Fetch Pricing Plans
$pricing_stmt = $db->query("
    SELECT * FROM pricing_plans 
    WHERE is_active = 1 
    ORDER BY display_order ASC 
    LIMIT 4
");
$pricing_plans = $pricing_stmt->fetchAll();

// 3. Fetch Active Offers
$offers_stmt = $db->query("
    SELECT * FROM offers 
    WHERE is_active = 1 
      AND valid_from <= CURDATE() 
      AND valid_until >= CURDATE() 
    ORDER BY id DESC 
    LIMIT 2
");
$active_offers = $offers_stmt->fetchAll();

// 4. Fetch Approved Reviews
$reviews_stmt = $db->query("
    SELECT * FROM reviews 
    WHERE is_approved = 1 
    ORDER BY is_featured DESC, id DESC 
    LIMIT 3
");
$featured_reviews = $reviews_stmt->fetchAll();

// 5. Fetch Gallery Images
$gallery_stmt = $db->query("
    SELECT * FROM gallery_images 
    ORDER BY is_featured DESC, display_order ASC, id DESC 
    LIMIT 6
");
$gallery_items = $gallery_stmt->fetchAll();
?>

<!-- =================================================================
     HERO SECTION
     ================================================================= -->
<section class="hero-section">
  <div class="container position-relative z-2">
    <div class="row align-items-center g-5">
      
      <!-- Left Column: Copy & CTAs -->
      <div class="col-lg-7 hero-content">
        <div class="hero-badge">
          <span class="live-dot"></span>
          <?= e(get_setting('hero_badge', 'Now Open in ' . $brand_city)) ?>
        </div>
        
        <h1 class="hero-title">
          <?= e(get_setting('hero_headline_1', 'ENTER THE ARENA.')) ?><br>
          <span class="title-accent"><?= e(get_setting('hero_headline_2', 'EXPERIENCE THE BATTLE.')) ?></span>
        </h1>
        
        <p class="hero-description">
          <?= e(get_setting('hero_description', 'Your gaming destination in ' . $brand_city . '. Experience esports-grade PC gaming, console lounges, competitive showdowns, and community under one roof.')) ?>
        </p>

        <div class="d-flex flex-wrap gap-3 hero-cta-group">
          <a href="<?= BASE_URL ?>book.php" class="btn btn-iron btn-iron-primary">
            <i class="bi bi-controller fs-5"></i> Book Your Session
          </a>
          <button type="button" class="btn btn-iron btn-iron-cyan btn-tour-hero" data-bs-toggle="modal" data-bs-target="#arenaTourModal">
            <span class="tour-play-badge"><i class="bi bi-play-fill fs-5"></i></span>
            <span>Watch Arena Tour</span>
            <span class="badge bg-danger rounded-pill px-2 py-1 ms-1" style="font-size:0.65rem; letter-spacing:0.04em;">4K VIDEO</span>
          </button>
          <a href="<?= BASE_URL ?>experiences.php" class="btn btn-iron btn-iron-outline">
            <i class="bi bi-compass fs-5"></i> Explore Gaming
          </a>
        </div>

        <!-- Trust Metrics / Highlights -->
        <div class="row g-3 mt-4 pt-3 border-top border-secondary border-opacity-25">
          <div class="col-4">
            <div class="text-gold fw-bold fs-4 brand-font">144Hz+</div>
            <div class="text-muted-iron small">High-Refresh Rigs</div>
          </div>
          <div class="col-4">
            <div class="text-red fw-bold fs-4 brand-font">&lt; 15ms</div>
            <div class="text-muted-iron small">Esports LAN Ping</div>
          </div>
          <div class="col-4">
            <div class="text-cyan fw-bold fs-4 brand-font">100%</div>
            <div class="text-muted-iron small">Real Arena Vibe</div>
          </div>
        </div>

        <!-- Quick Gamer Perks Badges -->
        <div class="d-flex flex-wrap gap-2 mt-3 pt-2">
          <span class="badge bg-dark border border-cyan text-cyan py-1 px-2 small">
            <i class="bi bi-tv-fill me-1"></i> 4K + QLED Screens
          </span>
          <span class="badge bg-dark border border-warning text-gold py-1 px-2 small">
            <i class="bi bi-stars me-1"></i> Cozy &amp; Aesthetic Ambience
          </span>
          <span class="badge bg-dark border border-danger text-danger py-1 px-2 small">
            <i class="bi bi-cup-straw me-1"></i> Beverage Cans &amp; Packed Food
          </span>
          <span class="badge bg-dark border border-success text-success py-1 px-2 small">
            <i class="bi bi-bag-check-fill me-1"></i> Outside Food Allowed
          </span>
        </div>
      </div>

      <!-- Right Column: Dynamic Business Logo Hero Showcase -->
      <div class="col-lg-5 hero-logo-box">
        <div class="hero-logo-card">
          <!-- Dynamic Business Logo with High Fetch Priority for instant LCP -->
          <img src="<?= $brand_logo ?>" alt="<?= e($brand_name) ?> Logo" class="hero-logo-image img-fluid" width="340" height="340" fetchpriority="high" decoding="async" style="filter: drop-shadow(0 0 35px rgba(214,168,75,0.45));">
          <div class="mt-4">
            <h4 class="text-gold mb-1 brand-font" style="letter-spacing: 0.15em;"><?= e($brand_short) ?></h4>
            <p class="text-muted-iron small mb-3"><?= e($brand_tagline) ?></p>
            <div class="d-inline-flex align-items-center gap-2 py-1 px-3 rounded-pill mb-3" style="background: rgba(255,255,255,0.05); border: 1px solid var(--border-color);">
              <i class="bi bi-geo-alt-fill text-red"></i>
              <span class="small text-muted-iron"><?= e($brand_city) ?>, <?= e($brand_state) ?></span>
            </div>

            <!-- Quick Tour Action -->
            <button type="button" class="hero-video-pill" data-bs-toggle="modal" data-bs-target="#arenaTourModal">
              <span class="pill-play-icon"><i class="bi bi-play-fill"></i></span>
              <span class="text-start">
                <span class="d-block text-gold fw-bold small"><i class="bi bi-camera-reels-fill text-cyan me-1"></i> WATCH 4K ARENA TOUR</span>
                <span class="d-block text-muted-iron" style="font-size: 0.72rem;">Official Arena Video Walkthrough</span>
              </span>
              <i class="bi bi-arrow-right-circle text-cyan ms-auto fs-5"></i>
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- =================================================================
     SECTION 2: GAMING EXPERIENCES OVERVIEW (Immersive Cafe Background)
     ================================================================= -->
<section class="py-5 section-cafe-bg position-relative" id="experiences" data-bg-url="<?= BASE_URL ?>images/cafe_bg.jpeg" style="background-image: linear-gradient(180deg, rgba(9, 11, 16, 0.50) 0%, rgba(9, 11, 16, 0.25) 50%, rgba(9, 11, 16, 0.55) 100%), url('<?= BASE_URL ?>images/cafe_bg.jpeg');">
  <div class="container py-4">
    <div class="text-center mb-5">
      <span class="section-subtitle">What We Offer</span>
      <h2 class="display-6 fw-bold mt-2">Elite Gaming Experiences</h2>
      <p class="text-muted-iron mx-auto" style="max-width: 600px;">
        Designed for hardcore competitive players and casual squads alike. Step into battle with zero compromises.
      </p>
    </div>

    <div class="row g-4">
      <!-- Feature 1: PC Gaming -->
      <div class="col-lg-4 col-md-6">
        <div class="iron-card h-100">
          <div class="feature-icon-box">
            <i class="bi bi-pc-display"></i>
          </div>
          <h3 class="h4 text-gold mb-2">Esports PC Arena</h3>
          <p class="text-muted-iron small mb-3">
            High-refresh rate gaming monitors, precision optical sensors, mechanical keyboards, and low-latency gigabit connectivity tailored for Valorant, CS2, Warzone, and Apex Legends.
          </p>
          <a href="<?= BASE_URL ?>experiences.php#pc" class="text-cyan text-decoration-none small fw-bold">
            Explore PC Rigs <i class="bi bi-arrow-right"></i>
          </a>
        </div>
      </div>

      <!-- Feature 2: Console Lounges -->
      <div class="col-lg-4 col-md-6">
        <div class="iron-card h-100">
          <div class="feature-icon-box" style="color: var(--rgb-cyan); border-color: rgba(0,229,255,0.3); background: rgba(0,229,255,0.08);">
            <i class="bi bi-controller"></i>
          </div>
          <h3 class="h4 text-cyan mb-2">Console Lounge Bays</h3>
          <p class="text-muted-iron small mb-3">
            Relax on cozy leather sofas in front of crisp 4K &amp; QLED screens with vibrant HDR. Perfect for FIFA / EA Sports FC tournaments, Mortal Kombat, WWE 2K, and split-screen co-op fun.
          </p>
          <a href="<?= BASE_URL ?>experiences.php#console" class="text-gold text-decoration-none small fw-bold">
            Explore Consoles <i class="bi bi-arrow-right"></i>
          </a>
        </div>
      </div>

      <!-- Feature 3: Multiplayer Squads -->
      <div class="col-lg-4 col-md-6">
        <div class="iron-card h-100">
          <div class="feature-icon-box" style="color: var(--electric-red); border-color: rgba(230,57,70,0.3); background: rgba(230,57,70,0.08);">
            <i class="bi bi-people-fill"></i>
          </div>
          <h3 class="h4 text-red mb-2">Squad LAN Battles</h3>
          <p class="text-muted-iron small mb-3">
            Assemble your 5-stack squad in adjacent battle bays. Crystal-clear voice comms, synced game lobbies, and zero ping lag to dominate tournaments and weekend scrims.
          </p>
          <a href="<?= BASE_URL ?>experiences.php#squad" class="text-red text-decoration-none small fw-bold">
            Explore Squads <i class="bi bi-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- =================================================================
     SECTION 3: ABOUT THE SANCTUARY (GTA VI Cyber Vibe Background)
     ================================================================= -->
<section class="py-5 section-gta6-bg position-relative" id="sanctuary-mission" data-bg-url="<?= BASE_URL ?>images/gta6_bg.jpeg" style="background-image: linear-gradient(180deg, rgba(9, 11, 16, 0.38) 0%, rgba(9, 11, 16, 0.15) 50%, rgba(9, 11, 16, 0.42) 100%), url('<?= BASE_URL ?>images/gta6_bg.jpeg');">
  <div class="container py-4">
    <div class="row align-items-stretch g-5">
      <div class="col-lg-6">
        <div class="iron-card p-4 p-md-5 h-100" style="background: rgba(13, 17, 24, 0.82) !important; backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border: 1px solid rgba(0, 229, 255, 0.3) !important; box-shadow: 0 14px 35px rgba(0, 0, 0, 0.75) !important;">
          <span class="section-subtitle">Our Mission</span>
          <h2 class="display-6 fw-bold mt-2 text-white">A True Gaming Sanctuary in <?= e($brand_city) ?></h2>
          <p class="text-light text-opacity-75 mt-3">
            Founded with a clear vision: <strong class="text-gold"><?= e($brand_tagline) ?></strong>
          </p>
          <p class="text-light text-opacity-75">
            <?= e($brand_short) ?> is not just a cafe with computers—it is an authentic esports hub where gamers in <?= e($brand_city) ?> and surrounding areas can play at peak competitive performance, meet like-minded players, and experience the highest standards of gaming hardware and ambience.
          </p>
          <div class="row g-3 mt-3">
            <div class="col-sm-6">
              <div class="d-flex align-items-start gap-3">
                <i class="bi bi-shield-check text-gold fs-4"></i>
                <div>
                  <h5 class="mb-1 text-gold">Clean & Sanitized</h5>
                  <p class="text-light text-opacity-75 small mb-0">Sanitized gear and ergonomic chairs for long comfortable play.</p>
                </div>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="d-flex align-items-start gap-3">
                <i class="bi bi-lightning-charge text-cyan fs-4"></i>
                <div>
                  <h5 class="mb-1 text-cyan">Low-Latency Network</h5>
                  <p class="text-light text-opacity-75 small mb-0">Dedicated high-speed fiber backhaul with uninterrupted power backup.</p>
                </div>
              </div>
            </div>
          </div>
          <div class="mt-4">
            <a href="<?= BASE_URL ?>about.php" class="btn btn-iron btn-iron-outline btn-iron-sm">
              Read Our Full Story <i class="bi bi-arrow-right ms-1"></i>
            </a>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="iron-card text-center p-4 h-100" style="background: rgba(13, 17, 24, 0.85) !important; backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border: 1px solid rgba(214, 168, 75, 0.35) !important; box-shadow: 0 14px 35px rgba(0, 0, 0, 0.75) !important;">
          <!-- Dynamic Brand Logo Crest -->
          <img src="<?= $brand_logo ?>" alt="<?= e($brand_name) ?> Crest" class="img-fluid mb-4" width="140" height="140" loading="lazy" decoding="async" style="max-height: 140px; filter: drop-shadow(0 0 20px rgba(214,168,75,0.4));">
          <h4 class="text-gold brand-font mb-2">SANCTUARY CODE OF HONOR</h4>
          <p class="text-light text-opacity-75 small mb-4">
            Fair play, high fps, respect for opponents, and zero toxic behavior. Everyone from first-timers to esports veterans is welcome.
          </p>
          <div class="p-3 rounded text-start" style="background: rgba(0,0,0,0.45); border: 1px solid rgba(255,255,255,0.1);">
            <div class="d-flex justify-content-between small text-muted-iron mb-2">
              <span><i class="bi bi-geo-alt text-red me-1"></i> Location:</span>
              <span class="text-light text-end"><?= e($brand_city) ?>, <?= e($brand_state) ?></span>
            </div>
            <div class="d-flex justify-content-between small text-muted-iron mb-2">
              <span><i class="bi bi-clock text-gold me-1"></i> Timing:</span>
              <span class="text-green text-end">10:00 AM – 11:00 PM Daily</span>
            </div>
            <div class="d-flex justify-content-between small text-muted-iron mb-2">
              <span><i class="bi bi-tv text-cyan me-1"></i> Displays:</span>
              <span class="text-light text-end">4K + QLED Screens</span>
            </div>
            <div class="d-flex justify-content-between small text-muted-iron mb-2">
              <span><i class="bi bi-stars text-gold me-1"></i> Ambience:</span>
              <span class="text-light text-end">Cozy &amp; Aesthetic RGB Vibe</span>
            </div>
            <div class="d-flex justify-content-between small text-muted-iron mb-2">
              <span><i class="bi bi-cup-straw text-danger me-1"></i> Refreshments:</span>
              <span class="text-light text-end">Beverage Cans &amp; Packed Food</span>
            </div>
            <div class="d-flex justify-content-between small text-muted-iron mb-2">
              <span><i class="bi bi-bag-check-fill text-green me-1"></i> Outside Food:</span>
              <span class="text-green fw-bold text-end">100% Allowed</span>
            </div>
            <div class="d-flex justify-content-between small text-muted-iron">
              <span><i class="bi bi-qr-code text-cyan me-1"></i> Payments:</span>
              <span class="text-light text-end">UPI QR (GPay / PhonePe / Paytm)</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- =================================================================
     SECTION: ARENA AMENITIES & GAMER PERKS (4K/QLED, Ambience, Food & Drinks)
     ================================================================= -->
<section class="py-5 section-gta6-bg position-relative overflow-hidden" id="amenities" data-bg-url="<?= BASE_URL ?>images/gta6_bg.jpeg" style="background-image: linear-gradient(180deg, rgba(9, 11, 16, 0.40) 0%, rgba(9, 11, 16, 0.16) 50%, rgba(9, 11, 16, 0.48) 100%), url('<?= BASE_URL ?>images/gta6_bg.jpeg'); border-top: 1px solid rgba(0, 229, 255, 0.3); border-bottom: 1px solid rgba(214, 168, 75, 0.3);">
  <div class="container py-4">
    <div class="text-center mb-5">
      <span class="section-subtitle">Comfort &amp; Hospitality</span>
      <h2 class="display-6 fw-bold mt-2 brand-font">Gamer Perks &amp; Arena Amenities</h2>
      <p class="text-muted-iron mx-auto" style="max-width: 650px;">
        Designed for uncompromised comfort, epic visuals, and squad hangouts. Enjoy 4K &amp; QLED screens, cozy aesthetic vibes, in-house chilled beverages, and the freedom to bring your own food!
      </p>
    </div>

    <div class="row g-4">
      <!-- Perk 1: 4K + QLED Screens -->
      <div class="col-lg-3 col-md-6">
        <div class="iron-card h-100 p-4 position-relative overflow-hidden" style="border: 1px solid rgba(0, 229, 255, 0.35); background: rgba(13, 17, 24, 0.82) !important; backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="feature-icon-box text-cyan mb-0" style="width: 52px; height: 52px; font-size: 1.5rem; background: rgba(0, 229, 255, 0.12); border-color: rgba(0, 229, 255, 0.4);">
              <i class="bi bi-tv-fill"></i>
            </div>
            <span class="badge bg-dark border border-cyan text-cyan small fw-bold px-2 py-1">Ultra HD Visuals</span>
          </div>
          <h4 class="h5 text-light fw-bold mb-2">4K + QLED Screens</h4>
          <p class="text-muted-iron small mb-0">
            Razor-sharp 4K Ultra-HD and high-refresh QLED displays with rich HDR colors, low input latency, and immersive viewing angles for both console lounges and PC rigs.
          </p>
        </div>
      </div>

      <!-- Perk 2: Cozy & Aesthetic Ambience -->
      <div class="col-lg-3 col-md-6">
        <div class="iron-card h-100 p-4 position-relative overflow-hidden" style="border: 1px solid rgba(214, 168, 75, 0.35); background: rgba(13, 17, 24, 0.82) !important; backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="feature-icon-box text-gold mb-0" style="width: 52px; height: 52px; font-size: 1.5rem; background: rgba(214, 168, 75, 0.12); border-color: rgba(214, 168, 75, 0.4);">
              <i class="bi bi-stars"></i>
            </div>
            <span class="badge bg-dark border border-warning text-gold small fw-bold px-2 py-1">Esports Lounge</span>
          </div>
          <h4 class="h5 text-light fw-bold mb-2">Cozy &amp; Aesthetic Ambience</h4>
          <p class="text-muted-iron small mb-0">
            Full ambient RGB neon glow, chilled climate control, plush ergonomic seating, and relaxed couch setups. An aesthetic sanctuary built for chill squad gaming or high-stakes matches.
          </p>
        </div>
      </div>

      <!-- Perk 3: Beverage Cans & Packed Food Available -->
      <div class="col-lg-3 col-md-6">
        <div class="iron-card h-100 p-4 position-relative overflow-hidden" style="border: 1px solid rgba(230, 57, 70, 0.35); background: rgba(13, 17, 24, 0.82) !important; backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="feature-icon-box text-red mb-0" style="width: 52px; height: 52px; font-size: 1.5rem; background: rgba(230, 57, 70, 0.12); border-color: rgba(230, 57, 70, 0.4);">
              <i class="bi bi-cup-straw"></i>
            </div>
            <span class="badge bg-dark border border-danger text-danger small fw-bold px-2 py-1">In-House Munchies</span>
          </div>
          <h4 class="h5 text-light fw-bold mb-2">Beverage Cans &amp; Packed Food</h4>
          <p class="text-muted-iron small mb-0">
            Never run out of energy! Ice-cold energy drinks (Red Bull, Monster), chilled soft drink cans, packaged snacks, chips, and quick bites available right at our reception counter.
          </p>
        </div>
      </div>

      <!-- Perk 4: Outside Food is Also Allowed -->
      <div class="col-lg-3 col-md-6">
        <div class="iron-card h-100 p-4 position-relative overflow-hidden" style="border: 1px solid rgba(46, 204, 113, 0.4); background: rgba(13, 17, 24, 0.82) !important; backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="feature-icon-box text-green mb-0" style="width: 52px; height: 52px; font-size: 1.5rem; background: rgba(46, 204, 113, 0.12); border-color: rgba(46, 204, 113, 0.4);">
              <i class="bi bi-bag-check-fill"></i>
            </div>
            <span class="badge bg-dark border border-success text-success small fw-bold px-2 py-1">100% Gamer Friendly</span>
          </div>
          <h4 class="h5 text-light fw-bold mb-2">Outside Food Allowed!</h4>
          <p class="text-muted-iron small mb-0">
            Order your favorite pizza, burgers, rolls, or bring home-cooked food and snacks with your crew. We welcome outside food so you can game on your own schedule!
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- =================================================================
     SECTION 4: GAMING STATIONS OVERVIEW
     ================================================================= -->
<section class="py-5 section-cafe-bg position-relative" id="stations" data-bg-url="<?= BASE_URL ?>images/cafe_bg.jpeg" style="background-image: linear-gradient(180deg, rgba(9, 11, 16, 0.50) 0%, rgba(9, 11, 16, 0.25) 50%, rgba(9, 11, 16, 0.55) 100%), url('<?= BASE_URL ?>images/cafe_bg.jpeg');">
  <div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
      <div>
        <span class="section-subtitle">Real-Time Stations</span>
        <h2 class="display-6 fw-bold mt-2">Arena Stations Overview</h2>
        <p class="text-muted-iron mb-0">Browse current battle stations. Book your favorite station in advance.</p>
      </div>
      <div class="mt-3 mt-md-0">
        <a href="<?= BASE_URL ?>book.php" class="btn btn-iron btn-iron-primary btn-iron-sm">
          <i class="bi bi-calendar-check me-1"></i> Reserve Station
        </a>
      </div>
    </div>

    <div class="row g-4">
      <?php if (!empty($stations)): ?>
        <?php foreach ($stations as $st): ?>
          <div class="col-lg-4 col-md-6">
            <div class="iron-card h-100">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="station-status-pill <?= $st['status'] === 'active' ? 'status-active' : 'status-maintenance' ?>">
                  <i class="bi <?= $st['status'] === 'active' ? 'bi-check-circle-fill' : 'bi-tools' ?>"></i>
                  <?= $st['status'] === 'active' ? 'Operational' : 'Maintenance' ?>
                </span>
                <span class="badge bg-dark border border-secondary text-gold">
                  <?= format_currency($st['hourly_rate']) ?> / hr
                </span>
              </div>

              <h4 class="h5 text-light mb-1"><?= e($st['name']) ?></h4>
              <div class="text-cyan small fw-bold mb-3">
                <i class="bi <?= e($st['category_icon']) ?> me-1"></i> <?= e($st['category_name']) ?>
              </div>

              <p class="text-muted-iron small mb-4" style="min-height: 48px;">
                <?= e($st['specs'] ?? 'High performance gaming equipment and comfortable setup.') ?>
              </p>

              <div class="pt-3 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
                <span class="text-muted-iron small">Code: <strong class="text-light"><?= e($st['code']) ?></strong></span>
                <a href="<?= BASE_URL ?>book.php?station_id=<?= $st['id'] ?>" class="btn btn-iron btn-iron-outline btn-iron-sm">
                  Book This <i class="bi bi-chevron-right ms-1"></i>
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-12">
          <div class="alert alert-dark text-center p-4">
            <i class="bi bi-info-circle me-1 text-gold"></i> Station schedule and equipment details are being configured by the arena owner. Check back shortly!
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- =================================================================
     SECTION 5: PRICING PREVIEW
     ================================================================= -->
<section class="py-5 section-gta6-bg position-relative" id="pricing" data-bg-url="<?= BASE_URL ?>images/gta6_bg.jpeg" style="background-image: linear-gradient(180deg, rgba(9, 11, 16, 0.38) 0%, rgba(9, 11, 16, 0.15) 50%, rgba(9, 11, 16, 0.45) 100%), url('<?= BASE_URL ?>images/gta6_bg.jpeg'); border-top: 1px solid rgba(0, 229, 255, 0.3); border-bottom: 1px solid rgba(214, 168, 75, 0.3);">
  <div class="container py-4">
    <div class="text-center mb-5">
      <span class="section-subtitle">Honest Pricing</span>
      <h2 class="display-6 fw-bold mt-2">Transparent Gaming Passes</h2>
      <p class="text-muted-iron mx-auto" style="max-width: 600px;">
        Pay by the hour or grab a multi-hour battle pass to maximize your game time and savings.
      </p>
    </div>

    <div class="row g-4 justify-content-center">
      <?php if (!empty($pricing_plans)): ?>
        <?php foreach ($pricing_plans as $plan): 
          $features = json_decode($plan['features_json'] ?? '[]', true) ?: [];
        ?>
          <div class="col-lg-3 col-md-6">
            <div class="iron-card pricing-card <?= $plan['is_popular'] ? 'featured' : '' ?>">
              <?php if (!empty($plan['badge'])): ?>
                <div class="pricing-badge-ribbon"><?= e($plan['badge']) ?></div>
              <?php endif; ?>

              <h4 class="h5 text-gold mb-1"><?= e($plan['title']) ?></h4>
              <p class="text-muted-iron small mb-3"><?= $plan['duration_hours'] ?> Hour<?= $plan['duration_hours'] > 1 ? 's' : '' ?> Dedicated Session</p>

              <div class="mb-4">
                <div class="price-amount">
                  <span class="currency">₹</span><?= number_format((float)$plan['price'], 0) ?>
                </div>
                <div class="price-duration">
                  <?php if (!empty($plan['original_price'])): ?>
                    <del class="text-secondary me-1">₹<?= number_format((float)$plan['original_price'], 0) ?></del>
                  <?php endif; ?>
                  Per Player
                </div>
              </div>

              <ul class="list-unstyled mb-4 flex-grow-1">
                <?php foreach ($features as $f): ?>
                  <li class="small text-muted-iron mb-2 d-flex align-items-start gap-2">
                    <i class="bi bi-check2 text-gold"></i>
                    <span><?= e($f) ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>

              <a href="<?= BASE_URL ?>book.php?duration=<?= $plan['duration_hours'] ?>" class="btn btn-iron <?= $plan['is_popular'] ? 'btn-iron-primary' : 'btn-iron-outline' ?> w-100">
                Choose Pass
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="text-center mt-4">
      <a href="<?= BASE_URL ?>pricing.php" class="text-gold text-decoration-none small fw-bold">
        View Full Pricing Details, Squad Rates & FAQs <i class="bi bi-arrow-right"></i>
      </a>
    </div>
  </div>
</section>

<!-- =================================================================
     SECTION 6: SPECIAL OFFERS
     ================================================================= -->
<?php if (!empty($active_offers)): ?>
<section class="py-5 section-cafe-bg position-relative" data-bg-url="<?= BASE_URL ?>images/cafe_bg.jpeg" style="background-image: linear-gradient(180deg, rgba(9, 11, 16, 0.50) 0%, rgba(9, 11, 16, 0.25) 50%, rgba(9, 11, 16, 0.55) 100%), url('<?= BASE_URL ?>images/cafe_bg.jpeg'); border-top: 1px solid rgba(214, 168, 75, 0.35); border-bottom: 1px solid rgba(214, 168, 75, 0.25);">
  <div class="container py-2">
    <div class="iron-card p-4 p-md-5 position-relative overflow-hidden" style="background: rgba(13, 17, 24, 0.85) !important; backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border: 1px solid rgba(214,168,75,0.4);">
      <div class="row align-items-center g-4">
        <div class="col-lg-8">
          <span class="badge bg-danger mb-2 text-uppercase fw-bold letter-spacing-1">Limited Time Arena Promo</span>
          <h3 class="display-6 text-gold fw-bold mb-2"><?= e($active_offers[0]['title']) ?></h3>
          <p class="text-muted-iron mb-3"><?= e($active_offers[0]['description']) ?></p>
          <div class="d-flex flex-wrap align-items-center gap-3">
            <div class="p-2 px-3 rounded text-center" style="background: rgba(0,0,0,0.5); border: 1px dashed var(--gold);">
              <span class="text-muted-iron small d-block">PROMO CODE:</span>
              <strong class="text-gold fs-5 font-monospace"><?= e($active_offers[0]['code']) ?></strong>
            </div>
            <div class="text-muted-iron small">
              Valid until: <strong class="text-light"><?= date('d M Y', strtotime($active_offers[0]['valid_until'])) ?></strong>
            </div>
          </div>
        </div>
        <div class="col-lg-4 text-lg-end">
          <a href="<?= BASE_URL ?>book.php" class="btn btn-iron btn-iron-primary">
            Apply Code & Book Now <i class="bi bi-arrow-right ms-1"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- =================================================================
     SECTION 7: BOOKING CALL TO ACTION
     ================================================================= -->
<section class="py-5 section-gta6-bg position-relative text-center" data-bg-url="<?= BASE_URL ?>images/gta6_bg.jpeg" style="background-image: linear-gradient(180deg, rgba(9, 11, 16, 0.40) 0%, rgba(9, 11, 16, 0.16) 50%, rgba(9, 11, 16, 0.48) 100%), url('<?= BASE_URL ?>images/gta6_bg.jpeg'); border-top: 1px solid rgba(0, 229, 255, 0.35); border-bottom: 1px solid rgba(214, 168, 75, 0.3);">
  <div class="container py-4">
    <div class="p-5 rounded-4 position-relative overflow-hidden" style="background: rgba(13, 17, 24, 0.82) !important; backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border: 1px solid rgba(0, 229, 255, 0.3); box-shadow: 0 16px 40px rgba(0, 0, 0, 0.75);">
      <div class="ambient-glow-wrapper" aria-hidden="true">
        <div class="glow-orb glow-orb-purple" style="width: 300px; height: 300px; top: -50px; left: 10%;"></div>
        <div class="glow-orb glow-orb-cyan" style="width: 300px; height: 300px; bottom: -50px; right: 10%;"></div>
      </div>
      <div class="position-relative z-2">
        <span class="section-subtitle">No Waiting in Line</span>
        <h2 class="display-5 fw-bold mt-2 mb-3">Reserve Your Station Before Arriving</h2>
        <p class="text-muted-iron mx-auto mb-4" style="max-width: 620px;">
          Lock in your preferred PC rig or console lounge. Submit your booking online, complete your UPI payment, and receive your verified booking reference instantly.
        </p>
        <div class="d-flex flex-wrap justify-content-center gap-3">
          <a href="<?= BASE_URL ?>book.php" class="btn btn-iron btn-iron-primary">
            <i class="bi bi-controller fs-5"></i> Book Your Session Now
          </a>
          <a href="<?= BASE_URL ?>check-booking.php" class="btn btn-iron btn-iron-outline">
            <i class="bi bi-search fs-5"></i> Check Existing Booking
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- =================================================================
     SECTION 8: ARENA VISUAL GALLERY & VIDEO REELS (With Expanded View)
     ================================================================= -->
<section class="py-5" id="arena-gallery">
  <div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-end mb-4 pb-2 border-bottom border-secondary border-opacity-25">
      <div>
        <span class="section-subtitle">Visual Experience</span>
        <h2 class="display-6 fw-bold mt-1 text-light brand-font">Battle Arena & Video Reels</h2>
        <p class="text-muted-iron small mb-0">Click any photograph or video clip to open the full-screen expanded player.</p>
      </div>
      <div class="mt-3 mt-md-0">
        <a href="<?= BASE_URL ?>gallery.php" class="btn btn-iron btn-iron-outline btn-iron-sm">
          <i class="bi bi-images me-1"></i> View Full Archive
        </a>
      </div>
    </div>

    <div class="row g-4" id="homeGalleryGrid">
      <?php if (!empty($gallery_items)): ?>
        <?php foreach ($gallery_items as $hIndex => $gItem): ?>
          <?php 
            $is_vid = (($gItem['media_type'] ?? 'image') === 'video'); 
            $media_url = $is_vid ? BASE_URL . $gItem['video_url'] : BASE_URL . $gItem['image_url'];
            $poster_url = BASE_URL . $gItem['image_url'];
          ?>
          <div class="col-lg-4 col-md-6">
            <div class="gallery-interactive-card h-100 d-flex flex-column"
                 data-gallery-item
                 data-index="<?= $hIndex ?>"
                 data-media-type="<?= $is_vid ? 'video' : 'image' ?>"
                 data-media-url="<?= e($media_url) ?>"
                 data-poster-url="<?= e($poster_url) ?>"
                 data-title="<?= e($gItem['title']) ?>"
                 data-caption="<?= e($gItem['caption'] ?? '') ?>"
                 data-category="<?= e(str_replace('_', ' ', $gItem['category'])) ?>">
              
              <div class="position-relative overflow-hidden" style="height: 240px; background: #000;">
                <?php if ($is_vid): ?>
                  <video src="<?= e($media_url) ?>" class="w-100 h-100 object-fit-cover" muted preload="none" playsinline></video>
                  <div class="position-absolute top-50 start-50 translate-middle">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: rgba(9,11,16,0.85); border: 2px solid var(--rgb-cyan); color: #00E5FF; box-shadow: 0 0 15px rgba(0,229,255,0.6);">
                      <i class="bi bi-play-fill fs-3" style="margin-left: 2px;"></i>
                    </div>
                  </div>
                  <span class="position-absolute top-3 start-3 badge bg-danger text-white shadow-sm" style="font-size: 0.65rem;">
                    <i class="bi bi-camera-reels me-1"></i> VIDEO
                  </span>
                <?php else: ?>
                  <img src="<?= e($poster_url) ?>" alt="<?= e($gItem['title']) ?>" class="w-100 h-100 object-fit-cover" width="400" height="240" loading="lazy" decoding="async">
                  <span class="position-absolute top-3 start-3 badge bg-dark border border-secondary text-gold shadow-sm" style="font-size: 0.65rem;">
                    <i class="bi bi-image me-1"></i> PHOTO
                  </span>
                <?php endif; ?>

                <div class="gallery-zoom-overlay">
                  <div class="text-center text-light">
                    <i class="bi <?= $is_vid ? 'bi-play-circle-fill' : 'bi-arrows-fullscreen' ?> fs-1 text-gold mb-1 d-block"></i>
                    <span class="small text-uppercase fw-bold brand-font" style="letter-spacing: 0.08em;">
                      <?= $is_vid ? 'Play Video' : 'Expand View' ?>
                    </span>
                  </div>
                </div>
              </div>

              <div class="p-3 flex-grow-1 d-flex flex-column justify-content-between" style="background: #11151E;">
                <div>
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="badge bg-secondary bg-opacity-35 text-gold small text-uppercase" style="font-size: 0.65rem;">
                      <?= e(str_replace('_', ' ', $gItem['category'])) ?>
                    </span>
                    <span class="text-cyan small" style="font-size: 0.72rem;"><i class="bi bi-arrows-angle-expand me-1"></i> Click to expand</span>
                  </div>
                  <h5 class="text-light mb-1 fs-6 fw-bold"><?= e($gItem['title']) ?></h5>
                </div>
              </div>

            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- =================================================================
     SECTION 9: CUSTOMER REVIEWS
     ================================================================= -->
<section class="py-5 section-cafe-bg position-relative" data-bg-url="<?= BASE_URL ?>images/cafe_bg.jpeg" style="background-image: linear-gradient(180deg, rgba(9, 11, 16, 0.50) 0%, rgba(9, 11, 16, 0.25) 50%, rgba(9, 11, 16, 0.55) 100%), url('<?= BASE_URL ?>images/cafe_bg.jpeg'); border-top: 1px solid rgba(214, 168, 75, 0.35); border-bottom: 1px solid rgba(214, 168, 75, 0.25);">
  <div class="container py-4">
    <div class="text-center mb-5">
      <span class="section-subtitle">Verified Feedback</span>
      <h2 class="display-6 fw-bold mt-2">What Gamers Are Saying</h2>
      <p class="text-muted-iron mx-auto" style="max-width: 600px;">
        Real reviews from competitive and casual players who stepped into the <?= e($brand_short) ?> sanctuary.
      </p>
    </div>

    <div class="row g-4">
      <?php if (!empty($featured_reviews)): ?>
        <?php foreach ($featured_reviews as $rev): ?>
          <div class="col-lg-4 col-md-6">
            <div class="iron-card h-100 d-flex flex-column">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="rating-stars">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="bi <?= $i <= $rev['rating'] ? 'bi-star-fill' : 'bi-star' ?>"></i>
                  <?php endfor; ?>
                </div>
                <span class="badge bg-secondary bg-opacity-25 text-gold small">Verified Player</span>
              </div>
              <p class="text-muted-iron small flex-grow-1 italic">
                "<?= e($rev['review_text']) ?>"
              </p>
              <div class="pt-3 border-top border-secondary border-opacity-25 mt-2">
                <strong class="text-light d-block"><?= e($rev['customer_name']) ?></strong>
                <?php if (!empty($rev['game_played'])): ?>
                  <span class="text-cyan small"><i class="bi bi-joystick me-1"></i> Played <?= e($rev['game_played']) ?></span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-12 text-center text-muted-iron small">
          Customer reviews will appear here once approved by the arena admin.
        </div>
      <?php endif; ?>
    </div>

    <div class="text-center mt-4">
      <a href="<?= BASE_URL ?>reviews.php" class="btn btn-iron btn-iron-outline btn-iron-sm">
        <i class="bi bi-pencil-square me-1"></i> Read All Reviews / Submit Yours
      </a>
    </div>
  </div>
</section>

<!-- =================================================================
     SECTION 10: LOCATION & DIRECTIONS
     ================================================================= -->
<section class="py-5 section-gta6-bg position-relative" id="location" data-bg-url="<?= BASE_URL ?>images/gta6_bg.jpeg" style="background-image: linear-gradient(180deg, rgba(9, 11, 16, 0.40) 0%, rgba(9, 11, 16, 0.16) 50%, rgba(9, 11, 16, 0.48) 100%), url('<?= BASE_URL ?>images/gta6_bg.jpeg'); border-top: 1px solid rgba(0, 229, 255, 0.35);">
  <div class="container py-4">
    <div class="row g-5 align-items-center">
      <div class="col-lg-5">
        <span class="section-subtitle">Find Us</span>
        <h2 class="display-6 fw-bold mt-2">Locate the Arena</h2>
        <p class="text-muted-iron mb-4">
          Centrally located in <?= e($brand_city) ?>, easy to reach with convenient parking for bikes and cars.
        </p>

        <div class="iron-card p-4 mb-3" style="background: rgba(13, 17, 24, 0.85) !important; backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);">
          <div class="d-flex align-items-start gap-3 mb-3">
            <div class="feature-icon-box text-red mb-0" style="width: 44px; height: 44px; font-size: 1.2rem;">
              <i class="bi bi-geo-alt-fill"></i>
            </div>
            <div>
              <h5 class="text-gold mb-1">Confirmed Arena Address</h5>
              <p class="text-muted-iron small mb-0">
                <?= nl2br(e($brand_address)) ?>
              </p>
            </div>
          </div>

          <div class="d-flex align-items-start gap-3 mb-3">
            <div class="feature-icon-box text-green mb-0" style="width: 44px; height: 44px; font-size: 1.2rem;">
              <i class="bi bi-clock-history"></i>
            </div>
            <div>
              <h5 class="text-green mb-1">Operating Hours</h5>
              <p class="text-muted-iron small mb-0">
                Monday – Sunday: 10:00 AM – 11:00 PM<br>
                Open on all major weekends and holidays.
              </p>
            </div>
          </div>

          <div class="d-flex align-items-start gap-3">
            <div class="feature-icon-box text-cyan mb-0" style="width: 44px; height: 44px; font-size: 1.2rem;">
              <i class="bi bi-info-circle-fill"></i>
            </div>
            <div>
              <h5 class="text-cyan mb-1">Landmark Note</h5>
              <p class="text-muted-iron small mb-0">
                Located right in <?= e($brand_city) ?>, <?= e($brand_state) ?> with easy road connectivity and high-speed facilities.
              </p>
            </div>
          </div>
        </div>

        <div class="d-flex gap-2">
          <a href="https://maps.google.com/?q=<?= urlencode($brand_address) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-iron btn-iron-primary btn-iron-sm w-100">
            <i class="bi bi-geo-fill me-1"></i> Get Google Maps Directions
          </a>
        </div>
      </div>

      <!-- Map Embed Column -->
      <div class="col-lg-7">
        <div class="rounded-4 overflow-hidden shadow-lg border border-secondary border-opacity-25" style="min-height: 400px; height: 100%;">
          <iframe 
            src="<?= e(get_setting('maps_embed_url', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d13788.134560790832!2d74.5029415!3d30.477028!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39174542ee59815d%3A0xe6bf448f43058a28!2sSri%20Muktsar%20Sahib%2C%20Punjab%20152026!5e0!3m2!1sen!2sin!4v1700000000000')) ?>" 
            width="100%" 
            height="420" 
            style="border:0; filter: grayscale(80%) invert(90%) contrast(85%);" 
            allowfullscreen="" 
            loading="lazy" 
            referrerpolicy="no-referrer-when-downgrade"
            title="<?= e($brand_name) ?> Location">
          </iframe>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- =================================================================
     MODAL: ARENA VIDEO TOUR TEASER
     ================================================================= -->
<div class="modal fade" id="arenaTourModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="background: #0E121A; border: 1px solid rgba(0,229,255,0.4); box-shadow: 0 0 40px rgba(0,229,255,0.25);">
      <div class="modal-header border-secondary border-opacity-25 py-2">
        <h5 class="modal-title text-cyan brand-font mb-0"><i class="bi bi-camera-reels me-2"></i><?= e($brand_short) ?> Arena Tour</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="document.getElementById('arenaTourVideo').pause()"></button>
      </div>
      <div class="modal-body p-0 bg-black">
        <video id="arenaTourVideo" data-src="<?= $brand_video ?>" preload="none" class="w-100" controls playsinline style="max-height: 70vh; outline: none;"></video>
      </div>
      <div class="modal-footer border-secondary border-opacity-25 py-2 justify-content-between">
        <small class="text-muted-iron"><i class="bi bi-geo-alt text-red me-1"></i> <?= e($brand_address) ?></small>
        <a href="<?= BASE_URL ?>book.php" class="btn btn-iron btn-iron-primary btn-iron-sm">
          <i class="bi bi-controller me-1"></i> Book A Station Now
        </a>
      </div>
    </div>
  </div>
</div>

<!-- =================================================================
     EXPANDED FULLSCREEN LIGHTBOX MODAL (Homepage)
     ================================================================= -->
<div id="ironcladLightbox" class="ironclad-lightbox" role="dialog" aria-modal="true" aria-label="Expanded Media View">
  <button type="button" class="lightbox-close-btn" id="lightboxCloseBtn" title="Close Expanded View (Esc)">
    <i class="bi bi-x-lg"></i>
  </button>
  <button type="button" class="lightbox-nav-btn lightbox-prev" id="lightboxPrevBtn" title="Previous (Left Arrow)">
    <i class="bi bi-chevron-left"></i>
  </button>
  <button type="button" class="lightbox-nav-btn lightbox-next" id="lightboxNextBtn" title="Next (Right Arrow)">
    <i class="bi bi-chevron-right"></i>
  </button>

  <div class="lightbox-content-box">
    <div class="lightbox-media-wrapper" id="lightboxMediaArea"></div>
    <div class="lightbox-info-bar">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <span class="badge bg-gold text-dark fw-bold text-uppercase" id="lightboxCategory" style="font-size: 0.7rem;"></span>
          <span class="text-muted-iron small" id="lightboxCounter"></span>
        </div>
        <h4 class="text-light brand-font mb-0" id="lightboxTitle"></h4>
        <p class="text-muted-iron small mb-0 mt-1" id="lightboxCaption"></p>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= BASE_URL ?>book.php" class="btn btn-iron btn-iron-primary btn-iron-sm">
          <i class="bi bi-controller me-1"></i> Book Station
        </a>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const items = Array.from(document.querySelectorAll('#homeGalleryGrid [data-gallery-item]'));
  if (!items.length) return;

  const lightbox = document.getElementById('ironcladLightbox');
  const mediaArea = document.getElementById('lightboxMediaArea');
  const titleEl = document.getElementById('lightboxTitle');
  const captionEl = document.getElementById('lightboxCaption');
  const catEl = document.getElementById('lightboxCategory');
  const counterEl = document.getElementById('lightboxCounter');
  const closeBtn = document.getElementById('lightboxCloseBtn');
  const prevBtn = document.getElementById('lightboxPrevBtn');
  const nextBtn = document.getElementById('lightboxNextBtn');

  let currentIndex = 0;

  function openLightbox(index) {
    currentIndex = (index + items.length) % items.length;
    const el = items[currentIndex];
    const type = el.getAttribute('data-media-type');
    const url = el.getAttribute('data-media-url');
    const poster = el.getAttribute('data-poster-url');
    const title = el.getAttribute('data-title');
    const caption = el.getAttribute('data-caption');
    const category = el.getAttribute('data-category');

    mediaArea.innerHTML = '';
    if (type === 'video') {
      const vid = document.createElement('video');
      vid.src = url;
      vid.controls = true;
      vid.autoplay = true;
      vid.playsInline = true;
      vid.className = 'w-100 h-100';
      if (poster) vid.poster = poster;
      mediaArea.appendChild(vid);
    } else {
      const img = document.createElement('img');
      img.src = url;
      img.alt = title;
      img.className = 'img-fluid';
      mediaArea.appendChild(img);
    }

    titleEl.textContent = title || '<?= e($brand_short) ?> Sanctuary';
    captionEl.textContent = caption || '';
    catEl.textContent = category || 'ARENA';
    counterEl.textContent = `Media ${currentIndex + 1} of ${items.length}`;

    lightbox.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeLightbox() {
    lightbox.classList.remove('active');
    document.body.style.overflow = '';
    const vid = mediaArea.querySelector('video');
    if (vid) vid.pause();
    mediaArea.innerHTML = '';
  }

  items.forEach((item, idx) => {
    item.addEventListener('click', () => openLightbox(idx));
  });

  closeBtn.addEventListener('click', closeLightbox);
  prevBtn.addEventListener('click', () => openLightbox(currentIndex - 1));
  nextBtn.addEventListener('click', () => openLightbox(currentIndex + 1));

  lightbox.addEventListener('click', (e) => {
    if (e.target === lightbox) closeLightbox();
  });

  window.addEventListener('keydown', (e) => {
    if (!lightbox.classList.contains('active')) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') openLightbox(currentIndex - 1);
    if (e.key === 'ArrowRight') openLightbox(currentIndex + 1);
  });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

