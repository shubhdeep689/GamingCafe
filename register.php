<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Gamer Registration Portal
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (is_customer_logged_in()) {
    redirect(BASE_URL . 'my-account.php');
}

$page_title = 'Player Registration | ' . get_brand_short();
$error = '';
$redirect_target = $_GET['redirect'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = trim($_POST['password'] ?? '');
    $redirect  = trim($_POST['redirect'] ?? '');

    $result = customer_register($full_name, $phone, $email, $password);

    if ($result['success']) {
        set_flash('success', 'Account created successfully! Welcome to the Ironclad community, ' . e($full_name) . '.');
        $dest = !empty($redirect) ? $redirect : BASE_URL . 'my-account.php';
        redirect($dest);
    } else {
        $error = $result['message'];
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="py-5" style="min-height: 80vh; display: flex; align-items: center;">
  <div class="container py-4">
    <div class="row justify-content-center">
      <div class="col-lg-6 col-md-8 col-sm-11">
        
        <div class="iron-card p-4 p-md-5 position-relative shadow-lg" style="background: radial-gradient(circle at top, #1A212E 0%, #11151E 100%); border: 1px solid var(--border-color);">
          
          <div class="text-center mb-4">
            <a href="<?= BASE_URL ?>index.php" class="d-inline-block mb-3">
              <img src="<?= get_brand_logo() ?>" alt="<?= e(get_brand_name()) ?> Logo" style="height: 68px; filter: drop-shadow(0 0 15px rgba(214,168,75,0.4));">
            </a>
            <h2 class="h3 brand-font text-gold mb-1">JOIN THE SANCTUARY</h2>
            <p class="text-muted-iron small">Create your gamer profile to unlock instant reservations, digital passes & exclusive discounts.</p>
          </div>

          <?= display_flash() ?>

          <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 px-3 small d-flex align-items-center gap-2 mb-4" role="alert">
              <i class="bi bi-exclamation-triangle-fill fs-5"></i>
              <div><?= e($error) ?></div>
            </div>
          <?php endif; ?>

          <!-- Semantic Sign-Up Form with Modern Autofill Attributes -->
          <form method="POST" action="<?= BASE_URL ?>register.php" novalidate autocomplete="on">
            <input type="hidden" name="redirect" value="<?= e($redirect_target) ?>">

            <!-- Full Name -->
            <div class="mb-3">
              <label for="full-name" class="form-label text-light small fw-bold">Full Name</label>
              <div class="input-group">
                <span class="input-group-text bg-dark border-secondary text-gold"><i class="bi bi-person-badge"></i></span>
                <input type="text" 
                       class="form-control bg-dark text-light border-secondary" 
                       id="full-name" 
                       name="full_name" 
                       autocomplete="name" 
                       placeholder="e.g. Vikramjit Singh" 
                       value="<?= e($_POST['full_name'] ?? '') ?>" 
                       required 
                       autofocus>
              </div>
            </div>

            <!-- Mobile Phone -->
            <div class="mb-3">
              <label for="phone" class="form-label text-light small fw-bold">10-Digit Mobile Number</label>
              <div class="input-group">
                <span class="input-group-text bg-dark border-secondary text-gold">+91</span>
                <input type="tel" 
                       class="form-control bg-dark text-light border-secondary" 
                       id="phone" 
                       name="phone" 
                       autocomplete="tel" 
                       inputmode="tel" 
                       pattern="[6-9][0-9]{9}" 
                       placeholder="e.g. 9876543210" 
                       value="<?= e($_POST['phone'] ?? '') ?>" 
                       required>
              </div>
              <div class="form-text text-muted-iron" style="font-size: 0.72rem;">Used for your digital booking pass & counter check-in verification.</div>
            </div>

            <!-- Email Address -->
            <div class="mb-3">
              <label for="email" class="form-label text-light small fw-bold">Email Address</label>
              <div class="input-group">
                <span class="input-group-text bg-dark border-secondary text-gold"><i class="bi bi-envelope"></i></span>
                <input type="email" 
                       class="form-control bg-dark text-light border-secondary" 
                       id="email" 
                       name="email" 
                       autocomplete="username" 
                       spellcheck="false" 
                       placeholder="e.g. player@gmail.com" 
                       value="<?= e($_POST['email'] ?? '') ?>" 
                       required>
              </div>
              <div class="form-text text-muted-iron" style="font-size: 0.72rem;">Used for payment receipts & reservation notifications.</div>
            </div>

            <!-- Password -->
            <div class="mb-4">
              <label for="new-password" class="form-label text-light small fw-bold">Create Account Password</label>
              <div class="input-group">
                <span class="input-group-text bg-dark border-secondary text-gold"><i class="bi bi-lock-fill"></i></span>
                <input type="password" 
                       class="form-control bg-dark text-light border-secondary" 
                       id="new-password" 
                       name="password" 
                       autocomplete="new-password" 
                       placeholder="At least 6 characters" 
                       required>
                <button class="btn btn-outline-secondary text-light border-secondary" type="button" id="btnToggleRegPassword" title="Show / Hide Password">
                  <i class="bi bi-eye" id="toggleRegIcon"></i>
                </button>
              </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-iron btn-iron-primary w-100 py-2 fs-6 fw-bold mb-3 shadow">
              <i class="bi bi-shield-plus me-1"></i> Create Player Account
            </button>
          </form>

          <hr class="border-secondary border-opacity-25 my-4">

          <div class="text-center">
            <span class="text-muted-iron small">Already have an account?</span>
            <a href="<?= BASE_URL ?>login.php<?= !empty($redirect_target) ? '?redirect=' . urlencode($redirect_target) : '' ?>" class="text-gold fw-bold small text-decoration-none ms-1">
              Sign In Here <i class="bi bi-arrow-right"></i>
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
  const toggleBtn = document.getElementById('btnToggleRegPassword');
  const passInput = document.getElementById('new-password');
  const icon = document.getElementById('toggleRegIcon');

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

