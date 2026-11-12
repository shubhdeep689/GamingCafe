<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Pricing & Passes Page - Dynamically White-Labeled
 */

require_once __DIR__ . '/config/config.php';

$brand_name  = get_brand_name();
$brand_short = get_brand_short();
$brand_city  = get_brand_city();

$page_title = 'Transparent Pricing & Battle Passes | ' . $brand_name;
$page_desc = 'Explore straightforward hourly rates and discounted battle passes at ' . $brand_name . ' in ' . $brand_city . '. Zero hidden charges.';

require_once __DIR__ . '/includes/header.php';

$db = get_db();

$plans = $db->query("SELECT * FROM pricing_plans WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll();
$stations = $db->query("
    SELECT s.*, c.name as category_name 
    FROM stations s 
    JOIN station_categories c ON s.category_id = c.id 
    WHERE s.status = 'active' 
    ORDER BY s.hourly_rate ASC
")->fetchAll();
?>

<!-- Page Banner -->
<section class="py-5 position-relative overflow-hidden" style="background: radial-gradient(circle at 50% 20%, rgba(34, 43, 61, 0.6) 0%, var(--bg-primary) 80%); border-bottom: 1px solid var(--border-color);">
  <div class="container py-4 text-center position-relative z-2">
    <span class="section-subtitle">Fair & Transparent Rates</span>
    <h1 class="display-5 fw-bold mt-2">Arena Pricing & Passes</h1>
    <p class="text-muted-iron mx-auto" style="max-width: 650px;">
      Straightforward pricing with zero hidden fees. Pay by the single hour or choose multi-hour battle passes for maximum savings.
    </p>
  </div>
</section>

<!-- Pricing Plans Grid -->
<section class="py-5">
  <div class="container py-4">
    <div class="row g-4 justify-content-center">
      <?php if (!empty($plans)): ?>
        <?php foreach ($plans as $plan): 
          $features = json_decode($plan['features_json'] ?? '[]', true) ?: [];
        ?>
          <div class="col-lg-3 col-md-6">
            <div class="iron-card pricing-card <?= $plan['is_popular'] ? 'featured' : '' ?>">
              <?php if (!empty($plan['badge'])): ?>
                <div class="pricing-badge-ribbon"><?= e($plan['badge']) ?></div>
              <?php endif; ?>

              <h4 class="h5 text-gold mb-1"><?= e($plan['title']) ?></h4>
              <p class="text-muted-iron small mb-3"><?= $plan['duration_hours'] ?> Hour<?= $plan['duration_hours'] > 1 ? 's' : '' ?> Continuous Play</p>

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
                Book This Pass
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Station-wise Rate Table -->
<section class="py-5" style="background: rgba(21, 25, 34, 0.4); border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);">
  <div class="container py-4">
    <div class="text-center mb-5">
      <span class="section-subtitle">Station Breakdown</span>
      <h2 class="display-6 fw-bold mt-2">Hourly Station Rates</h2>
      <p class="text-muted-iron mx-auto" style="max-width: 600px;">
        Standard base rates per hour for each station category.
      </p>
    </div>

    <div class="table-responsive">
      <table class="table table-dark table-hover border border-secondary border-opacity-25 rounded-3 align-middle">
        <thead>
          <tr class="text-gold" style="background: rgba(214,168,75,0.08);">
            <th class="py-3 px-4">Station Name</th>
            <th class="py-3">Category</th>
            <th class="py-3">Configuration & Peripherals</th>
            <th class="py-3 text-end px-4">Base Hourly Rate</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($stations as $st): ?>
            <tr>
              <td class="py-3 px-4 fw-bold text-light"><?= e($st['name']) ?></td>
              <td class="py-3 text-cyan small"><?= e($st['category_name']) ?></td>
              <td class="py-3 text-muted-iron small"><?= e($st['specs']) ?></td>
              <td class="py-3 text-end px-4 fw-bold text-gold"><?= format_currency($st['hourly_rate']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<!-- Pricing FAQs -->
<section class="py-5">
  <div class="container py-4">
    <div class="text-center mb-5">
      <span class="section-subtitle">Got Questions?</span>
      <h2 class="display-6 fw-bold mt-2">Frequently Asked Questions</h2>
    </div>

    <div class="row g-4 justify-content-center">
      <div class="col-lg-8">
        <div class="accordion accordion-flush" id="pricingFaq">
          
          <div class="accordion-item bg-transparent border-bottom border-secondary border-opacity-25 mb-3">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed bg-transparent text-light fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                Are all games and titles included in the session fee?
              </button>
            </h2>
            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#pricingFaq">
              <div class="accordion-body text-muted-iron small">
                Yes! All installed titles on our PC arena and consoles are accessible during your booked session. You can also sign into your personal Steam, Riot, Battle.net, or Epic Games accounts safely (we advise logging out when your session concludes).
              </div>
            </div>
          </div>

          <div class="accordion-item bg-transparent border-bottom border-secondary border-opacity-25 mb-3">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed bg-transparent text-light fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                Can I bring my own mouse, keyboard, or headset?
              </button>
            </h2>
            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#pricingFaq">
              <div class="accordion-body text-muted-iron small">
                Absolutely. Competitive players are encouraged to plug in their own mouse, keyboard, or headset for optimal muscle memory and comfort. Our stations have easily accessible front I/O ports.
              </div>
            </div>
          </div>

          <div class="accordion-item bg-transparent border-bottom border-secondary border-opacity-25 mb-3">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed bg-transparent text-light fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                How does UPI payment verification work?
              </button>
            </h2>
            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#pricingFaq">
              <div class="accordion-body text-muted-iron small">
                When you submit a booking, you will see the official <?= e($brand_short) ?> UPI QR scanner along with your total amount. Simply scan with GPay, PhonePe, Paytm, or BHIM, and submit the 12-digit UTR/Transaction Reference number. Our arena admin verifies the transaction on our terminal and confirms your booking promptly.
              </div>
            </div>
          </div>

          <div class="accordion-item bg-transparent border-bottom border-secondary border-opacity-25 mb-3">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed bg-transparent text-light fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                What if I want to extend my gaming session?
              </button>
            </h2>
            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#pricingFaq">
              <div class="accordion-body text-muted-iron small">
                If no subsequent player has reserved your station, you can speak directly with the arena manager at the counter to top up your time at standard rates.
              </div>
            </div>
          </div>

          <div class="accordion-item bg-transparent border-bottom border-secondary border-opacity-25 mb-3">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed bg-transparent text-light fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                Can I bring outside food, and what refreshments do you offer?
              </button>
            </h2>
            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#pricingFaq">
              <div class="accordion-body text-muted-iron small">
                <strong class="text-green"><i class="bi bi-check-circle-fill me-1"></i> Yes, outside food is 100% allowed!</strong> Feel free to order takeout, pizza, or bring your own snacks with your squad. Additionally, we stock ice-cold beverage cans (energy drinks, sodas) and packed food &amp; snacks right at our reception counter. Enjoy our cozy aesthetic ambience and 4K + QLED screens while you game!
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

