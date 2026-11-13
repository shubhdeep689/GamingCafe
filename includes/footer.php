<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Public Layout Footer - Dynamically White-Labeled
 */

$footer_phone = get_brand_phone();
$footer_email = get_brand_email();
$footer_address = get_brand_address();
$footer_insta = get_setting('instagram_url', 'https://instagram.com/');
$footer_discord = get_setting('discord_url', 'https://discord.gg/');
$footer_youtube = get_setting('youtube_url', 'https://youtube.com/');
$footer_tagline = get_brand_tagline();
$footer_city = get_brand_city();
$footer_state = get_brand_state();
$footer_brand = get_brand_name();
?>
  </main> <!-- End site-main-content -->

  <!-- Footer Section -->
  <footer class="iron-footer">
    <div class="container">
      <div class="row g-4 mb-4">
        
        <!-- Col 1: Brand & Identity -->
        <div class="col-lg-4 col-md-6">
          <a href="<?= BASE_URL ?>index.php" class="d-inline-block text-decoration-none">
            <!-- Authentic Transparent Business Logo -->
            <img src="<?= get_brand_logo() ?>" alt="<?= e($footer_brand) ?>" class="footer-logo">
          </a>
          <p class="text-gold fw-bold mb-2"><?= e($footer_brand) ?></p>
          <p class="text-muted-iron small mb-3">
            <em><?= e($footer_tagline) ?></em><br>
            The premier esports and competitive gaming lounge in <?= e($footer_city) ?>. Dedicated high-performance rigs, console bays, and a real gaming sanctuary.
          </p>
          <div class="social-links">
            <a href="<?= e($footer_insta) ?>" target="_blank" rel="noopener noreferrer" class="social-btn" aria-label="Instagram">
              <i class="bi bi-instagram"></i>
            </a>
            <a href="<?= e($footer_discord) ?>" target="_blank" rel="noopener noreferrer" class="social-btn" aria-label="Discord">
              <i class="bi bi-discord"></i>
            </a>
            <a href="<?= e($footer_youtube) ?>" target="_blank" rel="noopener noreferrer" class="social-btn" aria-label="YouTube">
              <i class="bi bi-youtube"></i>
            </a>
          </div>
        </div>

        <!-- Col 2: Navigation Links -->
        <div class="col-lg-2 col-md-6 col-6">
          <h4 class="footer-heading">Navigation</h4>
          <ul class="footer-links">
            <li><a href="<?= BASE_URL ?>index.php">Home Arena</a></li>
            <li><a href="<?= BASE_URL ?>about.php">About Sanctuary</a></li>
            <li><a href="<?= BASE_URL ?>experiences.php">Gaming Rigs</a></li>
            <li><a href="<?= BASE_URL ?>pricing.php">Pricing Plans</a></li>
            <li><a href="<?= BASE_URL ?>offers.php">Special Offers</a></li>
            <li><a href="<?= BASE_URL ?>gallery.php">Arena Gallery</a></li>
          </ul>
        </div>

        <!-- Col 3: Customer Service -->
        <div class="col-lg-3 col-md-6 col-6">
          <h4 class="footer-heading">Services</h4>
          <ul class="footer-links">
            <li><a href="<?= BASE_URL ?>book.php">Book Your Slot</a></li>
            <li><a href="<?= BASE_URL ?>check-booking.php">Check Booking Status</a></li>
            <li><a href="<?= BASE_URL ?>reviews.php">Player Reviews</a></li>
            <li><a href="<?= BASE_URL ?>contact.php">Find Location</a></li>
            <li><a href="<?= BASE_URL ?>admin/login.php"><i class="bi bi-shield-lock me-1 text-gold"></i> Owner Login</a></li>
          </ul>
        </div>

        <!-- Col 4: Location & Operating Hours -->
        <div class="col-lg-3 col-md-6">
          <h4 class="footer-heading">Location & Hours</h4>
          <p class="text-muted-iron small mb-2">
            <i class="bi bi-geo-alt-fill text-red me-2"></i>
            <?= e($footer_address) ?>
          </p>
          <p class="text-muted-iron small mb-2">
            <i class="bi bi-envelope-fill text-gold me-2"></i>
            <a href="mailto:<?= e($footer_email) ?>" class="text-decoration-none text-muted-iron"><?= e($footer_email) ?></a>
          </p>
          <?php if (!empty($footer_phone)): ?>
          <p class="text-muted-iron small mb-2">
            <i class="bi bi-telephone-fill text-cyan me-2"></i>
            <a href="tel:<?= e($footer_phone) ?>" class="text-decoration-none text-muted-iron"><?= e($footer_phone) ?></a>
          </p>
          <?php endif; ?>
          <div class="p-2 mt-2 rounded" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color);">
            <div class="d-flex justify-content-between align-items-center small">
              <span class="text-gold fw-bold"><i class="bi bi-clock-history me-1"></i> Arena Hours</span>
              <span class="text-green fw-bold">Open Daily</span>
            </div>
            <div class="text-muted-iron small mt-1">
              10:00 AM – 11:00 PM (IST)
            </div>
          </div>
        </div>

      </div>

      <!-- Copyright & SEO Bar -->
      <div class="pt-3 border-top border-secondary border-opacity-25 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
        <div class="text-muted-iron small">
          &copy; <?= date('Y') ?> <strong><?= e($footer_brand) ?></strong>. All rights reserved. <?= e($footer_city) ?>, <?= e($footer_state) ?>.
        </div>
        <div class="text-muted-iron small text-center text-md-end">
          <span>Website Made by <strong class="text-gold">shubhdeep689</strong></span>
        </div>
      </div>
    </div>
  </footer>

  <!-- Bootstrap 5 Bundle JS with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  
  <!-- Ironclad Client JS -->
  <script src="<?= ASSETS_URL ?>js/main.js?v=<?= APP_VERSION ?>"></script>
</body>
</html>

