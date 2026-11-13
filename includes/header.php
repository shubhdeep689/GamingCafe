<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Public Layout Header
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$page_title = $page_title ?? get_setting('meta_title', 'IRONCLAD GAMING SANCTUARY | Gaming Cafe in Sri Muktsar Sahib');
$page_desc = $page_desc ?? get_setting('meta_description', 'Visit IRONCLAD GAMING SANCTUARY in Guru Angad Dev Nagar, Sri Muktsar Sahib. Experience esports PC gaming, console lounges, competitive tournaments, and instant online session booking.');
$page_keywords = $page_keywords ?? get_setting('meta_keywords', 'Gaming Cafe in Sri Muktsar Sahib, Gaming Zone in Muktsar, Gaming Lounge in Sri Muktsar Sahib, PC Gaming in Muktsar, Multiplayer Gaming in Muktsar');
$current_page = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$canonical_url = BASE_URL . ($current_page === 'index.php' ? '' : $current_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title) ?></title>
  <meta name="description" content="<?= e($page_desc) ?>">
  <meta name="keywords" content="<?= e($page_keywords) ?>">
  <meta name="author" content="<?= e(get_brand_name()) ?>">
  <link rel="canonical" href="<?= e($canonical_url) ?>">

  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="business.business">
  <meta property="og:title" content="<?= e($page_title) ?>">
  <meta property="og:description" content="<?= e($page_desc) ?>">
  <meta property="og:image" content="<?= get_brand_logo() ?>">
  <meta property="og:url" content="<?= e($canonical_url) ?>">
  <meta property="og:site_name" content="<?= e(get_brand_name()) ?>">

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($page_title) ?>">
  <meta name="twitter:description" content="<?= e($page_desc) ?>">
  <meta name="twitter:image" content="<?= BASE_URL ?>images/NOBG_IRONCLADLOGO.png">

  <!-- Google Fonts & Iconsets -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700;800&family=Segoe+UI:wght@400;600;700&display=swap" rel="stylesheet">
  
  <!-- Bootstrap 5 CSS & Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <!-- Ironclad Theme CSS (Cache-Busted for Immediate Styling Updates) -->
  <link rel="stylesheet" href="<?= ASSETS_URL ?>css/style.css?v=<?= APP_VERSION ?>_<?= time() ?>">

  <?php if ($current_page === 'index.php' || $current_page === ''): ?>
  <!-- Preload LCP Hero Image for Instant Mobile Rendering -->
  <link rel="preload" as="image" href="<?= get_brand_logo() ?>" fetchpriority="high">
  <?php endif; ?>

  <!-- Favicon using authentic logo -->
  <link rel="icon" type="image/png" href="<?= get_brand_logo() ?>">
  <script>window.BASE_URL = '<?= BASE_URL ?>';</script>

  <!-- Schema.org Structured Data (LocalBusiness) -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "EntertainmentBusiness",
    "name": "<?= e(get_brand_name()) ?>",
    "image": "<?= get_brand_logo() ?>",
    "description": "<?= e($page_desc) ?>",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "<?= e(get_brand_address()) ?>",
      "addressLocality": "<?= e(get_brand_city()) ?>",
      "addressRegion": "<?= e(get_brand_state()) ?>",
      "postalCode": "<?= e(get_brand_pincode()) ?>",
      "addressCountry": "IN"
    },
    "geo": {
      "@type": "GeoCoordinates",
      "latitude": 30.477028,
      "longitude": 74.502942
    },
    "url": "<?= BASE_URL ?>",
    "priceRange": "₹₹",
    "openingHoursSpecification": [
      {
        "@type": "OpeningHoursSpecification",
        "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
        "opens": "10:00",
        "closes": "23:00"
      }
    ]
  }
  </script>
</head>
<body>

  <!-- Ambient RGB Background Lighting (Restrained) -->
  <div class="ambient-glow-wrapper" aria-hidden="true">
    <div class="glow-orb glow-orb-cyan"></div>
    <div class="glow-orb glow-orb-purple"></div>
    <div class="glow-orb glow-orb-red"></div>
    <div class="glow-orb glow-orb-gold"></div>
  </div>

  <!-- Primary Ironclad Navigation Bar -->
  <nav class="navbar navbar-expand-lg iron-navbar sticky-top">
    <div class="container-fluid px-lg-4">
      <a class="brand-logo-container" href="<?= BASE_URL ?>index.php" title="<?= e(get_brand_name()) ?>">
        <!-- Authentic Transparent Business Logo -->
        <img src="<?= get_brand_logo() ?>" alt="<?= e(get_brand_name()) ?>" class="brand-logo-img">
        <div class="d-none d-sm-block">
          <div class="brand-title"><?= e(get_brand_short()) ?></div>
          <div class="brand-location-badge"><?= e(get_brand_city()) ?></div>
        </div>
      </a>

      <!-- Mobile Navigation Hamburger -->
      <button class="navbar-toggler border-0 text-gold" type="button" data-bs-toggle="collapse" data-bs-target="#ironcladNav" aria-controls="ironcladNav" aria-expanded="false" aria-label="Toggle navigation">
        <i class="bi bi-list fs-2"></i>
      </button>

      <!-- Streamlined Modern Navigation Links (Ultra-Compact Shortened Navbar) -->
      <div class="collapse navbar-collapse" id="ironcladNav">
        <ul class="navbar-nav mx-auto mb-3 mb-lg-0 gap-lg-1 align-items-lg-center">
          <li class="nav-item">
            <a class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>index.php">
              Home
            </a>
          </li>

          <!-- Arena Dropdown (Gathers Rigs, Offers, Gallery, and Reviews to keep navbar ultra-short) -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?= in_array($current_page, ['experiences.php', 'offers.php', 'gallery.php', 'reviews.php']) ? 'active' : '' ?>" href="#" id="arenaDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              Arena
            </a>
            <ul class="dropdown-menu dropdown-menu-dark iron-dropdown shadow-lg py-2" aria-labelledby="arenaDropdown">
              <li>
                <a class="dropdown-item py-2 d-flex align-items-center gap-2 <?= $current_page === 'experiences.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>experiences.php">
                  <i class="bi bi-joystick text-cyan fs-5"></i>
                  <div>
                    <div class="fw-bold">Rigs &amp; Arena</div>
                    <small class="text-muted-iron d-block" style="font-size: 0.72rem;">RTX 4080s, PS5 Bays, Racing Sims</small>
                  </div>
                </a>
              </li>
              <li>
                <a class="dropdown-item py-2 d-flex align-items-center gap-2 <?= $current_page === 'offers.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>offers.php">
                  <i class="bi bi-fire text-red fs-5"></i>
                  <div>
                    <div class="fw-bold d-flex align-items-center">
                      Special Offers
                      <span class="badge bg-danger ms-2" style="font-size: 0.65rem; padding: 2px 5px;">HOT</span>
                    </div>
                    <small class="text-muted-iron d-block" style="font-size: 0.72rem;">Battle passes, student deals &amp; tournaments</small>
                  </div>
                </a>
              </li>
              <li>
                <a class="dropdown-item py-2 d-flex align-items-center gap-2 <?= $current_page === 'gallery.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>gallery.php">
                  <i class="bi bi-images text-gold fs-5"></i>
                  <div>
                    <div class="fw-bold">Arena Gallery</div>
                    <small class="text-muted-iron d-block" style="font-size: 0.72rem;">Photos, setup views &amp; video clips</small>
                  </div>
                </a>
              </li>
              <li>
                <a class="dropdown-item py-2 d-flex align-items-center gap-2 <?= $current_page === 'reviews.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>reviews.php">
                  <i class="bi bi-star-fill text-gold fs-5"></i>
                  <div>
                    <div class="fw-bold d-flex align-items-center">
                      Player Reviews
                      <span class="badge bg-gold text-dark ms-2" style="font-size: 0.65rem; padding: 2px 5px;">4.9★</span>
                    </div>
                    <small class="text-muted-iron d-block" style="font-size: 0.72rem;">Gamer feedback &amp; community ratings</small>
                  </div>
                </a>
              </li>
            </ul>
          </li>

          <li class="nav-item">
            <a class="nav-link <?= $current_page === 'pricing.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>pricing.php">
              Pricing
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $current_page === 'about.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>about.php">
              About
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $current_page === 'contact.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>contact.php">
              Contact
            </a>
          </li>
        </ul>

        <!-- Action Group: Sleek, High-End Esports Pro Alignment -->
        <div class="iron-nav-actions d-flex align-items-center gap-2 mt-3 mt-lg-0">
          <div class="iron-nav-subgroup d-flex align-items-center gap-2">
            <!-- Check Pass Action Button (Golden Outline & Cyber Accent) -->
            <a href="<?= BASE_URL ?>check-booking.php" class="iron-pass-btn" title="Lookup or verify your booking pass">
              <i class="bi bi-ticket-perforated-fill text-gold fs-6"></i>
              <span>Check Pass</span>
            </a>

            <!-- Gamer Sign In / Profile Dropdown ("Account List") -->
            <?php if (is_customer_logged_in()): ?>
              <div class="dropdown">
                <button class="iron-account-btn dropdown-toggle border-0" type="button" id="gamerAccountDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Gamer Account Menu">
                  <span class="pulse-dot"></span>
                  <i class="bi bi-person-badge-fill text-gold fs-6"></i>
                  <span><?= e(explode(' ', trim($_SESSION['customer_name'] ?? 'Gamer'))[0]) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end iron-account-dropdown shadow-lg py-2" aria-labelledby="gamerAccountDropdown">
                  <li class="dropdown-header-box">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                      <span class="badge bg-gold text-dark font-monospace fw-bold" style="font-size: 0.68rem; letter-spacing: 0.08em;">
                        <i class="bi bi-patch-check-fill me-1"></i>VERIFIED GAMER
                      </span>
                      <span class="badge bg-black text-cyan border border-info font-monospace" style="font-size: 0.68rem;">
                        ID #<?= (int)($_SESSION['customer_user_id'] ?? 0) ?>
                      </span>
                    </div>
                    <div class="fw-bold text-light brand-font fs-6 text-truncate"><?= e($_SESSION['customer_name'] ?? 'Player') ?></div>
                    <div class="text-muted-iron mt-1" style="font-size: 0.74rem;">
                      <i class="bi bi-telephone-fill text-gold me-1"></i> +91 <?= e($_SESSION['customer_phone'] ?? '') ?>
                    </div>
                  </li>
                  <li>
                    <a class="dropdown-item py-2" href="<?= BASE_URL ?>my-account.php">
                      <span class="menu-icon-box" style="background: rgba(0,229,255,0.1); border-color: rgba(0,229,255,0.25);"><i class="bi bi-speedometer2 text-cyan"></i></span>
                      <div>
                        <div class="fw-bold text-light">My Passes &amp; Bookings</div>
                        <small class="text-muted-iron d-block" style="font-size: 0.7rem;">Active sessions &amp; receipts</small>
                      </div>
                    </a>
                  </li>
                  <li>
                    <a class="dropdown-item py-2" href="<?= BASE_URL ?>check-booking.php">
                      <span class="menu-icon-box" style="background: rgba(214,168,75,0.1); border-color: rgba(214,168,75,0.25);"><i class="bi bi-ticket-perforated-fill text-gold"></i></span>
                      <div>
                        <div class="fw-bold text-light">Fast Pass Verification</div>
                        <small class="text-muted-iron d-block" style="font-size: 0.7rem;">Lookup with reference ID</small>
                      </div>
                    </a>
                  </li>
                  <li>
                    <a class="dropdown-item py-2" href="<?= BASE_URL ?>book.php">
                      <span class="menu-icon-box" style="background: rgba(16,185,129,0.1); border-color: rgba(16,185,129,0.25);"><i class="bi bi-controller text-green"></i></span>
                      <div>
                        <div class="fw-bold text-light">Reserve Next Slot</div>
                        <small class="text-muted-iron d-block" style="font-size: 0.7rem;">Pick PC rig or console bay</small>
                      </div>
                    </a>
                  </li>
                  <li>
                    <a class="dropdown-item py-2" href="<?= BASE_URL ?>my-account.php#profileModal">
                      <span class="menu-icon-box" style="background: rgba(168,85,247,0.1); border-color: rgba(168,85,247,0.25);"><i class="bi bi-gear-fill text-info"></i></span>
                      <div>
                        <div class="fw-bold text-light">Account &amp; Security</div>
                        <small class="text-muted-iron d-block" style="font-size: 0.7rem;">Profile &amp; password change</small>
                      </div>
                    </a>
                  </li>
                  <li><hr class="dropdown-divider border-secondary border-opacity-25 my-1"></li>
                  <li>
                    <a class="dropdown-item py-2 text-danger" href="<?= BASE_URL ?>logout.php">
                      <span class="menu-icon-box" style="background: rgba(230,57,70,0.15); border-color: rgba(230,57,70,0.3);"><i class="bi bi-box-arrow-right text-danger"></i></span>
                      <span class="fw-bold text-danger">Sign Out</span>
                    </a>
                  </li>
                </ul>
              </div>
            <?php else: ?>
              <a href="<?= BASE_URL ?>login.php" class="iron-signin-btn" title="Player Sign In">
                <i class="bi bi-person-fill text-gold fs-6"></i>
                <span>Sign In</span>
              </a>
            <?php endif; ?>
          </div>

          <!-- Standout Primary Booking CTA (Solid Golden Glow & Commanding) -->
          <a href="<?= BASE_URL ?>book.php" class="iron-book-btn" title="Reserve Your Gaming Station">
            <i class="bi bi-controller fs-5 text-dark"></i>
            <span>Book Rig</span>
          </a>
        </div>
      </div>
    </div>
  </nav>

  <!-- Main Content Wrapper -->
  <main class="site-main-content">

