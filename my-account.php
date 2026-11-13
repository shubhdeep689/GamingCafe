<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Gamer Account & Reservation Dashboard
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

require_customer();

$db = get_db();
$user = current_customer();

// Fetch fresh customer record
$stmt_u = $db->prepare("SELECT * FROM customers WHERE id = :id");
$stmt_u->execute([':id' => $user['id']]);
$customer = $stmt_u->fetch();

if (!$customer) {
    customer_logout();
    redirect(BASE_URL . 'login.php');
}

// Handle Profile Update / Password Change
$msg_success = '';
$msg_error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        $name  = trim($_POST['full_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));

        if (mb_strlen($name) < 2) {
            $msg_error = 'Please enter a valid full name.';
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $msg_error = 'Please enter a valid email address.';
        } else {
            $upd = $db->prepare("UPDATE customers SET full_name = :n, email = :e WHERE id = :id");
            $upd->execute([':n' => $name, ':e' => $email ?: null, ':id' => $customer['id']]);
            $_SESSION['customer_name'] = $name;
            $_SESSION['customer_email'] = $email;
            $customer['full_name'] = $name;
            $customer['email'] = $email;
            $msg_success = 'Profile details updated successfully!';
        }
    } elseif ($_POST['action'] === 'change_password') {
        $curr_pass = trim($_POST['current_password'] ?? '');
        $new_pass  = trim($_POST['new_password'] ?? '');

        if (empty($customer['password_hash']) || password_verify($curr_pass, $customer['password_hash'])) {
            if (strlen($new_pass) < 6) {
                $msg_error = 'New password must be at least 6 characters.';
            } else {
                $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);
                $upd = $db->prepare("UPDATE customers SET password_hash = :h WHERE id = :id");
                $upd->execute([':h' => $new_hash, ':id' => $customer['id']]);
                $msg_success = 'Password changed successfully!';
            }
        } else {
            $msg_error = 'Current password does not match.';
        }
    }
}

// Fetch all bookings for this customer (by customer_id or customer phone)
$stmt_b = $db->prepare("
    SELECT b.*, s.name AS station_name, s.code AS station_code, 
           sc.name AS category_name, p.status AS payment_status, p.payment_method
    FROM bookings b
    JOIN stations s ON b.station_id = s.id
    JOIN station_categories sc ON s.category_id = sc.id
    JOIN customers c ON b.customer_id = c.id
    LEFT JOIN payments p ON p.booking_id = b.id
    WHERE b.customer_id = :cid OR c.phone = :ph
    ORDER BY b.booking_date DESC, b.start_time DESC
");
$stmt_b->execute([':cid' => $customer['id'], ':ph' => $customer['phone']]);
$bookings = $stmt_b->fetchAll();

// Group into Upcoming vs Past
$today = date('Y-m-d');
$now_time = date('H:i:s');
$upcoming = [];
$past = [];

foreach ($bookings as $b) {
    if ($b['booking_date'] > $today || ($b['booking_date'] === $today && $b['end_time'] >= $now_time)) {
        $upcoming[] = $b;
    } else {
        $past[] = $b;
    }
}

$page_title = 'Player Dashboard | ' . get_brand_short();
require_once __DIR__ . '/includes/header.php';
?>

<section class="py-5 section-gta6-bg position-relative" data-bg-url="<?= BASE_URL ?>images/gta6_bg.jpeg" style="background-image: linear-gradient(180deg, rgba(9, 11, 16, 0.40) 0%, rgba(9, 11, 16, 0.16) 50%, rgba(9, 11, 16, 0.48) 100%), url('<?= BASE_URL ?>images/gta6_bg.jpeg'); min-height: 85vh;">
  <div class="container py-3">

    <!-- Flash Messages -->
    <?php if (!empty($msg_success)): ?>
      <div class="alert alert-success py-2 px-3 small d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-check-circle-fill fs-5"></i>
        <div><?= e($msg_success) ?></div>
      </div>
    <?php endif; ?>

    <?php if (!empty($msg_error)): ?>
      <div class="alert alert-danger py-2 px-3 small d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div><?= e($msg_error) ?></div>
      </div>
    <?php endif; ?>

    <!-- Player Banner Header -->
    <div class="iron-card p-4 mb-4 position-relative overflow-hidden" style="background: radial-gradient(circle at top right, rgba(0,229,255,0.15) 0%, #121620 70%); border: 1px solid var(--border-color);">
      <div class="row align-items-center g-3">
        <div class="col-md-8 d-flex align-items-center gap-3">
          <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 65px; height: 65px; background: rgba(214,168,75,0.15); border: 2px solid var(--gold); color: var(--gold); font-size: 1.8rem;">
            <i class="bi bi-controller"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2">
              <h2 class="h4 brand-font text-light mb-0"><?= e($customer['full_name']) ?></h2>
              <span class="badge bg-gold text-dark fw-bold small text-uppercase">Verified Gamer</span>
            </div>
            <div class="text-muted-iron small mt-1">
              <i class="bi bi-telephone text-cyan me-1"></i> +91 <?= e($customer['phone']) ?> &bull; 
              <i class="bi bi-envelope text-gold ms-1 me-1"></i> <?= e($customer['email'] ?: 'No email configured') ?>
            </div>
          </div>
        </div>

        <div class="col-md-4 text-md-end d-flex flex-wrap gap-2 justify-content-md-end">
          <a href="<?= BASE_URL ?>book.php" class="btn btn-iron btn-iron-primary btn-iron-sm">
            <i class="bi bi-plus-circle-fill me-1"></i> Book A Rig
          </a>
          <button type="button" class="btn btn-outline-secondary btn-sm text-light" data-bs-toggle="modal" data-bs-target="#profileModal">
            <i class="bi bi-gear-fill me-1"></i> Settings
          </button>
          <a href="<?= BASE_URL ?>logout.php" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-box-arrow-right me-1"></i> Logout
          </a>
        </div>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="iron-card p-3 text-center">
          <span class="text-muted-iron small d-block mb-1">Total Sessions</span>
          <span class="fs-4 fw-bold text-gold brand-font"><?= count($bookings) ?></span>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="iron-card p-3 text-center">
          <span class="text-muted-iron small d-block mb-1">Active / Upcoming</span>
          <span class="fs-4 fw-bold text-cyan brand-font"><?= count($upcoming) ?></span>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="iron-card p-3 text-center">
          <span class="text-muted-iron small d-block mb-1">Total Spent</span>
          <span class="fs-4 fw-bold text-green brand-font"><?= format_currency($customer['total_spent']) ?></span>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="iron-card p-3 text-center">
          <span class="text-muted-iron small d-block mb-1">Member Since</span>
          <span class="fs-5 fw-bold text-light brand-font"><?= date('M Y', strtotime($customer['created_at'])) ?></span>
        </div>
      </div>
    </div>

    <!-- Section 1: Upcoming & Live Reservations -->
    <div class="mb-5">
      <h3 class="h5 brand-font text-gold mb-3 d-flex align-items-center gap-2">
        <i class="bi bi-clock-history text-cyan"></i> Active &amp; Upcoming Reservations (<?= count($upcoming) ?>)
      </h3>

      <?php if (!empty($upcoming)): ?>
        <div class="row g-3">
          <?php foreach ($upcoming as $b): ?>
            <div class="col-lg-6">
              <div class="iron-card p-4 h-100 position-relative" style="border-left: 4px solid var(--gold);">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <div>
                    <span class="text-muted-iron small d-block">Booking Reference</span>
                    <strong class="fs-5 text-light font-monospace">#<?= e($b['booking_ref']) ?></strong>
                  </div>
                  <?php
                    $badge = match($b['status']) {
                      'confirmed' => 'bg-success text-white',
                      'in_session' => 'bg-info text-dark',
                      default => 'bg-warning text-dark'
                    };
                  ?>
                  <span class="badge <?= $badge ?> text-uppercase px-2 py-1"><?= e($b['status']) ?></span>
                </div>

                <div class="row g-2 small my-3 p-2 rounded" style="background: rgba(0,0,0,0.3);">
                  <div class="col-6">
                    <span class="text-muted-iron">Station / Rig:</span><br>
                    <strong class="text-gold"><?= e($b['station_name']) ?></strong> (<?= e($b['category_name']) ?>)
                  </div>
                  <div class="col-6">
                    <span class="text-muted-iron">Session Date:</span><br>
                    <strong class="text-light"><?= date('D, d M Y', strtotime($b['booking_date'])) ?></strong>
                  </div>
                  <div class="col-6">
                    <span class="text-muted-iron">Slot Timing:</span><br>
                    <strong class="text-cyan"><?= date('g:i A', strtotime($b['start_time'])) ?> – <?= date('g:i A', strtotime($b['end_time'])) ?></strong> (<?= $b['duration_hours'] ?>h)
                  </div>
                  <div class="col-6">
                    <span class="text-muted-iron">Total Amount:</span><br>
                    <strong class="text-green"><?= format_currency($b['final_amount']) ?></strong>
                  </div>
                </div>

                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-25">
                  <a href="<?= BASE_URL ?>receipt.php?ref=<?= urlencode($b['booking_ref']) ?>&phone=<?= urlencode($customer['phone']) ?>" class="btn btn-warning btn-sm text-dark fw-bold">
                    <i class="bi bi-file-earmark-pdf-fill me-1"></i> View Entry Pass
                  </a>

                  <?php if ($b['status'] === 'pending'): ?>
                    <a href="<?= BASE_URL ?>check-booking.php?ref=<?= urlencode($b['booking_ref']) ?>&pay=razorpay" class="btn btn-danger btn-sm fw-bold">
                      <i class="bi bi-lightning-charge-fill me-1"></i> Complete Payment
                    </a>
                  <?php endif; ?>
                </div>

              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="iron-card p-4 text-center text-muted-iron">
          <i class="bi bi-calendar-x fs-1 text-gold d-block mb-2"></i>
          <p class="mb-2">You have no upcoming reservations scheduled.</p>
          <a href="<?= BASE_URL ?>book.php" class="btn btn-iron btn-iron-primary btn-iron-sm">
            <i class="bi bi-controller me-1"></i> Reserve Your Rig Now
          </a>
        </div>
      <?php endif; ?>
    </div>

    <!-- Section 2: Full Booking History Table -->
    <div>
      <h3 class="h5 brand-font text-gold mb-3 d-flex align-items-center gap-2">
        <i class="bi bi-archive-fill text-cyan"></i> Complete Session History (<?= count($bookings) ?>)
      </h3>

      <div class="iron-card p-3 p-md-4">
        <div class="table-responsive">
          <table class="table table-dark align-middle mb-0">
            <thead>
              <tr style="border-bottom: 2px solid var(--border-color);">
                <th>Reference</th>
                <th>Station</th>
                <th>Date &amp; Time</th>
                <th>Duration</th>
                <th>Amount</th>
                <th>Status</th>
                <th class="text-end">Pass / Receipt</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($bookings)): ?>
                <?php foreach ($bookings as $b): ?>
                  <tr>
                    <td>
                      <strong class="font-monospace text-light">#<?= e($b['booking_ref']) ?></strong>
                    </td>
                    <td>
                      <span class="text-gold fw-bold"><?= e($b['station_name']) ?></span>
                      <div class="text-muted-iron small" style="font-size: 0.72rem;"><?= e($b['category_name']) ?></div>
                    </td>
                    <td>
                      <div class="text-light small"><?= date('d M Y', strtotime($b['booking_date'])) ?></div>
                      <div class="text-muted-iron small" style="font-size: 0.72rem;"><?= date('g:i A', strtotime($b['start_time'])) ?> – <?= date('g:i A', strtotime($b['end_time'])) ?></div>
                    </td>
                    <td>
                      <span class="badge bg-secondary"><?= $b['duration_hours'] ?> Hr(s)</span>
                    </td>
                    <td>
                      <strong class="text-green"><?= format_currency($b['final_amount']) ?></strong>
                    </td>
                    <td>
                      <?php
                        $st_class = match($b['status']) {
                          'confirmed' => 'bg-success',
                          'in_session' => 'bg-info text-dark',
                          'completed' => 'bg-secondary',
                          default => 'bg-warning text-dark'
                        };
                      ?>
                      <span class="badge <?= $st_class ?> text-uppercase"><?= e($b['status']) ?></span>
                    </td>
                    <td class="text-end">
                      <a href="<?= BASE_URL ?>receipt.php?ref=<?= urlencode($b['booking_ref']) ?>&phone=<?= urlencode($customer['phone']) ?>" class="btn btn-outline-warning btn-sm" title="View Pass">
                        <i class="bi bi-ticket-detailed me-1"></i> Pass
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7" class="text-center py-4 text-muted-iron">
                    No session history recorded yet. Book your first session to unlock gamer rewards!
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</section>

<!-- Profile & Security Settings Modal -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background: #11151E; border: 1px solid var(--border-color); color: #fff;">
      <div class="modal-header border-secondary border-opacity-25">
        <h5 class="modal-title text-gold brand-font" id="profileModalLabel"><i class="bi bi-gear-fill me-2"></i>Account Settings</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        
        <!-- Tab Nav -->
        <ul class="nav nav-pills mb-4" id="pills-tab" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active btn-sm" id="pills-profile-tab" data-bs-toggle="pill" data-bs-target="#pills-profile" type="button" role="tab">Gamer Profile</button>
          </li>
          <li class="nav-item ms-2" role="presentation">
            <button class="nav-link btn-sm" id="pills-pass-tab" data-bs-toggle="pill" data-bs-target="#pills-pass" type="button" role="tab">Change Password</button>
          </li>
        </ul>

        <div class="tab-content" id="pills-tabContent">
          <!-- Profile Tab -->
          <div class="tab-pane fade show active" id="pills-profile" role="tabpanel">
            <form method="POST" action="<?= BASE_URL ?>my-account.php">
              <input type="hidden" name="action" value="update_profile">

              <div class="mb-3">
                <label for="edit_full_name" class="form-label text-light small fw-bold">Full Name</label>
                <input type="text" class="form-control bg-dark text-light border-secondary" id="edit_full_name" name="full_name" autocomplete="name" value="<?= e($customer['full_name']) ?>" required>
              </div>

              <div class="mb-3">
                <label class="form-label text-light small fw-bold">Mobile Phone (Account ID)</label>
                <input type="text" class="form-control bg-dark text-muted-iron border-secondary" value="+91 <?= e($customer['phone']) ?>" disabled>
                <div class="form-text text-muted-iron" style="font-size: 0.72rem;">Mobile number is linked to your verified booking passes.</div>
              </div>

              <div class="mb-4">
                <label for="edit_email" class="form-label text-light small fw-bold">Email Address</label>
                <input type="email" class="form-control bg-dark text-light border-secondary" id="edit_email" name="email" autocomplete="username" spellcheck="false" value="<?= e($customer['email']) ?>" placeholder="e.g. player@gmail.com">
              </div>

              <button type="submit" class="btn btn-iron btn-iron-primary w-100">Save Profile Changes</button>
            </form>
          </div>

          <!-- Password Tab -->
          <div class="tab-pane fade" id="pills-pass" role="tabpanel">
            <form method="POST" action="<?= BASE_URL ?>my-account.php">
              <input type="hidden" name="action" value="change_password">

              <?php if (!empty($customer['password_hash'])): ?>
                <div class="mb-3">
                  <label for="curr_pass" class="form-label text-light small fw-bold">Current Password</label>
                  <input type="password" class="form-control bg-dark text-light border-secondary" id="curr_pass" name="current_password" autocomplete="current-password" required>
                </div>
              <?php endif; ?>

              <div class="mb-4">
                <label for="new_pass" class="form-label text-light small fw-bold">New Password</label>
                <input type="password" class="form-control bg-dark text-light border-secondary" id="new_pass" name="new_password" autocomplete="new-password" placeholder="At least 6 characters" required>
              </div>

              <button type="submit" class="btn btn-iron btn-iron-cyan w-100">Update Password</button>
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

