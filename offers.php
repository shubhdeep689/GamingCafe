<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Promotional Offers & Discount Passes - Dynamically White-Labeled
 */

require_once __DIR__ . '/config/config.php';

$brand_name = get_brand_name();
$brand_city = get_brand_city();
$page_title = 'Special Offers & Gaming Deals | ' . $brand_name;
$page_desc = 'Discover active promotional codes, student discounts, and squad battle passes at ' . $brand_name . ' in ' . $brand_city . '.';

require_once __DIR__ . '/includes/header.php';

$db = get_db();

$offers_stmt = $db->query("
    SELECT * FROM offers 
    WHERE is_active = 1 
      AND valid_from <= CURDATE() 
      AND valid_until >= CURDATE() 
    ORDER BY id DESC
");
$active_offers = $offers_stmt->fetchAll();
?>

<!-- Page Banner -->
<section class="py-5 position-relative overflow-hidden" style="background: radial-gradient(circle at 50% 20%, rgba(34, 43, 61, 0.6) 0%, var(--bg-primary) 80%); border-bottom: 1px solid var(--border-color);">
  <div class="container py-4 text-center position-relative z-2">
    <span class="section-subtitle">Arena Rewards</span>
    <h1 class="display-5 fw-bold mt-2">Special Offers & Deals</h1>
    <p class="text-muted-iron mx-auto" style="max-width: 650px;">
      Level up your gaming experience with our active promotional codes, weekend battle passes, and team discounts in <?= e($brand_city) ?>.
    </p>
  </div>
</section>

<!-- Offers List -->
<section class="py-5">
  <div class="container py-4">
    <div class="row g-4 justify-content-center">
      <?php if (!empty($active_offers)): ?>
        <?php foreach ($active_offers as $offer): ?>
          <div class="col-lg-6">
            <div class="iron-card p-4 p-md-5 h-100 position-relative overflow-hidden" style="border: 1px solid rgba(214,168,75,0.35);">
              
              <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="badge bg-danger text-uppercase fw-bold letter-spacing-1">
                  <?= $offer['discount_type'] === 'percentage' ? ((float)$offer['discount_value'] . '% OFF') : ('₹' . number_format((float)$offer['discount_value'], 0) . ' OFF') ?>
                </span>
                <span class="text-muted-iron small">
                  Expires: <strong class="text-light"><?= date('d M Y', strtotime($offer['valid_until'])) ?></strong>
                </span>
              </div>

              <h3 class="h4 text-gold mb-2"><?= e($offer['title']) ?></h3>
              <p class="text-muted-iron small mb-4"><?= e($offer['description']) ?></p>

              <div class="p-3 rounded mb-4 d-flex justify-content-between align-items-center" style="background: rgba(0,0,0,0.4); border: 1px dashed var(--gold);">
                <div>
                  <span class="text-muted-iron small d-block" style="font-size: 0.72rem;">COUPON CODE:</span>
                  <span class="text-gold fw-bold font-monospace fs-5"><?= e($offer['code']) ?></span>
                </div>
                <button type="button" class="btn btn-iron btn-iron-outline btn-iron-sm btn-copy-ref" data-copy="<?= e($offer['code']) ?>">
                  <i class="bi bi-clipboard me-1"></i> Copy
                </button>
              </div>

              <div class="d-flex justify-content-between align-items-center pt-3 border-top border-secondary border-opacity-25">
                <span class="text-muted-iron small">
                  <i class="bi bi-clock me-1 text-gold"></i> Min session: <?= $offer['min_booking_hours'] ?> hr<?= $offer['min_booking_hours'] > 1 ? 's' : '' ?>
                </span>
                <a href="<?= BASE_URL ?>book.php" class="btn btn-iron btn-iron-primary btn-iron-sm">
                  Book with Code <i class="bi bi-arrow-right ms-1"></i>
                </a>
              </div>

            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-lg-8">
          <div class="alert alert-dark text-center p-5">
            <i class="bi bi-gift text-gold display-5 mb-3 d-block"></i>
            <h4 class="text-light">No Active Public Offers Today</h4>
            <p class="text-muted-iron small mb-0">Check back soon or follow our social channels for tournament passes and festival gaming discounts!</p>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

