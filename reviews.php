<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Customer Reviews & Community Feedback - Dynamically White-Labeled
 */

require_once __DIR__ . '/config/config.php';

$brand_name  = get_brand_name();
$brand_short = get_brand_short();
$brand_city  = get_brand_city();

$page_title = 'Player Reviews & Ratings | ' . $brand_name;
$page_desc = 'Read honest reviews from gamers in ' . $brand_city . ', or submit your own review of ' . $brand_name . '.';

require_once __DIR__ . '/includes/header.php';

$db = get_db();
$errors = [];

// Handle Review Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!verify_csrf()) {
        $errors[] = 'Security token invalid or expired. Please refresh and retry.';
    } else {
        $name = to_title_case(trim($_POST['customer_name'] ?? ''));
        $rating = (int)($_POST['rating'] ?? 5);
        $game_played = trim($_POST['game_played'] ?? '');
        $review_text = trim($_POST['review_text'] ?? '');

        if (empty($name) || mb_strlen($name) < 2) {
            $errors[] = 'Please enter your name (minimum 2 characters).';
        }
        if ($rating < 1 || $rating > 5) {
            $rating = 5;
        }
        if (empty($review_text) || mb_strlen($review_text) < 10) {
            $errors[] = 'Please write a helpful review (minimum 10 characters).';
        }

        if (empty($errors)) {
            try {
                $stmt = $db->prepare("
                    INSERT INTO reviews (customer_name, rating, review_text, game_played, is_approved, is_featured) 
                    VALUES (:name, :rating, :text, :game, 0, 0)
                ");
                $stmt->execute([
                    ':name'   => $name,
                    ':rating' => $rating,
                    ':text'   => $review_text,
                    ':game'   => $game_played ?: null
                ]);

                set_flash('success', 'Thank you for your battle review! It has been submitted and will appear on the wall following owner review.');
                redirect(BASE_URL . 'reviews.php');
            } catch (Exception $e) {
                error_log('Review Submit Error: ' . $e->getMessage());
                $errors[] = 'Failed to submit review. Please try again.';
            }
        }
    }
}

// Fetch Approved Reviews
$reviews_stmt = $db->query("SELECT * FROM reviews WHERE is_approved = 1 ORDER BY is_featured DESC, id DESC");
$approved_reviews = $reviews_stmt->fetchAll();

// Calculate Stats
$total_revs = count($approved_reviews);
$avg_rating = 5.0;
if ($total_revs > 0) {
    $sum = array_sum(array_column($approved_reviews, 'rating'));
    $avg_rating = round($sum / $total_revs, 1);
}
?>

<!-- Page Banner -->
<section class="py-5 section-cafe-bg position-relative overflow-hidden" data-bg-url="<?= BASE_URL ?>images/cafe_bg.jpeg" style="background-image: linear-gradient(180deg, rgba(9, 11, 16, 0.45) 0%, rgba(9, 11, 16, 0.20) 50%, rgba(9, 11, 16, 0.50) 100%), url('<?= BASE_URL ?>images/cafe_bg.jpeg'); border-bottom: 1px solid rgba(214, 168, 75, 0.35);">
  <div class="container py-4 text-center position-relative z-2">
    <span class="section-subtitle">Community Voice</span>
    <h1 class="display-5 fw-bold mt-2">Player Reviews & Ratings</h1>
    <p class="text-muted-iron mx-auto" style="max-width: 650px;">
      Authentic feedback from competitive players, casual groups, and regular warriors at <?= e($brand_short) ?> in <?= e($brand_city) ?>.
    </p>
  </div>
</section>

<section class="py-5 section-gta6-bg position-relative" data-bg-url="<?= BASE_URL ?>images/gta6_bg.jpeg" style="background-image: linear-gradient(180deg, rgba(9, 11, 16, 0.40) 0%, rgba(9, 11, 16, 0.16) 50%, rgba(9, 11, 16, 0.48) 100%), url('<?= BASE_URL ?>images/gta6_bg.jpeg');">
  <div class="container py-2">
    
    <?= display_flash() ?>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger shadow-sm mb-4">
        <h5 class="alert-heading fs-6 fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Review Submission Notice</h5>
        <ul class="mb-0 small ps-3">
          <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <!-- Rating Summary Bar -->
    <div class="iron-card p-4 mb-5">
      <div class="row align-items-center g-4 text-center text-md-start">
        <div class="col-md-4 border-end border-secondary border-opacity-25 text-center">
          <div class="display-4 fw-bold text-gold brand-font mb-0"><?= number_format($avg_rating, 1) ?></div>
          <div class="rating-stars my-2">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <i class="bi <?= $i <= round($avg_rating) ? 'bi-star-fill' : 'bi-star' ?>"></i>
            <?php endfor; ?>
          </div>
          <div class="text-muted-iron small">Based on <?= $total_revs ?> verified player reviews</div>
        </div>

        <div class="col-md-5">
          <h4 class="h5 text-light mb-2">Played at <?= e($brand_short) ?> recently?</h4>
          <p class="text-muted-iron small mb-0">
            Share your feedback regarding our PC rigs, console lounges, ping latency, ambience, and staff support.
          </p>
        </div>

        <div class="col-md-3 text-center text-md-end">
          <a href="#submitReviewForm" class="btn btn-iron btn-iron-primary btn-iron-sm">
            <i class="bi bi-pencil-square me-1"></i> Write a Review
          </a>
        </div>
      </div>
    </div>

    <!-- Reviews Grid -->
    <div class="row g-4 mb-5">
      <?php if (!empty($approved_reviews)): ?>
        <?php foreach ($approved_reviews as $rev): ?>
          <div class="col-lg-4 col-md-6">
            <div class="iron-card h-100 d-flex flex-column">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="rating-stars">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="bi <?= $i <= $rev['rating'] ? 'bi-star-fill' : 'bi-star' ?>"></i>
                  <?php endfor; ?>
                </div>
                <span class="badge bg-secondary bg-opacity-25 text-gold small">Verified Gamer</span>
              </div>
              <p class="text-muted-iron small flex-grow-1">
                "<?= e($rev['review_text']) ?>"
              </p>
              <div class="pt-3 border-top border-secondary border-opacity-25 mt-2">
                <strong class="text-light d-block"><?= e($rev['customer_name']) ?></strong>
                <?php if (!empty($rev['game_played'])): ?>
                  <span class="text-cyan small"><i class="bi bi-controller me-1"></i> <?= e($rev['game_played']) ?></span>
                <?php endif; ?>
                <span class="text-secondary small d-block" style="font-size: 0.72rem;"><?= date('d M Y', strtotime($rev['created_at'])) ?></span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Submit Review Form -->
    <div class="row justify-content-center" id="submitReviewForm">
      <div class="col-lg-8">
        <div class="iron-card p-4 p-md-5">
          <h3 class="h4 text-gold mb-2 brand-font">Submit Player Review</h3>
          <p class="text-muted-iron small mb-4">
            Your review helps fellow gamers in <?= e($brand_city) ?> choose the best stations and helps us continuously enhance the sanctuary.
          </p>

          <form method="POST" action="<?= BASE_URL ?>reviews.php">
            <?= csrf_field() ?>
            <input type="hidden" name="submit_review" value="1">

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label for="revName" class="form-label">Your Name <span class="text-danger">*</span></label>
                <input type="text" name="customer_name" id="revName" class="form-control" placeholder="e.g. Navdeep Brar" required>
              </div>

              <div class="col-md-6">
                <label for="revRating" class="form-label">Rating <span class="text-danger">*</span></label>
                <select name="rating" id="revRating" class="form-select" required>
                  <option value="5">⭐⭐⭐⭐⭐ 5 - Legendary Experience</option>
                  <option value="4">⭐⭐⭐⭐ 4 - Great Gaming Session</option>
                  <option value="3">⭐⭐⭐ 3 - Good / Average</option>
                  <option value="2">⭐⭐ 2 - Needs Improvement</option>
                  <option value="1">⭐ 1 - Unsatisfactory</option>
                </select>
              </div>

              <div class="col-12">
                <label for="revGame" class="form-label">Title / Game Played (Optional)</label>
                <input type="text" name="game_played" id="revGame" class="form-control" placeholder="e.g. Valorant, EA Sports FC 24, Tekken 8, GTA V">
              </div>

              <div class="col-12">
                <label for="revText" class="form-label">Your Honest Review <span class="text-danger">*</span></label>
                <textarea name="review_text" id="revText" rows="4" class="form-control" placeholder="Describe the rigs, display smoothness, ping, ambience, and overall experience..." required></textarea>
              </div>
            </div>

            <div class="p-3 rounded mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color);">
              <span class="text-muted-iron small">
                <i class="bi bi-shield-lock text-gold me-1"></i> Anti-spam policy: All player submissions undergo moderation before publishing.
              </span>
            </div>

            <button type="submit" class="btn btn-iron btn-iron-primary py-3 px-5">
              <i class="bi bi-send-fill me-1"></i> Post Player Review
            </button>
          </form>
        </div>
      </div>
    </div>

  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

