<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Owner & Admin Authentication Portal
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Redirect if already logged in
if (is_admin_logged_in()) {
    redirect(BASE_URL . 'admin/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Security session expired. Please refresh the page and retry.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        $result = admin_login_attempt($username, $password);
        if ($result['success']) {
            set_flash('success', 'Welcome back to the Ironclad Command Center!');
            redirect(BASE_URL . 'admin/dashboard.php');
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | IRONCLAD GAMING SANCTUARY</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=Segoe+UI:wght@400;600;700&display=swap" rel="stylesheet">
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= ASSETS_URL ?>css/style.css?v=<?= APP_VERSION ?>">
  <link rel="icon" type="image/png" href="<?= BASE_URL ?>images/NOBG_IRONCLADLOGO.png">
</head>
<body style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #090B10; padding: 2rem 1rem;">

  <!-- Ambient Light Backdrops -->
  <div class="ambient-glow-wrapper" aria-hidden="true">
    <div class="glow-orb glow-orb-purple" style="top: -100px; left: -100px;"></div>
    <div class="glow-orb glow-orb-cyan" style="bottom: -100px; right: -100px;"></div>
  </div>

  <div class="container position-relative z-2" style="max-width: 440px;">
    
    <?= display_flash() ?>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger shadow-sm mb-4 d-flex align-items-center" role="alert">
        <i class="bi bi-shield-exclamation fs-4 me-2"></i>
        <div class="small flex-grow-1"><?= e($error) ?></div>
      </div>
    <?php endif; ?>

    <div class="iron-card p-4 p-md-5 text-center" style="border: 1px solid rgba(214,168,75,0.3); background: #151922;">
      <!-- Authentic Logo (Transparent) -->
      <a href="<?= BASE_URL ?>">
        <img src="<?= BASE_URL ?>images/NOBG_IRONCLADLOGO.png" alt="Ironclad Sanctuary Logo" class="img-fluid mb-3" style="max-height: 90px; filter: drop-shadow(0 0 15px rgba(214,168,75,0.5));">
      </a>
      
      <h3 class="h4 text-gold brand-font mb-1">COMMAND CENTER</h3>
      <p class="text-muted-iron small mb-4">Owner & Management Access Portal</p>

      <form method="POST" action="<?= BASE_URL ?>admin/login.php" class="text-start">
        <?= csrf_field() ?>

        <div class="mb-3">
          <label for="adminUser" class="form-label">Admin Username</label>
          <div class="input-group">
            <span class="input-group-text bg-dark border-secondary border-opacity-25 text-muted-iron">
              <i class="bi bi-person-fill"></i>
            </span>
            <input type="text" name="username" id="adminUser" class="form-control" placeholder="Enter username" required autofocus>
          </div>
        </div>

        <div class="mb-4">
          <div class="d-flex justify-content-between align-items-center">
            <label for="adminPass" class="form-label">Password</label>
          </div>
          <div class="input-group">
            <span class="input-group-text bg-dark border-secondary border-opacity-25 text-muted-iron">
              <i class="bi bi-key-fill"></i>
            </span>
            <input type="password" name="password" id="adminPass" class="form-control" placeholder="Enter password" required>
          </div>
        </div>

        <button type="submit" class="btn btn-iron btn-iron-primary w-100 py-3">
          <i class="bi bi-box-arrow-in-right fs-5"></i> Authenticate & Enter
        </button>
      </form>

      <div class="mt-4 pt-3 border-top border-secondary border-opacity-25 text-center">
        <a href="<?= BASE_URL ?>" class="text-muted-iron small text-decoration-none">
          <i class="bi bi-arrow-left me-1"></i> Return to Public Arena
        </a>
      </div>
    </div>

  </div>

</body>
</html>

