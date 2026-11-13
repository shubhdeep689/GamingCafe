<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Gamer Sign-In Portal
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (is_customer_logged_in()) {
    $redirect = $_GET['redirect'] ?? BASE_URL . 'my-account.php';
    redirect($redirect);
}

$page_title = 'Player Login | ' . get_brand_short();
$error = '';
$redirect_target = $_GET['redirect'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = trim($_POST['password'] ?? '');
    $redirect   = trim($_POST['redirect'] ?? '');

    $result = customer_login_attempt($identifier, $password);

    if ($result['success']) {
        set_flash('success', 'Welcome back, ' . e($_SESSION['customer_name']) . '! Ready for the next match.');
        $dest = !empty($redirect) ? $redirect : BASE_URL . 'my-account.php';
        redirect($dest);
    } else {
        $error = $result['message'];
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="py-5 section-gta6-bg position-relative" data-bg-url="<?= BASE_URL ?>images/gta6_bg.jpeg" style="min-height: 85vh; display: flex; align-items: center; background-image: linear-gradient(180deg, rgba(9, 11, 16, 0.45) 0%, rgba(9, 11, 16, 0.18) 50%, rgba(9, 11, 16, 0.50) 100%), url('<?= BASE_URL ?>images/gta6_bg.jpeg');">
  <div class="container py-4">
    <div class="row justify-content-center">
      <div class="col-lg-5 col-md-8 col-sm-11">
        
        <div class="iron-card p-4 p-md-5 position-relative shadow-lg" style="background: rgba(13, 17, 24, 0.88) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(0, 229, 255, 0.35); box-shadow: 0 16px 45px rgba(0,0,0,0.8);">
          
          <div class="text-center mb-4">
            <a href="<?= BASE_URL ?>index.php" class="d-inline-block mb-3">
              <img src="<?= get_brand_logo() ?>" alt="<?= e(get_brand_name()) ?> Logo" style="height: 68px; filter: drop-shadow(0 0 15px rgba(214,168,75,0.4));">
            </a>
            <h2 class="h3 brand-font text-gold mb-1">PLAYER SIGN IN</h2>
            <p class="text-muted-iron small">Access your game history, digital passes, and instant slot reservations.</p>
          </div>

          <?= display_flash() ?>

          <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 px-3 small d-flex align-items-center gap-2 mb-4" role="alert">
              <i class="bi bi-exclamation-triangle-fill fs-5"></i>
              <div><?= e($error) ?></div>
            </div>
          <?php endif; ?>

          <!-- Semantic Sign-In Form with Autofill Attributes -->
          <form method="POST" action="<?= BASE_URL ?>login.php" novalidate autocomplete="on">
            <input type="hidden" name="redirect" value="<?= e($redirect_target) ?>">

            <!-- Identifier: Mobile Number or Email -->
            <div class="mb-3">
              <label for="identifier" class="form-label text-light small fw-bold">Mobile Number or Email</label>
              <div class="input-group">
                <span class="input-group-text bg-dark border-secondary text-gold"><i class="bi bi-person-fill"></i></span>
                <input type="text" 
                       class="form-control bg-dark text-light border-secondary" 
                       id="identifier" 
                       name="identifier" 
                       autocomplete="username" 
                       spellcheck="false" 
                       autocorrect="off" 
                       autocapitalize="off" 
                       placeholder="e.g. 9876543210 or player@gmail.com" 
                       value="<?= e($_POST['identifier'] ?? '') ?>" 
                       required 
                       autofocus>
              </div>
              <div class="form-text text-muted-iron" style="font-size: 0.72rem;">Enter the mobile number you book with or your email.</div>
            </div>

            <!-- Password -->
            <div class="mb-4">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="current-password" class="form-label text-light small fw-bold mb-0">Password</label>
                <a href="<?= BASE_URL ?>check-booking.php" class="text-cyan small text-decoration-none" style="font-size: 0.75rem;">Lookup by Ref ID?</a>
              </div>
              <div class="input-group">
                <span class="input-group-text bg-dark border-secondary text-gold"><i class="bi bi-lock-fill"></i></span>
                <input type="password" 
                       class="form-control bg-dark text-light border-secondary" 
                       id="current-password" 
                       name="password" 
                       autocomplete="current-password" 
                       placeholder="Enter your account password" 
                       required>
                <button class="btn btn-outline-secondary text-light border-secondary" type="button" id="btnTogglePassword" title="Show / Hide Password">
                  <i class="bi bi-eye" id="toggleIcon"></i>
                </button>
              </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-iron btn-iron-primary w-100 py-2 fs-6 fw-bold mb-3 shadow">
              <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Sanctuary
            </button>
          </form>

          <hr class="border-secondary border-opacity-25 my-4">

          <!-- New Gamer Registration Link -->
          <div class="text-center">
            <span class="text-muted-iron small">New to <?= e(get_brand_short()) ?>?</span>
            <a href="<?= BASE_URL ?>register.php<?= !empty($redirect_target) ? '?redirect=' . urlencode($redirect_target) : '' ?>" class="text-gold fw-bold small text-decoration-none ms-1">
              Create an Account <i class="bi bi-arrow-right"></i>
            </a>
          </div>

        </div>

        <div class="text-center mt-3">
          <a href="<?= BASE_URL ?>index.php" class="text-muted-iron small text-decoration-none">
            <i class="bi bi-house-door me-1"></i> Return to Main Arena
          </a>
        </div>

      </div>
    </div>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const toggleBtn = document.getElementById('btnTogglePassword');
  const passInput = document.getElementById('current-password');
  const icon = document.getElementById('toggleIcon');

  if (toggleBtn && passInput) {
    toggleBtn.addEventListener('click', function () {
      const isPass = passInput.getAttribute('type') === 'password';
      passInput.setAttribute('type', isPass ? 'text' : 'password');
      if (icon) {
        icon.className = isPass ? 'bi bi-eye-slash' : 'bi bi-eye';
      }
    });
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

