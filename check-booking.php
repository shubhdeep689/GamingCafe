<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Booking Lookup & UPI QR Payment Verification Portal
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$brand_name  = get_brand_name();
$brand_short = get_brand_short();
$page_title = 'Check Booking Status & UPI Payment | ' . $brand_name;
$page_desc = 'Check your gaming session reservation status, view official UPI QR scanner, submit your UTR reference, and track verification.';

$db = get_db();
$errors = [];
$booking = null;
$payment = null;

$ref = strtoupper(trim($_GET['ref'] ?? $_POST['booking_ref'] ?? ''));
$phone = trim($_GET['phone'] ?? $_POST['phone'] ?? '');
$clean_phone = preg_replace('/[^\d]/', '', $phone);

// 1. If lookup parameters provided, fetch booking
if (!empty($ref)) {
    $sql = "
        SELECT b.*, s.name AS station_name, s.code AS station_code, 
               c.name AS category_name, cust.full_name AS customer_name, cust.phone AS customer_phone
        FROM bookings b
        JOIN stations s ON b.station_id = s.id
        JOIN station_categories c ON s.category_id = c.id
        JOIN customers cust ON b.customer_id = cust.id
        WHERE b.booking_ref = :ref
    ";
    $params = [':ref' => $ref];

    if (!empty($clean_phone)) {
        $sql .= " AND cust.phone LIKE :phone";
        $params[':phone'] = '%' . substr($clean_phone, -10);
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $booking = $stmt->fetch();

    if ($booking) {
        // Fetch corresponding payment record
        $stmt_pay = $db->prepare("SELECT * FROM payments WHERE booking_id = :bid ORDER BY id DESC LIMIT 1");
        $stmt_pay->execute([':bid' => $booking['id']]);
        $payment = $stmt_pay->fetch();
    } else {
        $errors[] = 'No booking found matching the provided reference and contact details. Please check your reference code.';
    }
}

// 2. Handle UTR / Payment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment'])) {
    if (!verify_csrf()) {
        $errors[] = 'Security token invalid or expired. Please refresh and retry.';
    } elseif (!$booking) {
        $errors[] = 'No active booking loaded to attach payment details.';
    } else {
        $utr = trim($_POST['upi_transaction_id'] ?? '');
        $utr_clean = preg_replace('/[^a-zA-Z0-9]/', '', $utr);

        if (empty($utr_clean) || strlen($utr_clean) < 8) {
            $errors[] = 'Please provide a valid UPI Transaction ID / UTR reference number (minimum 8 characters).';
        } else {
            $proof_path = null;

            // Optional Screenshot Upload
            if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] === UPLOAD_ERR_OK) {
                $upload_result = handle_file_upload($_FILES['proof_file'], 'payments/');
                if ($upload_result['success']) {
                    $proof_path = $upload_result['path'];
                } else {
                    $errors[] = 'Payment proof upload failed: ' . $upload_result['error'];
                }
            }

            if (empty($errors)) {
                try {
                    if ($payment) {
                        $upd_pay = $db->prepare("
                            UPDATE payments 
                            SET upi_transaction_id = :utr,
                                proof_file = COALESCE(:proof, proof_file),
                                status = 'pending',
                                created_at = NOW()
                            WHERE id = :id
                        ");
                        $upd_pay->execute([
                            ':utr'   => $utr_clean,
                            ':proof' => $proof_path,
                            ':id'    => $payment['id']
                        ]);
                    } else {
                        $ins_pay = $db->prepare("
                            INSERT INTO payments (booking_id, amount, payment_method, upi_transaction_id, proof_file, status)
                            VALUES (:bid, :amt, 'upi_qr', :utr, :proof, 'pending')
                        ");
                        $ins_pay->execute([
                            ':bid'   => $booking['id'],
                            ':amt'   => $booking['final_amount'],
                            ':utr'   => $utr_clean,
                            ':proof' => $proof_path
                        ]);
                    }

                    set_flash('success', 'UPI Transaction details submitted successfully! The owner will verify your payment and confirm your slot.');
                    redirect(BASE_URL . 'check-booking.php?ref=' . urlencode($booking['booking_ref']) . '&phone=' . urlencode($booking['customer_phone']));
                } catch (Exception $e) {
                    error_log('Payment Submit Error: ' . $e->getMessage());
                    $errors[] = 'Failed to submit payment reference. Please retry or contact the sanctuary.';
                }
            }
        }
    }
}

// 3. Handle Time Change / Reschedule Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_request_reschedule'])) {
    if (!verify_csrf()) {
        $errors[] = 'Security token invalid or expired. Please refresh.';
    } elseif (!$booking) {
        $errors[] = 'No active booking loaded.';
    } elseif (in_array($booking['status'], ['completed', 'cancelled', 'rejected'])) {
        $errors[] = 'This booking is ' . $booking['status'] . ' and cannot be rescheduled.';
    } else {
        $req_station_id = (int)($_POST['reschedule_station_id'] ?? $booking['station_id']);
        if ($req_station_id <= 0) $req_station_id = (int)$booking['station_id'];
        $req_date = trim($_POST['reschedule_date'] ?? '');
        $req_time = trim($_POST['reschedule_time'] ?? '');
        $req_dur = (int)($_POST['reschedule_duration'] ?? $booking['duration_hours']);
        $req_reason = trim($_POST['reschedule_reason'] ?? '');

        if (empty($req_date) || strtotime($req_date) < strtotime(date('Y-m-d'))) {
            $errors[] = 'Please select a valid upcoming date.';
        } elseif (empty($req_time)) {
            $errors[] = 'Please select your requested start time.';
        } else {
            if (strlen($req_time) === 5) $req_time .= ':00';
            
            // Check station availability for requested slot & station
            $avail = is_slot_available($req_station_id, $req_date, $req_time, $req_dur, (int)$booking['id']);
            if (!$avail) {
                $errors[] = 'The requested station or slot is already reserved at that timing. Please pick another station or time.';
            } else {
                try {
                    $upd_res = $db->prepare("
                        UPDATE bookings 
                        SET reschedule_requested_station_id = :rsid,
                            reschedule_requested_date = :rdate,
                            reschedule_requested_time = :rtime,
                            reschedule_requested_duration = :rdur,
                            reschedule_reason = :rreason,
                            reschedule_status = 'pending'
                        WHERE id = :id
                    ");
                    $upd_res->execute([
                        ':rsid'    => $req_station_id,
                        ':rdate'   => $req_date,
                        ':rtime'   => $req_time,
                        ':rdur'    => $req_dur,
                        ':rreason' => $req_reason ?: 'Customer requested timing or station change',
                        ':id'      => $booking['id']
                    ]);

                    set_flash('success', 'Your change request has been submitted to the owner! You will see the updated slot once confirmed.');
                    redirect(BASE_URL . 'check-booking.php?ref=' . urlencode($booking['booking_ref']) . '&phone=' . urlencode($booking['customer_phone']));
                } catch (Exception $e) {
                    error_log('Reschedule Error: ' . $e->getMessage());
                    $errors[] = 'Failed to submit reschedule request: ' . $e->getMessage();
                }
            }
        }
    }
}

// Payment Settings
$upi_id = get_setting('upi_id', 'ironclad@upi');
$upi_merchant = get_setting('upi_merchant_name', 'IRONCLAD GAMING SANCTUARY');
$upi_qr_img = get_setting('upi_qr_image', 'images/sample_qr.svg');
$payment_instructions = get_setting('payment_instructions', 'Scan the UPI QR code using any UPI app. Enter the exact total amount. After completing payment, submit your 12-digit transaction ID below.');

// All active stations for station change request
$all_stations = $db->query("SELECT id, name, code, hourly_rate FROM stations WHERE status = 'active' ORDER BY display_order ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Banner -->
<section class="py-5 position-relative overflow-hidden" style="background: radial-gradient(circle at 50% 20%, rgba(34, 43, 61, 0.6) 0%, var(--bg-primary) 80%); border-bottom: 1px solid var(--border-color);">
  <div class="container py-4 text-center position-relative z-2">
    <span class="section-subtitle">Self-Service Portal</span>
    <h1 class="display-5 fw-bold mt-2">Check Booking & Complete Payment</h1>
    <p class="text-muted-iron mx-auto" style="max-width: 650px;">
      Track your reservation status, complete manual UPI payment verification, and review station allocation.
    </p>
  </div>
</section>

<section class="py-5">
  <div class="container py-2">
    
    <!-- Flash Messages & Errors -->
    <?= display_flash() ?>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger shadow-sm mb-4">
        <h5 class="alert-heading fs-6 fw-bold mb-2"><i class="bi bi-exclamation-octagon-fill me-2"></i>Notice</h5>
        <ul class="mb-0 small ps-3">
          <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <!-- Lookup Form if not already loaded or user wants to lookup another -->
    <?php if (!$booking): ?>
      <div class="row justify-content-center">
        <div class="col-lg-6">
          <div class="iron-card p-4 p-md-5">
            <div class="text-center mb-4">
              <i class="bi bi-search text-gold display-5 mb-2"></i>
              <h3 class="h4 text-light">Lookup Your Booking</h3>
              <p class="text-muted-iron small">Enter your unique booking reference and registered mobile number.</p>
            </div>

            <form method="GET" action="<?= BASE_URL ?>check-booking.php">
              <div class="mb-3">
                <label for="searchRef" class="form-label">Booking Reference Code <span class="text-danger">*</span></label>
                <input type="text" name="ref" id="searchRef" class="form-control text-uppercase font-monospace" placeholder="e.g. IC-2026-X8K9M" value="<?= e($ref) ?>" required>
              </div>

              <div class="mb-4">
                <label for="searchPhone" class="form-label">Registered Mobile Number <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text bg-dark text-muted-iron border-secondary border-opacity-25">+91</span>
                  <input type="tel" name="phone" id="searchPhone" class="form-control" placeholder="10-digit mobile number" value="<?= e($phone) ?>" required>
                </div>
              </div>

              <button type="submit" class="btn btn-iron btn-iron-primary w-100 py-3">
                <i class="bi bi-search me-1"></i> Check Booking Status
              </button>
            </form>
          </div>
        </div>
      </div>
    <?php else: ?>

      <!-- Booking Found: Status Dashboard -->
      <?php if (($booking['reschedule_status'] ?? 'none') === 'pending'): ?>
        <div class="alert alert-warning py-3 px-4 mb-4 border border-warning shadow-sm" style="background: rgba(214,168,75,0.12); color: #F5F7FA;">
          <div class="d-flex align-items-center gap-3">
            <i class="bi bi-clock-history fs-2 text-gold"></i>
            <div>
              <h6 class="alert-heading fw-bold mb-1 text-gold">Time Change Request Pending Verification</h6>
              <p class="mb-0 small">
                You requested to change this session to <strong><?= date('l, d M Y', strtotime($booking['reschedule_requested_date'])) ?> at <?= date('g:i A', strtotime($booking['reschedule_requested_time'])) ?></strong> (<?= (int)$booking['reschedule_requested_duration'] ?> hour(s)).<br>
                Note: <em>"<?= e($booking['reschedule_reason'] ?? 'Customer requested reschedule') ?>"</em>.<br>
                The sanctuary owner will verify station availability and confirm the updated timing shortly.
              </p>
            </div>
          </div>
        </div>
      <?php elseif (($booking['reschedule_status'] ?? 'none') === 'approved'): ?>
        <div class="alert alert-success py-2 px-3 mb-4 d-flex align-items-center gap-2">
          <i class="bi bi-check-circle-fill text-success fs-5"></i>
          <span class="small">Your time change request was approved by the owner! Your updated timings are shown below.</span>
        </div>
      <?php endif; ?>

      <div class="row g-4">
        
        <!-- Booking Details Card -->
        <div class="col-lg-6">
          <div class="iron-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary border-opacity-25">
              <div>
                <span class="text-muted-iron small d-block">Booking Reference</span>
                <span class="fs-4 fw-bold text-gold font-monospace"><?= e($booking['booking_ref']) ?></span>
                <button type="button" class="btn btn-sm btn-outline-secondary btn-copy-ref py-0 px-2 ms-2" data-copy="<?= e($booking['booking_ref']) ?>" title="Copy to clipboard">
                  <i class="bi bi-clipboard"></i>
                </button>
              </div>
              <div>
                <?= booking_status_badge($booking['status']) ?>
              </div>
            </div>

            <div class="row g-3 mb-4">
              <div class="col-6">
                <span class="text-muted-iron small d-block">Player Name:</span>
                <strong class="text-light"><?= e($booking['customer_name']) ?></strong>
              </div>
              <div class="col-6">
                <span class="text-muted-iron small d-block">Mobile Phone:</span>
                <strong class="text-light">+91 <?= e($booking['customer_phone']) ?></strong>
              </div>
              <div class="col-6">
                <span class="text-muted-iron small d-block">Assigned Station:</span>
                <strong class="text-cyan"><?= e($booking['station_name']) ?></strong>
                <span class="badge bg-dark border border-secondary text-secondary small d-block mt-1"><?= e($booking['category_name']) ?></span>
              </div>
              <div class="col-6">
                <span class="text-muted-iron small d-block">Date of Battle:</span>
                <strong class="text-light"><?= date('l, d M Y', strtotime($booking['booking_date'])) ?></strong>
              </div>
              <div class="col-6">
                <span class="text-muted-iron small d-block">Time Slot:</span>
                <strong class="text-gold"><?= date('g:i A', strtotime($booking['start_time'])) ?> – <?= date('g:i A', strtotime($booking['end_time'])) ?></strong>
              </div>
              <div class="col-6">
                <span class="text-muted-iron small d-block">Duration:</span>
                <strong class="text-light"><?= $booking['duration_hours'] ?> Hour(s)</strong>
              </div>
            </div>

            <div class="p-3 rounded mb-4" style="background: rgba(0,0,0,0.3); border: 1px solid var(--border-color);">
              <div class="d-flex justify-content-between text-muted-iron small mb-2">
                <span>Subtotal Rate:</span>
                <span><?= format_currency($booking['total_amount']) ?></span>
              </div>
              <?php if ((float)$booking['discount_amount'] > 0): ?>
                <div class="d-flex justify-content-between text-muted-iron small mb-2">
                  <span>Applied Discount:</span>
                  <span class="text-green">-<?= format_currency($booking['discount_amount']) ?></span>
                </div>
              <?php endif; ?>
              <div class="pt-2 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
                <span class="fw-bold text-light">Total Payable Amount:</span>
                <span class="fs-4 fw-bold text-gold brand-font"><?= format_currency($booking['final_amount']) ?></span>
              </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
              <a href="<?= generate_whatsapp_url('confirmed', $booking) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-success btn-iron-sm">
                <i class="bi bi-whatsapp me-1"></i> Save to WhatsApp
              </a>
              <a href="<?= BASE_URL ?>check-booking.php" class="btn btn-iron btn-iron-outline btn-iron-sm">
                <i class="bi bi-arrow-repeat me-1"></i> Check Another
              </a>
              <?php if (!in_array($booking['status'], ['completed', 'cancelled', 'rejected'])): ?>
                <button type="button" class="btn btn-iron btn-iron-primary btn-iron-sm" data-bs-toggle="modal" data-bs-target="#rescheduleModal">
                  <i class="bi bi-arrow-left-right me-1"></i> Change Time / Station
                </button>
              <?php endif; ?>
              <a href="<?= BASE_URL ?>receipt.php?ref=<?= urlencode($booking['booking_ref']) ?>&download=pdf" target="_blank" class="btn btn-danger btn-sm fw-bold shadow-sm">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i> Download PDF Pass
              </a>
              <a href="<?= BASE_URL ?>receipt.php?ref=<?= urlencode($booking['booking_ref']) ?>&phone=<?= urlencode($booking['customer_phone']) ?>" target="_blank" class="btn btn-iron btn-iron-cyan btn-iron-sm">
                <i class="bi bi-receipt-cutoff me-1"></i> View 1-Page Receipt
              </a>
            </div>
          </div>
        </div>

        <!-- Right Column: UPI Payment Scanner & Submission -->
        <div class="col-lg-6">
          <div class="iron-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h4 class="text-gold brand-font mb-0">UPI Payment Verification</h4>
              <?php if ($payment): ?>
                <?= payment_status_badge($payment['status']) ?>
              <?php else: ?>
                <span class="badge bg-warning text-dark">Payment Required</span>
              <?php endif; ?>
            </div>

            <!-- If Verified -->
            <?php if ($payment && $payment['status'] === 'verified'): ?>
              <div class="alert alert-success py-3 px-4 mb-4">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-check-circle-fill fs-3 text-success"></i>
                  <div>
                    <h5 class="alert-heading fs-6 fw-bold mb-1">Payment Successfully Verified!</h5>
                    <p class="mb-0 small">Your seat is locked and confirmed. We look forward to seeing you at <?= e($brand_name) ?>.</p>
                  </div>
                </div>
              </div>
            <?php elseif ($payment && $payment['status'] === 'rejected'): ?>
              <div class="alert alert-danger py-3 px-4 mb-4">
                <h5 class="alert-heading fs-6 fw-bold mb-1"><i class="bi bi-x-octagon-fill me-2"></i>Payment Rejected by Admin</h5>
                <p class="mb-0 small">
                  <?= e($payment['admin_notes'] ?? 'The submitted transaction ID could not be matched. Please re-check and resubmit your valid UPI UTR.') ?>
                </p>
              </div>
            <?php endif; ?>

            <!-- QR Code Box -->
            <div class="text-center p-3 rounded mb-3" style="background: rgba(0,0,0,0.4); border: 1px solid var(--border-color);">
              <p class="text-muted-iron small mb-2">Scan & Pay using any UPI App (GPay, PhonePe, Paytm, BHIM):</p>
              
              <div class="d-inline-block p-2 bg-dark rounded-3 border border-secondary border-opacity-50 my-2" style="max-width: 260px;">
                <img src="<?= BASE_URL . e($upi_qr_img) ?>" alt="<?= e($brand_short) ?> UPI QR" class="img-fluid rounded" style="max-height: 240px; width: 100%; object-fit: contain;">
              </div>

              <div class="mt-2">
                <span class="text-muted-iron small d-block">UPI Payee ID:</span>
                <strong class="text-gold font-monospace fs-6"><?= e($upi_id) ?></strong>
                <span class="text-muted-iron small d-block mt-1">Merchant: <strong><?= e($upi_merchant) ?></strong></span>
                <span class="badge bg-gold text-dark fs-6 mt-2">Exact Amount: <?= format_currency($booking['final_amount']) ?></span>
              </div>
            </div>

            <!-- UTR Submission Form -->
            <?php if (!$payment || $payment['status'] !== 'verified'): ?>
              <form method="POST" action="<?= BASE_URL ?>check-booking.php?ref=<?= urlencode($booking['booking_ref']) ?>" enctype="multipart/form-data" class="mt-3">
                <?= csrf_field() ?>
                <input type="hidden" name="booking_ref" value="<?= e($booking['booking_ref']) ?>">
                <input type="hidden" name="phone" value="<?= e($booking['customer_phone']) ?>">
                <input type="hidden" name="submit_payment" value="1">

                <div class="mb-3">
                  <label for="upiUtr" class="form-label">
                    12-Digit UPI Transaction ID / UTR <span class="text-danger">*</span>
                  </label>
                  <input type="text" name="upi_transaction_id" id="upiUtr" class="form-control font-monospace" placeholder="e.g. 431289012345" value="<?= e($payment['upi_transaction_id'] ?? '') ?>" required>
                  <div class="form-text text-muted-iron small">Available in your GPay / PhonePe / Paytm receipt screen.</div>
                </div>

                <div class="mb-4">
                  <label for="proofFile" class="form-label">Upload Payment Screenshot Proof (Optional)</label>
                  <input type="file" name="proof_file" id="proofFile" class="form-control" accept="image/jpeg,image/png,image/webp">
                  <div class="form-text text-muted-iron small">Accepted formats: JPG, PNG, WEBP (Max 5MB).</div>
                </div>

                <button type="submit" class="btn btn-iron btn-iron-primary w-100 py-3">
                  <i class="bi bi-shield-check me-1"></i> Submit Transaction For Verification
                </button>
              </form>
            <?php else: ?>
              <div class="text-center p-3 text-muted-iron small">
                <i class="bi bi-check2-all text-success fs-5 me-1"></i>
                Transaction ID: <span class="text-light font-monospace"><?= e($payment['upi_transaction_id']) ?></span>
              </div>
            <?php endif; ?>

          </div>
        </div>

      </div>

      <!-- Customer Reschedule Request Modal -->
      <?php if (!in_array($booking['status'], ['completed', 'cancelled', 'rejected'])): ?>
        <div class="modal fade" id="rescheduleModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-start" style="background: #151922; border: 1px solid var(--border-color); color: var(--text-main);">
              <form method="POST" action="<?= BASE_URL ?>check-booking.php?ref=<?= urlencode($booking['booking_ref']) ?>&phone=<?= urlencode($booking['customer_phone']) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action_request_reschedule" value="1">
                <input type="hidden" name="booking_ref" value="<?= e($booking['booking_ref']) ?>">
                <input type="hidden" name="phone" value="<?= e($booking['customer_phone']) ?>">

                <div class="modal-header border-secondary border-opacity-25">
                  <div>
                    <h5 class="modal-title text-gold brand-font mb-0"><i class="bi bi-arrow-left-right me-2"></i>Change Time or Station</h5>
                    <small class="text-muted-iron" style="font-size: 0.75rem;">Current Station: <?= e($booking['station_name']) ?> (<?= e($booking['station_code'] ?? '') ?>)</small>
                  </div>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                  <div class="p-3 rounded mb-3" style="background: rgba(0,0,0,0.3); border: 1px solid var(--border-color);">
                    <div class="d-flex justify-content-between small text-muted-iron">
                      <span>Current Slot:</span>
                      <strong class="text-light"><?= date('d M Y', strtotime($booking['booking_date'])) ?> &bull; <?= date('g:i A', strtotime($booking['start_time'])) ?></strong>
                    </div>
                  </div>

                  <div class="row g-3">
                    <div class="col-12">
                      <label class="form-label">Preferred Station / Rig</label>
                      <select name="reschedule_station_id" class="form-select">
                        <?php foreach ($all_stations as $st): ?>
                          <option value="<?= $st['id'] ?>" <?= (int)$booking['station_id'] === (int)$st['id'] ? 'selected' : '' ?>>
                            <?= e($st['name']) ?> (<?= e($st['code']) ?>) — ₹<?= number_format((float)$st['hourly_rate'], 0) ?>/hr
                          </option>
                        <?php endforeach; ?>
                      </select>
                      <div class="form-text text-muted-iron small">Want to change to a different PC rig or console lounge? Choose above.</div>
                    </div>

                    <div class="col-md-6">
                      <label class="form-label">Desired Date <span class="text-danger">*</span></label>
                      <input type="date" name="reschedule_date" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= e($booking['booking_date']) ?>" required>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Desired Start Time <span class="text-danger">*</span></label>
                      <input type="time" name="reschedule_time" class="form-control" value="<?= substr($booking['start_time'], 0, 5) ?>" required>
                    </div>
                    <div class="col-12">
                      <label class="form-label">Desired Duration</label>
                      <select name="reschedule_duration" class="form-select">
                        <?php for ($d = 1; $d <= 6; $d++): ?>
                          <option value="<?= $d ?>" <?= (int)$booking['duration_hours'] === $d ? 'selected' : '' ?>>
                            <?= $d ?> Hour<?= $d > 1 ? 's' : '' ?> Session
                          </option>
                        <?php endfor; ?>
                      </select>
                    </div>
                    <div class="col-12">
                      <label class="form-label">Reason / Message for Owner</label>
                      <textarea name="reschedule_reason" class="form-control" rows="2" placeholder="e.g. Prefer PS5 lounge instead, or need to delay by 1 hour..."></textarea>
                    </div>
                  </div>
                </div>

                <div class="modal-footer border-secondary border-opacity-25">
                  <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                  <button type="submit" class="btn btn-iron btn-iron-primary btn-iron-sm">Submit Change Request</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      <?php endif; ?>

    <?php endif; ?>

  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

