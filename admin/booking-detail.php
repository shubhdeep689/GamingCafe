<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Booking Details, Status Control & 1-Click WhatsApp Notification Engine
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();

$admin_user = current_admin();
$db = get_db();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash('danger', 'Invalid booking ID.');
    redirect(BASE_URL . 'admin/bookings.php');
}

// Fetch Booking
$stmt = $db->prepare("
    SELECT b.*, s.name AS station_name, s.code AS station_code, s.hourly_rate,
           c.name AS category_name, cust.full_name AS customer_name, 
           cust.phone AS customer_phone, cust.email AS customer_email,
           req_s.name AS requested_station_name, req_s.hourly_rate AS requested_hourly_rate
    FROM bookings b
    JOIN stations s ON b.station_id = s.id
    JOIN station_categories c ON s.category_id = c.id
    JOIN customers cust ON b.customer_id = cust.id
    LEFT JOIN stations req_s ON b.reschedule_requested_station_id = req_s.id
    WHERE b.id = :id
");
$stmt->execute([':id' => $id]);
$booking = $stmt->fetch();

if (!$booking) {
    set_flash('danger', 'Booking record not found.');
    redirect(BASE_URL . 'admin/bookings.php');
}

// Fetch Payment
$stmt_pay = $db->prepare("SELECT * FROM payments WHERE booking_id = :bid ORDER BY id DESC LIMIT 1");
$stmt_pay->execute([':bid' => $booking['id']]);
$payment = $stmt_pay->fetch();

// Fetch all active stations for owner slot & station reassignment
$all_stations = $db->query("
    SELECT s.id, s.name, s.code, s.hourly_rate, c.name AS category_name 
    FROM stations s 
    JOIN station_categories c ON s.category_id = c.id 
    WHERE s.status = 'active'
    ORDER BY s.display_order ASC
")->fetchAll();

// Handle Approve Reschedule / Station Change Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_approve_reschedule'])) {
    if (!verify_csrf()) {
        set_flash('danger', 'Invalid security token.');
    } else {
        $target_station_id = (int)($booking['reschedule_requested_station_id'] ?: $booking['station_id']);
        $rdate = $booking['reschedule_requested_date'] ?: $booking['booking_date'];
        $rtime = $booking['reschedule_requested_time'] ?: $booking['start_time'];
        $rdur = (int)($booking['reschedule_requested_duration'] ?: $booking['duration_hours']);

        if (!$rdate || !$rtime) {
            set_flash('danger', 'No pending reschedule timing found on this booking.');
        } else {
            // Verify slot availability for target station and timing
            if (!is_slot_available($target_station_id, $rdate, $rtime, $rdur, (int)$booking['id'])) {
                set_flash('danger', 'Cannot approve: The requested slot/station conflicts with another booking. Please choose another station or time manually.');
            } else {
                // Fetch rate of target station
                $stmt_trate = $db->prepare("SELECT hourly_rate, name FROM stations WHERE id = :id");
                $stmt_trate->execute([':id' => $target_station_id]);
                $target_station = $stmt_trate->fetch();
                $trate = (float)$target_station['hourly_rate'];

                $retime = date('H:i:s', strtotime($rtime) + ($rdur * 3600));
                $rtotal = $trate * $rdur;
                $rfinal = max(0, $rtotal - (float)$booking['discount_amount']);

                $upd = $db->prepare("
                    UPDATE bookings 
                    SET station_id = :sid,
                        booking_date = :bdate,
                        start_time = :stime,
                        end_time = :etime,
                        duration_hours = :dur,
                        total_amount = :tot,
                        final_amount = :fin,
                        status = 'confirmed',
                        reschedule_status = 'approved',
                        whatsapp_status = 'pending',
                        whatsapp_pending_reason = 'Rescheduled Timing Pass Pending'
                    WHERE id = :id
                ");
                $upd->execute([
                    ':sid'   => $target_station_id,
                    ':bdate' => $rdate,
                    ':stime' => $rtime,
                    ':etime' => $retime,
                    ':dur'   => $rdur,
                    ':tot'   => $rtotal,
                    ':fin'   => $rfinal,
                    ':id'    => $booking['id']
                ]);

                // Update payment amount if exists
                if ($payment) {
                    $db->prepare("UPDATE payments SET amount = :amt WHERE id = :pid")->execute([':amt' => $rfinal, ':pid' => $payment['id']]);
                }

                $_SESSION['flash_wa_url'] = BASE_URL . "admin/whatsapp-send.php?id={$booking['id']}&type=rescheduled";

                log_admin_activity($admin_user['id'], 'approve_reschedule', "Approved reschedule for #{$booking['booking_ref']} to station {$target_station_id}, {$rdate} {$rtime}");
                set_flash('success', "Reschedule approved! Session updated to {$rdate} at " . date('g:i A', strtotime($rtime)) . ".");
                redirect(BASE_URL . "admin/booking-detail.php?id={$booking['id']}");
            }
        }
    }
}

// Handle Decline Reschedule Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_reject_reschedule'])) {
    if (!verify_csrf()) {
        set_flash('danger', 'Invalid security token.');
    } else {
        $decline_note = trim($_POST['decline_reason'] ?? 'Requested slot or station is unavailable.');
        $upd = $db->prepare("
            UPDATE bookings 
            SET reschedule_status = 'rejected',
                notes = CONCAT(COALESCE(notes, ''), '\n[Reschedule Declined: ', :note, ']')
            WHERE id = :id
        ");
        $upd->execute([':note' => $decline_note, ':id' => $booking['id']]);

        log_admin_activity($admin_user['id'], 'reject_reschedule', "Declined reschedule for #{$booking['booking_ref']}: {$decline_note}");
        set_flash('warning', "Reschedule request declined. Original booking slot retained.");
        redirect(BASE_URL . "admin/booking-detail.php?id={$booking['id']}");
    }
}

// Handle Early Checkout & Free Console Immediately
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_early_checkout'])) {
    if (!verify_csrf()) {
        set_flash('danger', 'Invalid security token.');
    } else {
        $checkout_note = trim($_POST['early_checkout_note'] ?? 'Player finished session early. Station freed for walk-ins.');
        $res = release_station_early((int)$booking['id'], $checkout_note, (int)$admin_user['id']);
        if ($res['success']) {
            set_flash('success', $res['message']);
        } else {
            set_flash('danger', $res['message']);
        }
        redirect(BASE_URL . "admin/booking-detail.php?id={$booking['id']}");
    }
}

// Handle Direct Owner Timing / Station Adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_owner_change_slot'])) {
    if (!verify_csrf()) {
        set_flash('danger', 'Invalid security token.');
    } else {
        $new_station = (int)($_POST['owner_station_id'] ?? $booking['station_id']);
        $new_date = trim($_POST['owner_date'] ?? '');
        $new_time = trim($_POST['owner_time'] ?? '');
        $new_dur = (float)($_POST['owner_duration'] ?? 1);
        if ($new_dur < 0.5) $new_dur = 0.5;

        if (empty($new_date) || empty($new_time)) {
            set_flash('danger', 'Please enter valid date and start time.');
        } else {
            if (strlen($new_time) === 5) $new_time .= ':00';

            if (!is_slot_available($new_station, $new_date, $new_time, $new_dur, (int)$booking['id'])) {
                set_flash('danger', 'Collision error: The selected station is already booked at that timing. Please pick another station or time slot.');
            } else {
                // Fetch rate of chosen station
                $stmt_s = $db->prepare("SELECT hourly_rate, name FROM stations WHERE id = :id");
                $stmt_s->execute([':id' => $new_station]);
                $st_info = $stmt_s->fetch();
                $rate = (float)$st_info['hourly_rate'];

                $new_etime = calculate_end_time($new_time, $new_dur);
                $new_tot = $rate * $new_dur;
                $new_fin = max(0, $new_tot - (float)$booking['discount_amount']);

                $upd_slot = $db->prepare("
                    UPDATE bookings 
                    SET station_id = :sid,
                        booking_date = :bdate,
                        start_time = :stime,
                        end_time = :etime,
                        duration_hours = :dur,
                        total_amount = :tot,
                        final_amount = :fin,
                        reschedule_status = IF(reschedule_status = 'pending', 'approved', reschedule_status),
                        whatsapp_status = 'pending',
                        whatsapp_pending_reason = 'Timing / Station Reassigned'
                    WHERE id = :id
                ");
                $upd_slot->execute([
                    ':sid'   => $new_station,
                    ':bdate' => $new_date,
                    ':stime' => $new_time,
                    ':etime' => $new_etime,
                    ':dur'   => $new_dur,
                    ':tot'   => $new_tot,
                    ':fin'   => $new_fin,
                    ':id'    => $booking['id']
                ]);

                if ($payment) {
                    $db->prepare("UPDATE payments SET amount = :amt WHERE id = :pid")->execute([':amt' => $new_fin, ':pid' => $payment['id']]);
                }

                $_SESSION['flash_wa_url'] = BASE_URL . "admin/whatsapp-send.php?id={$booking['id']}&type=rescheduled";

                log_admin_activity($admin_user['id'], 'owner_changed_slot', "Directly updated booking #{$booking['booking_ref']} to station {$new_station}, {$new_date} {$new_time} ({$new_dur} hrs)");
                set_flash('success', "Booking schedule and station updated successfully to {$new_date} at " . date('g:i A', strtotime($new_time)) . "!");
                redirect(BASE_URL . "admin/booking-detail.php?id={$booking['id']}");
            }
        }
    }
}

// Handle General Status & Payment Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_booking'])) {
    if (!verify_csrf()) {
        set_flash('danger', 'Invalid CSRF token.');
    } else {
        $new_status = trim($_POST['status'] ?? '');
        $new_pay_status = trim($_POST['payment_status'] ?? '');
        $admin_notes = trim($_POST['notes'] ?? '');
        $utr = trim($_POST['upi_transaction_id'] ?? '');

        $allowed_statuses = ['pending_payment', 'confirmed', 'in_session', 'completed', 'cancelled', 'rejected'];
        $allowed_pay_statuses = ['pending', 'verified', 'rejected'];

        if (in_array($new_status, $allowed_statuses)) {
            $wa_pending_reason = 'Booking Status Updated';
            $flash_type = null;

            // Update Payment if exists
            if ($payment && in_array($new_pay_status, $allowed_pay_statuses)) {
                $verified_clause = ($new_pay_status === 'verified') ? ", verified_at = NOW()" : "";
                $upd_p = $db->prepare("
                    UPDATE payments 
                    SET status = :pst, 
                        upi_transaction_id = COALESCE(:utr, upi_transaction_id),
                        admin_notes = :anotes,
                        verified_by = :aid
                        {$verified_clause}
                    WHERE id = :pid
                ");
                $upd_p->execute([
                    ':pst'    => $new_pay_status,
                    ':utr'    => $utr ?: null,
                    ':anotes' => $admin_notes ?: null,
                    ':aid'    => $admin_user['id'],
                    ':pid'    => $payment['id']
                ]);

                // If payment rejected or verified, set reason and tracked flash link
                if ($new_pay_status === 'rejected') {
                    $wa_pending_reason = 'Payment Rejection Notice';
                    $flash_type = 'payment_rejected';
                } elseif ($new_pay_status === 'verified') {
                    $wa_pending_reason = 'Payment Verified & Confirmed Pass';
                    $flash_type = 'confirmed';
                }
            }

            if ($flash_type) {
                $_SESSION['flash_wa_url'] = BASE_URL . "admin/whatsapp-send.php?id={$booking['id']}&type={$flash_type}";
            }

            $upd_b = $db->prepare("
                UPDATE bookings 
                SET status = :st, 
                    notes = :n,
                    whatsapp_status = 'pending',
                    whatsapp_pending_reason = :wa_reason
                WHERE id = :id
            ");
            $upd_b->execute([
                ':st'        => $new_status, 
                ':n'         => $admin_notes ?: null, 
                ':wa_reason' => $wa_pending_reason,
                ':id'        => $booking['id']
            ]);

            log_admin_activity($admin_user['id'], 'update_booking', "Updated booking #{$booking['booking_ref']} to {$new_status} / payment {$new_pay_status}");
            set_flash('success', "Booking #{$booking['booking_ref']} successfully updated.");
            redirect(BASE_URL . "admin/booking-detail.php?id={$booking['id']}");
        }
    }
}

// Tracked WhatsApp Dispatch Endpoints (auto-updates send count & status in DB)
$wa_confirm_url = BASE_URL . "admin/whatsapp-send.php?id=" . $booking['id'] . "&type=confirmed";
$wa_reschedule_url = BASE_URL . "admin/whatsapp-send.php?id=" . $booking['id'] . "&type=rescheduled";
$wa_station_url = BASE_URL . "admin/whatsapp-send.php?id=" . $booking['id'] . "&type=station_changed";
$wa_reject_url = BASE_URL . "admin/whatsapp-send.php?id=" . $booking['id'] . "&type=payment_rejected";
$wa_reminder_url = BASE_URL . "admin/whatsapp-send.php?id=" . $booking['id'] . "&type=reminder";

$flash_wa = $_SESSION['flash_wa_url'] ?? null;
unset($_SESSION['flash_wa_url']);

$admin_title = 'Booking Details';
require_once __DIR__ . '/includes/admin-header.php';
?>

<!-- 1. Instant WhatsApp Post-Action Banner -->
<?php if ($flash_wa || isset($_GET['new'])): 
  $send_url = $flash_wa ?: $wa_confirm_url;
?>
  <div class="alert alert-success d-flex flex-wrap justify-content-between align-items-center gap-3 p-3 mb-4 shadow-sm" style="background: rgba(57, 255, 136, 0.12); border: 1.5px solid var(--rgb-green); color: #fff;">
    <div>
      <h5 class="alert-heading fw-bold mb-1 text-green">
        <i class="bi bi-whatsapp fs-4 me-2"></i>Send Booking Confirmation & Digital Entry Pass to Gamer
      </h5>
      <span class="small text-light">
        Booking #<strong><?= e($booking['booking_ref']) ?></strong> is saved. Send digital entry pass and e-receipt to <strong>+91 <?= e($booking['customer_phone']) ?></strong>.
      </span>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= $send_url ?>" target="_blank" rel="noopener noreferrer" class="btn btn-success fw-bold px-3 py-2">
        <i class="bi bi-whatsapp me-1"></i> Send on WhatsApp Now
      </a>
      <a href="<?= BASE_URL ?>receipt.php?ref=<?= urlencode($booking['booking_ref']) ?>" target="_blank" class="btn btn-warning fw-bold text-dark px-3 py-2">
        <i class="bi bi-printer-fill me-1"></i> Print 1-Page e-Receipt
      </a>
      <a href="<?= BASE_URL ?>check-booking.php?ref=<?= urlencode($booking['booking_ref']) ?>&phone=<?= urlencode($booking['customer_phone']) ?>" target="_blank" class="btn btn-outline-light btn-sm px-3 py-2">
        <i class="bi bi-receipt me-1"></i> Customer View
      </a>
    </div>
  </div>
<?php endif; ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div class="d-flex align-items-center gap-3">
    <a href="<?= BASE_URL ?>admin/bookings.php" class="btn btn-iron btn-iron-outline btn-iron-sm">
      <i class="bi bi-arrow-left me-1"></i> Back to Bookings
    </a>
    <h3 class="h4 text-light mb-0 brand-font">Reservation #<?= e($booking['booking_ref']) ?></h3>
  </div>

  <div class="d-flex align-items-center gap-2">
    <a href="<?= BASE_URL ?>receipt.php?ref=<?= urlencode($booking['booking_ref']) ?>&download=pdf" target="_blank" class="btn btn-iron btn-iron-primary btn-iron-sm">
      <i class="bi bi-file-earmark-pdf-fill me-1"></i> Download PDF Pass
    </a>
    <a href="<?= BASE_URL ?>receipt.php?ref=<?= urlencode($booking['booking_ref']) ?>" target="_blank" class="btn btn-iron btn-iron-cyan btn-iron-sm">
      <i class="bi bi-receipt-cutoff me-1"></i> Print 1-Page Receipt
    </a>
    <?php if ($booking['reschedule_status'] === 'pending'): ?>
      <span class="badge bg-warning text-dark px-3 py-2 fw-bold"><i class="bi bi-clock-history me-1"></i> Change Requested</span>
    <?php endif; ?>
    <?= booking_status_badge($booking['status']) ?>
  </div>
</div>

<!-- Customer Reschedule / Station Change Request Action Alert -->
<?php if ($booking['reschedule_status'] === 'pending'): ?>
  <div class="iron-card p-4 mb-4 border border-warning" style="background: rgba(214,168,75,0.08);">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
      <div>
        <h5 class="text-gold brand-font mb-1">
          <i class="bi bi-exclamation-diamond-fill me-2"></i>Customer Requested Timing / Station Change
        </h5>
        <div class="text-light small mb-1">
          Requested Slot: <strong class="text-gold"><?= date('l, d M Y', strtotime($booking['reschedule_requested_date'])) ?></strong> at 
          <strong class="text-cyan"><?= date('g:i A', strtotime($booking['reschedule_requested_time'])) ?></strong> 
          (<?= (int)$booking['reschedule_requested_duration'] ?> hour(s))
        </div>
        <?php if (!empty($booking['requested_station_name'])): ?>
          <div class="text-cyan small mb-1">
            <i class="bi bi-arrow-left-right me-1"></i>Requested Station: <strong><?= e($booking['requested_station_name']) ?></strong> (Current: <?= e($booking['station_name']) ?>)
          </div>
        <?php endif; ?>
        <?php if (!empty($booking['reschedule_reason'])): ?>
          <div class="text-muted-iron small">Player's Note: <em>"<?= e($booking['reschedule_reason']) ?>"</em></div>
        <?php endif; ?>
      </div>

      <div class="d-flex gap-2">
        <form method="POST" action="<?= BASE_URL ?>admin/booking-detail.php?id=<?= $booking['id'] ?>" onsubmit="return confirm('Approve customer request and update slot / station?');">
          <?= csrf_field() ?>
          <input type="hidden" name="action_approve_reschedule" value="1">
          <button type="submit" class="btn btn-iron btn-iron-primary btn-iron-sm">
            <i class="bi bi-check-circle-fill me-1"></i> Approve & Reassign
          </button>
        </form>

        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#declineRescheduleModal">
          <i class="bi bi-x-circle me-1"></i> Decline
        </button>
      </div>
    </div>
  </div>

  <!-- Decline Modal -->
  <div class="modal fade" id="declineRescheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="background: #151922; border: 1px solid var(--border-color); color: var(--text-main);">
        <form method="POST" action="<?= BASE_URL ?>admin/booking-detail.php?id=<?= $booking['id'] ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="action_reject_reschedule" value="1">
          <div class="modal-header border-secondary border-opacity-25">
            <h5 class="modal-title text-danger brand-font">Decline Reschedule Request</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <label class="form-label">Reason for Declining (Visible to Customer)</label>
            <textarea name="decline_reason" class="form-control" rows="3" required placeholder="e.g. Station is reserved for tournament / operating hours closed..."></textarea>
          </div>
          <div class="modal-footer border-secondary border-opacity-25">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger btn-sm">Confirm Decline</button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php endif; ?>

<div class="row g-4">
  
  <!-- Left Column: Booking & Customer Details + WhatsApp Hub -->
  <div class="col-lg-7">
    
    <!-- Player & Station Info -->
    <div class="iron-card p-4 mb-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="h5 text-gold brand-font mb-0">Player & Session Information</h4>
        <button type="button" class="btn btn-iron btn-iron-outline btn-iron-sm" data-bs-toggle="modal" data-bs-target="#ownerChangeSlotModal">
          <i class="bi bi-calendar2-range me-1 text-gold"></i> Change Timing / Station
        </button>
      </div>
      
      <div class="row g-3 mb-4">
        <div class="col-sm-6">
          <span class="text-muted-iron small d-block">Player Name:</span>
          <strong class="text-light fs-6"><?= e($booking['customer_name']) ?></strong>
        </div>
        <div class="col-sm-6">
          <span class="text-muted-iron small d-block">Mobile Phone:</span>
          <a href="tel:<?= e($booking['customer_phone']) ?>" class="text-gold fw-bold text-decoration-none">
            <i class="bi bi-telephone-fill me-1"></i>+91 <?= e($booking['customer_phone']) ?>
          </a>
        </div>
        <div class="col-sm-6">
          <span class="text-muted-iron small d-block">Email Address:</span>
          <span class="text-light"><?= e($booking['customer_email'] ?: 'Not provided') ?></span>
        </div>
        <div class="col-sm-6">
          <span class="text-muted-iron small d-block">Booking Channel:</span>
          <span class="badge bg-secondary text-uppercase"><?= e($booking['booking_type']) ?></span>
        </div>
      </div>

      <!-- Station Allocation Card -->
      <div class="p-3 rounded mb-4" style="background: rgba(0,0,0,0.3); border: 1px solid var(--border-color);">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="h6 text-cyan mb-0"><i class="bi bi-display me-1"></i> Station Allocation</h5>
          <span class="badge bg-dark border border-secondary text-secondary small"><?= e($booking['category_name']) ?></span>
        </div>
        <div class="row g-2 small">
          <div class="col-6">
            <span class="text-muted-iron">Station:</span> <strong class="text-light"><?= e($booking['station_name']) ?></strong> (<?= e($booking['station_code']) ?>)
          </div>
          <div class="col-6">
            <span class="text-muted-iron">Standard Rate:</span> <span class="text-light"><?= format_currency($booking['hourly_rate']) ?>/hr</span>
          </div>
          <div class="col-6">
            <span class="text-muted-iron">Date:</span> <strong class="text-gold"><?= date('l, d M Y', strtotime($booking['booking_date'])) ?></strong>
          </div>
          <div class="col-6">
            <span class="text-muted-iron">Slot:</span> <strong class="text-green"><?= date('g:i A', strtotime($booking['start_time'])) ?> – <?= date('g:i A', strtotime($booking['end_time'])) ?></strong>
          </div>
          <div class="col-12">
            <span class="text-muted-iron">Duration:</span> <span class="text-light fw-bold"><?= (float)$booking['duration_hours'] < 1 ? round((float)$booking['duration_hours'] * 60) . ' Mins' : (float)$booking['duration_hours'] . ' Hour(s)' ?></span>
          </div>
        </div>
      </div>

      <?php 
      $is_active_session = in_array($booking['status'], ['in_session', 'confirmed']) && $booking['booking_date'] === date('Y-m-d');
      $is_early_completed = ($booking['status'] === 'completed' && strpos($booking['notes'] ?? '', 'Early Checkout') !== false);
      ?>

      <?php if ($is_active_session): ?>
        <!-- Instant Early Checkout Rig Release -->
        <div class="p-3 rounded mb-4 shadow-sm" style="background: rgba(230, 57, 70, 0.08); border: 1.5px solid var(--electric-red);">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-door-open-fill text-danger fs-5"></i>
                <strong class="text-danger">Player Leaving Early? Free This Station Now</strong>
              </div>
              <div class="text-muted-iron small mt-1">
                Currently locked until <span class="text-light fw-bold"><?= date('g:i A', strtotime($booking['end_time'])) ?></span>. Ending session early immediately frees this rig on the Live Radar for walk-ins without wasting time!
              </div>
            </div>
            <button type="button" class="btn btn-danger btn-sm fw-bold px-3 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#earlyCheckoutModal">
              <i class="bi bi-box-arrow-right me-1"></i> Release Rig Now
            </button>
          </div>
        </div>
      <?php elseif ($is_early_completed): ?>
        <div class="p-2 px-3 rounded mb-4 small d-flex align-items-center gap-2" style="background: rgba(0, 229, 255, 0.08); border: 1px solid var(--rgb-cyan); color: var(--rgb-cyan);">
          <i class="bi bi-check2-circle fs-5"></i>
          <div><strong>Early Checkout Completed:</strong> Station was released early at <?= date('g:i A', strtotime($booking['end_time'])) ?> for walk-in players.</div>
        </div>
      <?php endif; ?>

      <!-- Financial Calculation -->
      <div class="p-3 rounded mb-4" style="background: rgba(214,168,75,0.05); border: 1px solid rgba(214,168,75,0.2);">
        <div class="d-flex justify-content-between small text-muted-iron mb-1">
          <span>Gross Rate:</span>
          <span><?= format_currency($booking['total_amount']) ?></span>
        </div>
        <div class="d-flex justify-content-between small text-muted-iron mb-2">
          <span>Discount Applied:</span>
          <span class="text-green">-<?= format_currency($booking['discount_amount']) ?></span>
        </div>
        <div class="pt-2 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
          <span class="fw-bold text-light">Final Payable Amount:</span>
          <span class="fs-4 fw-bold text-gold brand-font"><?= format_currency($booking['final_amount']) ?></span>
        </div>
      </div>

      <!-- 2. WHATSAPP NOTIFICATION & DISPATCH HUB -->
      <div class="p-3 rounded" style="background: rgba(57, 255, 136, 0.05); border: 1.5px solid rgba(57, 255, 136, 0.3);">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
          <h5 class="h6 text-green brand-font mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-whatsapp fs-5"></i> 1-Click WhatsApp Customer Dispatch
          </h5>
          <?php if (($booking['whatsapp_status'] ?? 'pending') === 'pending'): ?>
            <span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-hourglass-split me-1"></i>Remains for WhatsApp</span>
          <?php else: ?>
            <span class="badge bg-success px-2 py-1"><i class="bi bi-check2-all me-1"></i>Whatsapped (<?= (int)$booking['whatsapp_send_count'] ?>x)</span>
          <?php endif; ?>
        </div>

        <!-- WhatsApp Delivery Status Indicator -->
        <?php if (($booking['whatsapp_status'] ?? 'pending') === 'pending'): ?>
          <div class="alert alert-warning py-2 px-3 small mb-3 text-dark d-flex align-items-center gap-2" style="background: rgba(214, 168, 75, 0.2); border-color: var(--gold); color: #fff;">
            <i class="bi bi-exclamation-triangle-fill text-gold fs-5"></i>
            <div>
              <strong class="text-gold">Action Remaining:</strong> <?= e($booking['whatsapp_pending_reason'] ?: 'Booking confirmation & pass needs to be sent to gamer.') ?>
            </div>
          </div>
        <?php else: ?>
          <div class="p-2 px-3 rounded small mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2" style="background: rgba(57,255,136,0.1); border: 1px solid rgba(57,255,136,0.3); color: #c4fcd9;">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-check-circle-fill text-green fs-5"></i>
              <div>
                <strong>Sent on WhatsApp:</strong> <?= e(ucwords(str_replace('_', ' ', $booking['whatsapp_last_type'] ?? 'pass'))) ?> on 
                <?= !empty($booking['whatsapp_sent_at']) ? date('d M Y, g:i A', strtotime($booking['whatsapp_sent_at'])) : 'Recently' ?>
              </div>
            </div>
            <span class="badge bg-dark border border-success text-green">Total Sent: <?= (int)$booking['whatsapp_send_count'] ?>x</span>
          </div>
        <?php endif; ?>

        <p class="text-muted-iron small mb-3">
          Clicking any dispatch button automatically logs the message timestamp, increments your sent count, and opens WhatsApp Web/App:
        </p>
        
        <div class="d-flex flex-wrap gap-2">
          <a href="<?= $wa_confirm_url ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-success fw-bold">
            <i class="bi bi-whatsapp me-1"></i> <?= ($booking['whatsapp_status'] ?? 'pending') === 'sent' ? 'WhatsApp Again (Pass & Receipt)' : 'Send Booking Pass & e-Receipt' ?>
          </a>
          <a href="<?= $wa_reschedule_url ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-info">
            <i class="bi bi-arrow-repeat me-1"></i> <?= ($booking['whatsapp_status'] ?? 'pending') === 'sent' ? 'WhatsApp Again (Timing Update)' : 'Send Timing Update' ?>
          </a>
          <a href="<?= $wa_reject_url ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-exclamation-octagon me-1"></i> Send Payment Issue
          </a>
          <a href="<?= $wa_reminder_url ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-light">
            <i class="bi bi-bell me-1"></i> Send Session Reminder
          </a>
          <a href="<?= BASE_URL ?>receipt.php?ref=<?= urlencode($booking['booking_ref']) ?>&download=pdf" target="_blank" class="btn btn-sm btn-danger fw-bold shadow-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i> Download PDF Pass
          </a>
          <a href="<?= BASE_URL ?>receipt.php?ref=<?= urlencode($booking['booking_ref']) ?>" target="_blank" class="btn btn-sm btn-iron-cyan">
            <i class="bi bi-receipt-cutoff me-1"></i> 1-Page Pass
          </a>
          <a href="<?= BASE_URL ?>check-booking.php?ref=<?= urlencode($booking['booking_ref']) ?>&phone=<?= urlencode($booking['customer_phone']) ?>" target="_blank" class="btn btn-sm btn-iron-outline">
            <i class="bi bi-box-arrow-up-right me-1"></i> Customer View
          </a>
        </div>
      </div>

    </div>
  </div>

  <!-- Right Column: Status Control & Payment Verification -->
  <div class="col-lg-5">
    <div class="iron-card p-4">
      <h4 class="h5 text-gold brand-font mb-3">Admin Action Panel</h4>

      <form method="POST" action="<?= BASE_URL ?>admin/booking-detail.php?id=<?= $booking['id'] ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="update_booking" value="1">

        <div class="mb-3">
          <label for="bStatus" class="form-label">Booking Status</label>
          <select name="status" id="bStatus" class="form-select">
            <option value="pending_payment" <?= $booking['status'] === 'pending_payment' ? 'selected' : '' ?>>Pending Payment</option>
            <option value="confirmed" <?= $booking['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
            <option value="in_session" <?= $booking['status'] === 'in_session' ? 'selected' : '' ?>>In Session</option>
            <option value="completed" <?= $booking['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
            <option value="cancelled" <?= $booking['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            <option value="rejected" <?= $booking['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
          </select>
        </div>

        <?php if ($payment): ?>
          <div class="mb-3">
            <label for="payStatus" class="form-label">Payment Verification Status</label>
            <select name="payment_status" id="payStatus" class="form-select">
              <option value="pending" <?= $payment['status'] === 'pending' ? 'selected' : '' ?>>Pending Verification</option>
              <option value="verified" <?= $payment['status'] === 'verified' ? 'selected' : '' ?>>Verified & Approved</option>
              <option value="rejected" <?= $payment['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="utrInput" class="form-label">Submitted UPI Transaction ID / UTR</label>
            <input type="text" name="upi_transaction_id" id="utrInput" class="form-control font-monospace" value="<?= e($payment['upi_transaction_id'] ?? '') ?>" placeholder="e.g. 431289012345">
          </div>

          <?php if (!empty($payment['proof_file'])): ?>
            <div class="mb-3">
              <label class="form-label d-block">Submitted Payment Proof Screenshot</label>
              <a href="<?= BASE_URL . e($payment['proof_file']) ?>" target="_blank" class="d-inline-block border border-secondary border-opacity-50 rounded overflow-hidden">
                <img src="<?= BASE_URL . e($payment['proof_file']) ?>" alt="Proof" style="max-height: 120px; width: auto;" class="img-fluid">
              </a>
              <div class="text-muted-iron small mt-1"><i class="bi bi-box-arrow-up-right me-1"></i> Click to enlarge screenshot in new tab</div>
            </div>
          <?php endif; ?>
        <?php endif; ?>

        <div class="mb-4">
          <label for="adminNotes" class="form-label">Internal Admin / Counter Notes</label>
          <textarea name="notes" id="adminNotes" rows="3" class="form-control" placeholder="Add notes (e.g. Cash received at counter, player requested specific game...)"><?= e($booking['notes'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn-iron btn-iron-primary w-100 py-3">
          <i class="bi bi-save me-1"></i> Save Changes & Update Status
        </button>
      </form>

    </div>
  </div>
</div>

<!-- Modal: Owner Directly Changes Booking Timing / Station -->
<div class="modal fade" id="ownerChangeSlotModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background: #151922; border: 1px solid var(--border-color); color: var(--text-main);">
      <form method="POST" action="<?= BASE_URL ?>admin/booking-detail.php?id=<?= $booking['id'] ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action_owner_change_slot" value="1">

        <div class="modal-header border-secondary border-opacity-25">
          <h5 class="modal-title text-gold brand-font"><i class="bi bi-pencil-square me-2"></i>Change Booking Slot & Station</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Reassign Gaming Station</label>
              <select name="owner_station_id" class="form-select">
                <?php foreach ($all_stations as $st): ?>
                  <option value="<?= $st['id'] ?>" <?= (int)$booking['station_id'] === (int)$st['id'] ? 'selected' : '' ?>>
                    <?= e($st['name']) ?> (<?= e($st['category_name']) ?>) — ₹<?= number_format((float)$st['hourly_rate'], 0) ?>/hr
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Date <span class="text-danger">*</span></label>
              <input type="date" name="owner_date" class="form-control" value="<?= e($booking['booking_date']) ?>" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Start Time <span class="text-danger">*</span></label>
              <input type="time" name="owner_time" class="form-control" value="<?= substr($booking['start_time'], 0, 5) ?>" required>
            </div>

            <div class="col-12">
              <label class="form-label">Duration</label>
              <select name="owner_duration" class="form-select">
                <?php 
                $dur_opts = [
                    '0.5' => '30 Minutes (Quick Match)',
                    '1.0' => '1 Hour (Standard)',
                    '1.5' => '1.5 Hours (Extended Match)',
                    '2.0' => '2 Hours (Championship Battle)',
                    '2.5' => '2.5 Hours (Power Session)',
                    '3.0' => '3 Hours (Squad Raid)',
                    '4.0' => '4 Hours (Marathon Play)',
                    '5.0' => '5 Hours (Champion All-Day)',
                    '6.0' => '6 Hours',
                    '8.0' => '8 Hours'
                ];
                foreach ($dur_opts as $d_val => $d_lbl):
                ?>
                  <option value="<?= $d_val ?>" <?= (abs((float)$booking['duration_hours'] - (float)$d_val) < 0.01) ? 'selected' : '' ?>>
                    <?= $d_lbl ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <div class="modal-footer border-secondary border-opacity-25">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-iron btn-iron-primary btn-iron-sm">Update Slot & Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Early Checkout Rig Release -->
<div class="modal fade" id="earlyCheckoutModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background: #151922; border: 1.5px solid var(--electric-red); color: var(--text-main);">
      <form method="POST" action="<?= BASE_URL ?>admin/booking-detail.php?id=<?= $booking['id'] ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action_early_checkout" value="1">
        <div class="modal-header border-secondary border-opacity-25">
          <h5 class="modal-title text-danger brand-font"><i class="bi bi-door-open-fill me-2"></i>Early Checkout & Free Station</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="text-light mb-3">
            Are you sure you want to release station <strong class="text-cyan"><?= e($booking['station_name']) ?></strong> for player <strong class="text-gold"><?= e($booking['customer_name']) ?></strong> right now?
          </p>
          <div class="p-3 rounded mb-3" style="background: rgba(0,0,0,0.3); border: 1px solid var(--border-color);">
            <div class="small text-muted-iron mb-1">Station: <strong class="text-light"><?= e($booking['station_name']) ?> (<?= e($booking['station_code']) ?>)</strong></div>
            <div class="small text-muted-iron mb-1">Scheduled End: <strong class="text-light"><?= date('g:i A', strtotime($booking['end_time'])) ?></strong></div>
            <div class="small text-muted-iron">New Released End: <strong class="text-green"><?= date('g:i A') ?> (Free for walk-ins now)</strong></div>
          </div>
          <label class="form-label small text-muted-iron">Early Release Note</label>
          <input type="text" name="early_checkout_note" class="form-control form-control-sm" value="Player finished session early. Rig released for walk-in players.">
        </div>
        <div class="modal-footer border-secondary border-opacity-25">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger btn-sm fw-bold"><i class="bi bi-box-arrow-right me-1"></i> Release Rig Immediately</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
