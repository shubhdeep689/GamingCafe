<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Public Layout Header
 */

require_once dirname(__DIR__) . '/config/config.php';

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
  
  <!-- Ironclad Theme CSS -->
  <link rel="stylesheet" href="<?= ASSETS_URL ?>css/style.css?v=<?= APP_VERSION ?>">

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

      <!-- Streamlined Modern Navigation Links -->
      <div class="collapse navbar-collapse" id="ironcladNav">
        <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-lg-1">
          <li class="nav-item">
            <a class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>index.php">
              <i class="bi bi-house-door me-1 d-none d-xl-inline"></i> Home
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $current_page === 'pricing.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>pricing.php">
              <i class="bi bi-cpu me-1 d-none d-xl-inline"></i> Stations & Rates
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $current_page === 'about.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>about.php">
              About
            </a>
          </li>
          
          <!-- Explore Dropdown (Neatly gathers secondary pages so navbar stays spacious & sleek) -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?= in_array($current_page, ['experiences.php', 'gallery.php', 'reviews.php', 'offers.php']) ? 'active' : '' ?>" href="#" id="exploreDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              Explore Arena
            </a>
            <ul class="dropdown-menu dropdown-menu-dark iron-dropdown shadow-lg border-secondary border-opacity-25" aria-labelledby="exploreDropdown">
              <li>
                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?= BASE_URL ?>index.php#arena-tour">
                  <i class="bi bi-camera-reels-fill text-cyan"></i>
                  <div>
                    <div class="fw-bold text-cyan">Watch Arena Tour</div>
                    <small class="text-muted d-block" style="font-size: 0.72rem;">Authentic 4K Video Walkthrough</small>
                  </div>
                </a>
              </li>
              <li>
                <a class="dropdown-item py-2 d-flex align-items-center gap-2 <?= $current_page === 'experiences.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>experiences.php">
                  <i class="bi bi-joystick text-cyan"></i>
                  <div>
                    <div class="fw-bold">Gaming Experiences</div>
                    <small class="text-muted d-block" style="font-size: 0.72rem;">PC Arena, VIP Console Bays & VR</small>
                  </div>
                </a>
              </li>
              <li>
                <a class="dropdown-item py-2 d-flex align-items-center gap-2 <?= $current_page === 'gallery.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>gallery.php">
                  <i class="bi bi-play-btn-fill text-gold"></i>
                  <div>
                    <div class="fw-bold">Arena Gallery & Reels</div>
                    <small class="text-muted d-block" style="font-size: 0.72rem;">Photos, Setup Views & Gameplay Clips</small>
                  </div>
                </a>
              </li>
              <li>
                <a class="dropdown-item py-2 d-flex align-items-center gap-2 <?= $current_page === 'reviews.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>reviews.php">
                  <i class="bi bi-star-fill text-warning"></i>
                  <div>
                    <div class="fw-bold">Player Reviews</div>
                    <small class="text-muted d-block" style="font-size: 0.72rem;">Community Feedback & Ratings</small>
                  </div>
                </a>
              </li>
              <li><hr class="dropdown-divider border-secondary border-opacity-25"></li>
              <li>
                <a class="dropdown-item py-2 d-flex align-items-center gap-2 <?= $current_page === 'offers.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>offers.php">
                  <i class="bi bi-fire text-red"></i>
                  <div>
                    <div class="fw-bold">Special Offers</div>
                    <small class="text-muted d-block" style="font-size: 0.72rem;">Battle Pass Discounts & Night Owls</small>
                  </div>
                </a>
              </li>
            </ul>
          </li>

          <li class="nav-item">
            <a class="nav-link <?= $current_page === 'contact.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>contact.php">
              Contact
            </a>
          </li>
        </ul>

        <!-- Action CTAs: High Contrast, Compact, Esports Aesthetic -->
        <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
          <a href="<?= BASE_URL ?>check-booking.php" class="btn btn-iron btn-iron-outline btn-iron-sm" title="Lookup or reschedule your session">
            <i class="bi bi-ticket-perforated text-gold me-1"></i> Check Booking
          </a>
          <a href="<?= BASE_URL ?>book.php" class="btn btn-iron btn-iron-primary btn-iron-sm">
            <i class="bi bi-controller me-1"></i> Book Station
          </a>
        </div>
      </div>
    </div>
  </nav>

  <!-- Main Content Wrapper -->
  <main class="site-main-content">

