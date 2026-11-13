<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Admin Panel Layout Header
 */

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

// Authorization Gate
require_admin();

$admin_user = current_admin();
$current_admin_page = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');
$db = get_db();

// Counts for badges
$pending_pay_count = 0;
$pending_rev_count = 0;
$pending_wa_badge_count = 0;
$pending_inquiry_count = 0;
try {
    $pending_pay_count = (int)$db->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn();
    $pending_rev_count = (int)$db->query("SELECT COUNT(*) FROM reviews WHERE is_approved = 0")->fetchColumn();
    $pending_wa_badge_count = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE whatsapp_status = 'pending'")->fetchColumn();
    $pending_inquiry_count = (int)$db->query("SELECT COUNT(*) FROM inquiries WHERE status = 'unread'")->fetchColumn();
} catch (Exception $e) {
    // Graceful fallback
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($admin_title ?? 'Admin Dashboard') ?> | <?= e(get_brand_short()) ?> Sanctuary</title>
  
  <!-- Fonts & Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=Segoe+UI:wght@400;600;700&display=swap" rel="stylesheet">
  
  <!-- Bootstrap 5 CSS & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <!-- Admin Stylesheet -->
  <link rel="stylesheet" href="<?= ASSETS_URL ?>css/admin.css?v=<?= APP_VERSION ?>">
  <link rel="icon" type="image/png" href="<?= get_brand_logo() ?>">
  <meta name="csrf-token" content="<?= csrf_token() ?>">

  <!-- Chart.js for Esports Analytics & Performance Graphs -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="admin-body">

  <!-- Sidebar -->
  <aside class="admin-sidebar" id="adminSidebar">
    <a href="<?= BASE_URL ?>admin/dashboard.php" class="admin-sidebar-brand">
      <!-- Dynamic Business Logo -->
      <img src="<?= get_brand_logo() ?>" alt="<?= e(get_brand_short()) ?> Logo" class="admin-sidebar-logo">
      <div>
        <div class="fw-bold text-light" style="letter-spacing: 0.08em; font-size: 0.95rem;"><?= e(get_brand_short()) ?></div>
        <div class="text-gold small text-uppercase" style="font-size: 0.65rem;">Sanctuary Admin</div>
      </div>
    </a>

    <ul class="admin-nav">
      <!-- 1. Dashboard -->
      <li class="admin-nav-item">
        <a href="<?= BASE_URL ?>admin/dashboard.php" class="admin-nav-link <?= $current_admin_page === 'dashboard.php' ? 'active' : '' ?>">
          <i class="bi bi-speedometer2"></i> Dashboard
        </a>
      </li>

      <!-- 2. Bookings -->
      <li class="admin-nav-item">
        <a href="<?= BASE_URL ?>admin/bookings.php" class="admin-nav-link <?= in_array($current_admin_page, ['bookings.php', 'booking-create.php', 'booking-detail.php']) ? 'active' : '' ?>">
          <i class="bi bi-calendar2-check"></i> Bookings
          <?php if ($pending_wa_badge_count > 0): ?>
            <span class="badge bg-success text-dark admin-nav-badge" title="<?= $pending_wa_badge_count ?> WhatsApp notification(s) pending"><i class="bi bi-whatsapp"></i> <?= $pending_wa_badge_count ?></span>
          <?php endif; ?>
        </a>
      </li>

      <!-- 3. Gaming Stations -->
      <li class="admin-nav-item">
        <a href="<?= BASE_URL ?>admin/stations.php" class="admin-nav-link <?= $current_admin_page === 'stations.php' ? 'active' : '' ?>">
          <i class="bi bi-display"></i> Gaming Stations
        </a>
      </li>

      <!-- 4. Availability & Hours -->
      <li class="admin-nav-item">
        <a href="<?= BASE_URL ?>admin/availability.php" class="admin-nav-link <?= $current_admin_page === 'availability.php' ? 'active' : '' ?>">
          <i class="bi bi-clock-history"></i> Availability & Hours
        </a>
      </li>

      <?php if (is_admin_owner()): ?>
      <!-- 5. Pricing (Owner Only) -->
      <li class="admin-nav-item">
        <a href="<?= BASE_URL ?>admin/pricing.php" class="admin-nav-link <?= $current_admin_page === 'pricing.php' ? 'active' : '' ?>">
          <i class="bi bi-currency-rupee"></i> Pricing & Passes
        </a>
      </li>
      <?php endif; ?>

      <!-- 6. Payments Verification -->
      <li class="admin-nav-item">
        <a href="<?= BASE_URL ?>admin/payments.php" class="admin-nav-link <?= $current_admin_page === 'payments.php' ? 'active' : '' ?>">
          <i class="bi bi-shield-check"></i> Payments
          <?php if ($pending_pay_count > 0): ?>
            <span class="badge bg-warning text-dark admin-nav-badge"><?= $pending_pay_count ?></span>
          <?php endif; ?>
        </a>
      </li>

      <?php if (is_admin_owner()): ?>
      <!-- 7. UPI QR Management (Owner Only) -->
      <li class="admin-nav-item">
        <a href="<?= BASE_URL ?>admin/upi-qr.php" class="admin-nav-link <?= $current_admin_page === 'upi-qr.php' ? 'active' : '' ?>">
          <i class="bi bi-qr-code-scan"></i> UPI QR Setup
        </a>
      </li>
      <?php endif; ?>

      <!-- 8. Customers & Marketing -->
      <li class="admin-nav-item">
        <a href="<?= BASE_URL ?>admin/customers.php" class="admin-nav-link <?= $current_admin_page === 'customers.php' ? 'active' : '' ?>">
          <i class="bi bi-people"></i> Customers & Broadcast
        </a>
      </li>

      <!-- 9. Inquiries & Contact Messages -->
      <li class="admin-nav-item">
        <a href="<?= BASE_URL ?>admin/inquiries.php" class="admin-nav-link <?= $current_admin_page === 'inquiries.php' ? 'active' : '' ?>">
          <i class="bi bi-chat-left-dots"></i> Inquiries &amp; Messages
          <?php if ($pending_inquiry_count > 0): ?>
            <span class="badge bg-danger admin-nav-badge" title="<?= $pending_inquiry_count ?> unread inquiry message(s)"><?= $pending_inquiry_count ?></span>
          <?php endif; ?>
        </a>
      </li>

      <!-- 9. Offers -->
      <li class="admin-nav-item">
        <a href="<?= BASE_URL ?>admin/offers.php" class="admin-nav-link <?= $current_admin_page === 'offers.php' ? 'active' : '' ?>">
          <i class="bi bi-tag"></i> Offers & Promos
        </a>
      </li>

      <!-- 10. Gallery -->
      <li class="admin-nav-item">
        <a href="<?= BASE_URL ?>admin/gallery.php" class="admin-nav-link <?= $current_admin_page === 'gallery.php' ? 'active' : '' ?>">
          <i class="bi bi-images"></i> Arena Gallery
        </a>
      </li>

      <!-- 11. Reviews Moderation -->
      <li class="admin-nav-item">
        <a href="<?= BASE_URL ?>admin/reviews.php" class="admin-nav-link <?= $current_admin_page === 'reviews.php' ? 'active' : '' ?>">
          <i class="bi bi-chat-heart"></i> Player Reviews
          <?php if ($pending_rev_count > 0): ?>
            <span class="badge bg-danger admin-nav-badge"><?= $pending_rev_count ?></span>
          <?php endif; ?>
        </a>
      </li>

      <!-- 12. Reports & Analytics -->
      <li class="admin-nav-item">
        <a href="<?= BASE_URL ?>admin/reports.php" class="admin-nav-link <?= $current_admin_page === 'reports.php' ? 'active' : '' ?>">
          <i class="bi bi-bar-chart-line"></i> Reports
        </a>
      </li>

      <?php if (is_admin_owner()): ?>
      <!-- 13. Team & Staff Management (Owner Only) -->
      <li class="admin-nav-item">
        <a href="<?= BASE_URL ?>admin/users.php" class="admin-nav-link <?= $current_admin_page === 'users.php' ? 'active' : '' ?>">
          <i class="bi bi-person-gear"></i> Staff & Team
        </a>
      </li>

      <!-- 14. Website Settings (Owner Only) -->
      <li class="admin-nav-item">
        <a href="<?= BASE_URL ?>admin/settings.php" class="admin-nav-link <?= $current_admin_page === 'settings.php' ? 'active' : '' ?>">
          <i class="bi bi-gear"></i> Website Settings
        </a>
      </li>
      <?php endif; ?>

      <!-- Activity Logs -->
      <li class="admin-nav-item">
        <a href="<?= BASE_URL ?>admin/logs.php" class="admin-nav-link <?= $current_admin_page === 'logs.php' ? 'active' : '' ?>">
          <i class="bi bi-journal-text"></i> Activity Logs
        </a>
      </li>

      <!-- 14. Logout -->
      <li class="admin-nav-item mt-3 pt-3 border-top border-secondary border-opacity-25">
        <a href="<?= BASE_URL ?>admin/logout.php" class="admin-nav-link text-danger">
          <i class="bi bi-box-arrow-right text-danger"></i> Sign Out
        </a>
      </li>
    </ul>

    <!-- Logged in Admin Footer in Sidebar -->
    <div class="p-3 border-top border-secondary border-opacity-25 text-muted-iron small">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-person-circle fs-5 text-gold"></i>
        <div class="overflow-hidden">
          <div class="text-light fw-bold text-truncate"><?= e($admin_user['full_name']) ?></div>
          <div class="text-secondary small text-uppercase" style="font-size: 0.65rem;"><?= e($admin_user['role']) ?></div>
        </div>
      </div>
    </div>
  </aside>

  <!-- Main Content Wrapper -->
  <div class="admin-main-wrapper">
    
    <!-- Topbar Navigation -->
    <header class="admin-topbar">
      <div class="d-flex align-items-center gap-3">
        <button type="button" class="btn btn-outline-secondary btn-sm d-lg-none" id="sidebarToggleBtn" aria-label="Toggle Sidebar">
          <i class="bi bi-list fs-5"></i>
        </button>
        <span class="fs-5 fw-bold text-light brand-font d-none d-sm-inline"><?= e($admin_title ?? 'Dashboard') ?></span>
      </div>

      <div class="d-flex align-items-center gap-3">
        <a href="<?= BASE_URL ?>" target="_blank" class="btn btn-sm btn-outline-warning">
          <i class="bi bi-box-arrow-up-right me-1"></i> View Live Sanctuary
        </a>
        <a href="<?= BASE_URL ?>admin/profile.php" class="btn btn-sm btn-outline-secondary" title="My Profile / Change Password">
          <i class="bi bi-person-gear"></i>
        </a>
      </div>
    </header>

    <!-- Page Content Container -->
    <div class="admin-content-container">
      <?= display_flash() ?>
