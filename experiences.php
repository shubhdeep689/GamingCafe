<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Gaming Experiences Page - Dynamically White-Labeled
 */

require_once __DIR__ . '/config/config.php';

$brand_name  = get_brand_name();
$brand_short = get_brand_short();
$brand_city  = get_brand_city();

$page_title = 'Gaming Experiences | PC Rigs, Console Lounges & Squad Battles | ' . $brand_short;
$page_desc = 'Explore esports PC battle stations, 4K console lounges, and competitive multiplayer tournament rigs at ' . $brand_name . ' in ' . $brand_city . '.';

require_once __DIR__ . '/includes/header.php';

$db = get_db();

// Fetch categories with stations
$categories_stmt = $db->query("SELECT * FROM station_categories WHERE is_active = 1 ORDER BY display_order ASC");
$categories = $categories_stmt->fetchAll();

$stations_by_category = [];
$all_stations = $db->query("SELECT * FROM stations WHERE status != 'inactive' ORDER BY display_order ASC")->fetchAll();
foreach ($all_stations as $s) {
    $stations_by_category[$s['category_id']][] = $s;
}
?>

<!-- Page Banner -->
<section class="py-5 position-relative overflow-hidden" style="background: radial-gradient(circle at 50% 20%, rgba(34, 43, 61, 0.6) 0%, var(--bg-primary) 80%); border-bottom: 1px solid var(--border-color);">
  <div class="container py-4 text-center position-relative z-2">
    <span class="section-subtitle">Arena Hardware & Setups</span>
    <h1 class="display-5 fw-bold mt-2">Gaming Experiences</h1>
    <p class="text-muted-iron mx-auto" style="max-width: 650px;">
      From razor-sharp 4K &amp; QLED screens to cozy aesthetic lounges, discover setups crafted for every genre in <?= e($brand_city) ?>. Outside food is welcome and chilled beverage cans &amp; snacks are available on-site!
    </p>
  </div>
</section>

<!-- Experience Sections -->
<div class="container py-5">
  
  <!-- Category Loop -->
  <?php foreach ($categories as $index => $cat): 
    $cat_stations = $stations_by_category[$cat['id']] ?? [];
    $is_even = ($index % 2 === 1);
  ?>
    <section class="py-5 <?= $index > 0 ? 'border-top border-secondary border-opacity-25' : '' ?>" id="<?= e($cat['slug']) ?>">
      <div class="row align-items-center g-5 <?= $is_even ? 'flex-lg-row-reverse' : '' ?>">
        
        <!-- Text details -->
        <div class="col-lg-5">
          <div class="feature-icon-box mb-3" style="width: 50px; height: 50px;">
            <i class="bi <?= e($cat['icon']) ?>"></i>
          </div>
          <span class="section-subtitle">Category <?= $index + 1 ?></span>
          <h2 class="display-6 fw-bold mt-2 text-gold"><?= e($cat['name']) ?></h2>
          <p class="text-muted-iron mt-3"><?= e($cat['description']) ?></p>
          
          <div class="p-3 rounded mb-4" style="background: var(--bg-card); border: 1px solid var(--border-color);">
            <h6 class="text-light mb-2"><i class="bi bi-star-fill text-gold me-2"></i> Setup &amp; Hospitality Highlights:</h6>
            <ul class="text-muted-iron small mb-0 ps-3">
              <li class="mb-1"><strong class="text-cyan">4K + QLED Screens:</strong> Ultra-crisp visuals with tuned low input response rates.</li>
              <li class="mb-1"><strong class="text-gold">Cozy &amp; Aesthetic Ambience:</strong> Ambient RGB glow, climate control, and ergonomic comfort.</li>
              <li class="mb-1"><strong class="text-danger">Beverage Cans &amp; Packed Food:</strong> Cold drinks, energy cans, and packaged munchies at the counter.</li>
              <li class="mb-1"><strong class="text-green">Outside Food Allowed:</strong> Bring your own outside food, snacks, and takeout to fuel your gaming squad.</li>
              <li>Sanitized headsets and controllers prior to every customer session.</li>
            </ul>
          </div>

          <a href="<?= BASE_URL ?>book.php" class="btn btn-iron btn-iron-primary btn-iron-sm">
            <i class="bi bi-controller me-1"></i> Book In This Category
          </a>
        </div>

        <!-- Available Stations Grid -->
        <div class="col-lg-7">
          <h5 class="text-muted-iron text-uppercase small mb-3 letter-spacing-1">
            <i class="bi bi-cpu-fill text-gold me-1"></i> Available Stations in <?= e($cat['name']) ?>:
          </h5>
          <div class="row g-3">
            <?php if (!empty($cat_stations)): ?>
              <?php foreach ($cat_stations as $st): ?>
                <div class="col-md-6">
                  <div class="iron-card h-100 p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                      <h5 class="h6 text-light mb-0"><?= e($st['name']) ?></h5>
                      <span class="station-status-pill <?= $st['status'] === 'active' ? 'status-active' : 'status-maintenance' ?>" style="font-size: 0.65rem;">
                        <?= $st['status'] === 'active' ? 'Active' : 'Maintenance' ?>
                      </span>
                    </div>
                    <div class="text-gold small fw-bold mb-2">
                      <?= format_currency($st['hourly_rate']) ?> / hour
                    </div>
                    <p class="text-muted-iron small mb-3" style="min-height: 40px; font-size: 0.8rem;">
                      <?= e($st['specs'] ?? 'High performance gaming setup.') ?>
                    </p>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-25">
                      <span class="text-secondary small"><?= e($st['code']) ?></span>
                      <a href="<?= BASE_URL ?>book.php?station_id=<?= $st['id'] ?>" class="btn btn-iron btn-iron-outline btn-iron-sm" style="padding: 0.25rem 0.65rem; font-size: 0.75rem;">
                        Select Slot <i class="bi bi-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="col-12">
                <div class="alert alert-dark small">Stations for this category are being updated by the arena manager.</div>
              </div>
            <?php endif; ?>
          </div>
        </div>

      </div>
    </section>
  <?php endforeach; ?>

</div>

<!-- Booking CTA Footer Banner -->
<section class="py-5 text-center" style="background: rgba(21, 25, 34, 0.5); border-top: 1px solid var(--border-color);">
  <div class="container py-3">
    <h3 class="display-6 fw-bold mb-3">Found Your Station?</h3>
    <p class="text-muted-iron mx-auto mb-4" style="max-width: 550px;">
      Lock in your battle station in advance. Real-time server slot confirmation and instant UPI verification.
    </p>
    <a href="<?= BASE_URL ?>book.php" class="btn btn-iron btn-iron-primary">
      <i class="bi bi-calendar2-check me-2"></i> Book Your Session Now
    </a>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

