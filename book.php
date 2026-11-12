<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Interactive Booking Engine - Dynamically White-Labeled
 */

require_once __DIR__ . '/config/config.php';

$brand_name = get_brand_name();
$brand_city = get_brand_city();
$page_title = 'Book Your Gaming Session | ' . $brand_name;
$page_desc = 'Reserve your PC rig, console lounge, or squad station online at ' . $brand_name . ' in ' . $brand_city . '. Live availability and instant booking.';

require_once __DIR__ . '/includes/header.php';

$db = get_db();
$errors = [];

// Selected station or duration pre-fill from query string
$preset_station_id = isset($_GET['station_id']) ? (int)$_GET['station_id'] : 0;
$preset_duration = isset($_GET['duration']) ? (float)$_GET['duration'] : 2.0;
if ($preset_duration < 0.5 || $preset_duration > 12.0) {
    $preset_duration = 2.0;
}

// Fetch all active stations grouped with their categories
$stations_stmt = $db->query("
    SELECT s.*, c.name AS category_name 
    FROM stations s 
    JOIN station_categories c ON s.category_id = c.id 
    WHERE s.status = 'active' 
    ORDER BY c.display_order ASC, s.display_order ASC
");
$active_stations = $stations_stmt->fetchAll();

// Default to first station if not specified
if ($preset_station_id <= 0 && !empty($active_stations)) {
    $preset_station_id = (int)$active_stations[0]['id'];
}

// If after 22:00 (10 PM), arena is about to close at 23:00, default date to tomorrow
$default_booking_date = (date('H') >= 22) ? date('Y-m-d', strtotime('+1 day')) : date('Y-m-d');

// Handle Booking Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF
    if (!verify_csrf()) {
        $errors[] = 'Security token expired or invalid. Please refresh the page and submit again.';
    }

    $station_id     = (int)($_POST['station_id'] ?? 0);
    $booking_date   = trim($_POST['booking_date'] ?? '');
    $start_time     = trim($_POST['start_time'] ?? '');
    $duration_hours = (float)($_POST['duration_hours'] ?? 1.0);
    $full_name      = to_title_case($_POST['full_name'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $notes          = trim($_POST['notes'] ?? '');
    $promo_code     = strtoupper(trim($_POST['promo_code'] ?? ''));

    // 2. Validate Inputs
    if ($station_id <= 0) {
        $errors[] = 'Please select a valid gaming station.';
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $booking_date)) {
        $errors[] = 'Please select a valid booking date.';
    } elseif ($booking_date < date('Y-m-d')) {
        $errors[] = 'Booking date cannot be in the past.';
    }

    if (empty($start_time) || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $start_time)) {
        $errors[] = 'Please select an available start time slot.';
    } else {
        if (strlen($start_time) === 5) {
            $start_time .= ':00';
        }
    }

    if ($duration_hours < 0.5 || $duration_hours > 12.0) {
        $errors[] = 'Session duration must be between 30 minutes (0.5 hr) and 12 hours.';
    }

    if (empty($full_name) || mb_strlen($full_name) < 2) {
        $errors[] = 'Please enter your full name (minimum 2 characters).';
    }

    // Strict Phone validation (Indian 10-digit format standard: starts with 6, 7, 8, or 9)
    $clean_phone = clean_indian_phone($phone);
    if (!validate_indian_phone($phone)) {
        $errors[] = 'Please provide a valid 10-digit Indian mobile number (e.g. 9812345678) starting with 6, 7, 8, or 9.';
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'The provided email address is invalid. Please enter a valid email address.';
    }

    // Check same-day past slot restriction
    if ($booking_date === date('Y-m-d')) {
        $slot_timestamp = strtotime($booking_date . ' ' . $start_time);
        if ($slot_timestamp < (time() + 300)) { // 5-minute buffer
            $errors[] = 'The selected start time has already passed. Please select a future time slot.';
        }
    }

    // 3. Process Booking if no validation errors
    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Fetch station details with row lock
            $stmt_st = $db->prepare("SELECT * FROM stations WHERE id = :id FOR UPDATE");
            $stmt_st->execute([':id' => $station_id]);
            $station = $stmt_st->fetch();

            if (!$station || $station['status'] !== 'active') {
                $db->rollBack();
                $errors[] = 'The selected station is currently unavailable or under maintenance.';
            } else {
                // Server-side atomic slot conflict check
                if (!is_slot_available($station_id, $booking_date, $start_time, $duration_hours)) {
                    $db->rollBack();
                    $errors[] = 'The selected time slot is either already booked or exceeds sanctuary operating hours (closes at 11:00 PM). Please choose another time or station.';
                } else {
                    $end_time = calculate_end_time($start_time, $duration_hours);
                    $hourly_rate = (float)$station['hourly_rate'];
                    $total_amount = $hourly_rate * $duration_hours;
                    $discount_amount = 0.00;

                    // Apply promo code if entered
                    if (!empty($promo_code)) {
                        $stmt_promo = $db->prepare("
                            SELECT * FROM offers 
                            WHERE code = :code 
                              AND is_active = 1 
                              AND valid_from <= :today 
                              AND valid_until >= :today2
                            LIMIT 1
                        ");
                        $stmt_promo->execute([':code' => $promo_code, ':today' => $booking_date, ':today2' => $booking_date]);
                        $promo = $stmt_promo->fetch();

                        if ($promo && $duration_hours >= $promo['min_booking_hours']) {
                            if ($promo['discount_type'] === 'percentage') {
                                $discount_amount = ($total_amount * (float)$promo['discount_value']) / 100.0;
                            } else {
                                $discount_amount = min($total_amount, (float)$promo['discount_value']);
                            }
                        }
                    }

                    $final_amount = max(0.00, $total_amount - $discount_amount);

                    // Find or create customer
                    $stmt_cust = $db->prepare("SELECT id FROM customers WHERE phone = :phone LIMIT 1");
                    $stmt_cust->execute([':phone' => $clean_phone]);
                    $customer_id = $stmt_cust->fetchColumn();

                    if (!$customer_id) {
                        $stmt_new_cust = $db->prepare("
                            INSERT INTO customers (full_name, phone, email, total_bookings, total_spent) 
                            VALUES (:name, :phone, :email, 1, :spent)
                        ");
                        $stmt_new_cust->execute([
                            ':name'  => $full_name,
                            ':phone' => $clean_phone,
                            ':email' => $email ?: null,
                            ':spent' => $final_amount
                        ]);
                        $customer_id = (int)$db->lastInsertId();
                    } else {
                        $stmt_upd_cust = $db->prepare("
                            UPDATE customers 
                            SET full_name = :name, 
                                email = COALESCE(:email, email),
                                total_bookings = total_bookings + 1,
                                total_spent = total_spent + :spent 
                            WHERE id = :id
                        ");
                        $stmt_upd_cust->execute([
                            ':name'  => $full_name,
                            ':email' => $email ?: null,
                            ':spent' => $final_amount,
                            ':id'    => $customer_id
                        ]);
                    }

                    // Create booking
                    $booking_ref = generate_booking_ref();
                    $stmt_book = $db->prepare("
                        INSERT INTO bookings (
                            booking_ref, customer_id, station_id, booking_date, 
                            start_time, end_time, duration_hours, total_amount, 
                            discount_amount, final_amount, status, booking_type, notes,
                            whatsapp_status, whatsapp_pending_reason
                        ) VALUES (
                            :ref, :cid, :sid, :bdate, 
                            :stime, :etime, :dur, :tot, 
                            :disc, :fin, 'pending_payment', 'online', :notes,
                            'pending', 'Online Reservation - Pass Needs WhatsApp'
                        )
                    ");
                    $stmt_book->execute([
                        ':ref'   => $booking_ref,
                        ':cid'   => $customer_id,
                        ':sid'   => $station_id,
                        ':bdate' => $booking_date,
                        ':stime' => $start_time,
                        ':etime' => $end_time,
                        ':dur'   => $duration_hours,
                        ':tot'   => $total_amount,
                        ':disc'  => $discount_amount,
                        ':fin'   => $final_amount,
                        ':notes' => $notes ?: null
                    ]);
                    $booking_id = (int)$db->lastInsertId();

                    // Create pending payment record
                    $initial_pay_method = is_razorpay_enabled() ? 'razorpay' : 'upi_qr';
                    $stmt_pay = $db->prepare("
                        INSERT INTO payments (booking_id, amount, payment_method, status) 
                        VALUES (:bid, :amt, :method, 'pending')
                    ");
                    $stmt_pay->execute([
                        ':bid'    => $booking_id,
                        ':amt'    => $final_amount,
                        ':method' => $initial_pay_method
                    ]);

                    $db->commit();

                    // Set success flash and redirect to booking payment & status page
                    if (is_razorpay_enabled()) {
                        set_flash('success', "Booking initiated! Your reference is {$booking_ref}. Complete your Razorpay payment below for instant pass confirmation.");
                        redirect(BASE_URL . 'check-booking.php?ref=' . urlencode($booking_ref) . '&phone=' . urlencode($clean_phone) . '&pay=razorpay');
                    } else {
                        set_flash('success', "Booking initiated! Your reference is {$booking_ref}. Please scan the UPI QR code below and submit your 12-digit transaction number to confirm your reservation.");
                        redirect(BASE_URL . 'check-booking.php?ref=' . urlencode($booking_ref) . '&phone=' . urlencode($clean_phone));
                    }
                }
            }
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Booking Engine Error: ' . $e->getMessage());
            $errors[] = 'An unexpected server error occurred while booking your session. Please retry or contact the sanctuary.';
        }
    }
}
?>

<!-- Page Banner -->
<section class="py-5 position-relative overflow-hidden" style="background: radial-gradient(circle at 50% 20%, rgba(34, 43, 61, 0.6) 0%, var(--bg-primary) 80%); border-bottom: 1px solid var(--border-color);">
  <div class="container py-4 text-center position-relative z-2">
    <span class="section-subtitle">Real-Time Booking Engine</span>
    <h1 class="display-5 fw-bold mt-2">Book Your Gaming Session</h1>
    <p class="text-muted-iron mx-auto" style="max-width: 650px;">
      Reserve your preferred PC rig or console lounge in <?= e($brand_city) ?>. Instant slot verification and transparent pricing.
    </p>
  </div>
</section>

<!-- Booking Engine Container -->
<section class="py-5">
  <div class="container py-2">
    
    <!-- Flash Messages & Errors -->
    <?= display_flash() ?>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger shadow-sm mb-4" role="alert">
        <h5 class="alert-heading fs-6 fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please resolve the following issues:</h5>
        <ul class="mb-0 small ps-3">
          <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>book.php" id="ironcladBookingForm" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="final_amount" id="hiddenFinalAmount" value="200.00">

      <div class="row g-4">
        
        <!-- Left Column: Station & Slot Selection -->
        <div class="col-lg-8">
          <div class="iron-card p-4 mb-4">
            <h4 class="text-gold mb-3 brand-font d-flex align-items-center gap-2">
              <span class="badge bg-gold text-dark rounded-circle" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.85rem;">1</span>
              Station & Duration
            </h4>

            <div class="row g-3">
              <!-- Station Select -->
              <div class="col-md-7">
                <label for="bookingStation" class="form-label">Select Gaming Station <span class="text-danger">*</span></label>
                <select name="station_id" id="bookingStation" class="form-select" required>
                  <option value="">-- Choose a Battle Station --</option>
                  <?php foreach ($active_stations as $st): ?>
                    <option value="<?= $st['id'] ?>" data-rate="<?= $st['hourly_rate'] ?>" <?= ($preset_station_id === $st['id']) ? 'selected' : '' ?>>
                      <?= e($st['name']) ?> (<?= e($st['category_name']) ?>) — ₹<?= number_format((float)$st['hourly_rate'], 0) ?>/hr
                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="form-text text-muted-iron small">Choose from high-refresh PC rigs or 4K console lounges.</div>
              </div>

              <!-- Duration Select -->
              <div class="col-md-5">
                <label for="bookingDuration" class="form-label">Session Duration <span class="text-danger">*</span></label>
                <select name="duration_hours" id="bookingDuration" class="form-select" required>
                  <option value="0.5" <?= (abs($preset_duration - 0.5) < 0.01) ? 'selected' : '' ?>>30 Minutes (Quick Match)</option>
                  <option value="1" <?= (abs($preset_duration - 1) < 0.01) ? 'selected' : '' ?>>1 Hour (Standard Session)</option>
                  <option value="1.5" <?= (abs($preset_duration - 1.5) < 0.01) ? 'selected' : '' ?>>1.5 Hours (Extended Match)</option>
                  <option value="2" <?= (abs($preset_duration - 2) < 0.01) ? 'selected' : '' ?>>2 Hours (Championship Battle)</option>
                  <option value="2.5" <?= (abs($preset_duration - 2.5) < 0.01) ? 'selected' : '' ?>>2.5 Hours (Squad Battle)</option>
                  <option value="3" <?= (abs($preset_duration - 3) < 0.01) ? 'selected' : '' ?>>3 Hours (Arena Battle Pass)</option>
                  <option value="4" <?= (abs($preset_duration - 4) < 0.01) ? 'selected' : '' ?>>4 Hours (Marathon Play)</option>
                  <option value="5" <?= (abs($preset_duration - 5) < 0.01) ? 'selected' : '' ?>>5 Hours (Champion All-Day)</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Step 2: Date & Slot Pick -->
          <div class="iron-card p-4 mb-4">
            <h4 class="text-gold mb-3 brand-font d-flex align-items-center gap-2">
              <span class="badge bg-gold text-dark rounded-circle" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.85rem;">2</span>
              Choose Date & Available Slot
            </h4>

            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <label for="bookingDate" class="form-label">Booking Date <span class="text-danger">*</span></label>
                <input type="date" name="booking_date" id="bookingDate" class="form-control" value="<?= e($default_booking_date) ?>" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+14 days')) ?>" required>
              </div>
              <div class="col-md-6 d-flex align-items-end">
                <div class="text-muted-iron small p-2 rounded w-100" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color);">
                  <i class="bi bi-clock-history text-gold me-1"></i> Open Hours: <strong>10:00 AM – 11:00 PM</strong>
                </div>
              </div>
            </div>

            <label class="form-label d-block">Available Start Times <span class="text-danger">*</span></label>
            <div id="availableSlotsContainer" class="p-2 rounded" style="background: rgba(0,0,0,0.2); min-height: 120px; border: 1px solid var(--border-color);">
              <div class="text-center p-3 text-muted-iron small">
                Select station and date to inspect available time slots.
              </div>
            </div>
          </div>

          <!-- Step 3: Player Details -->
          <div class="iron-card p-4">
            <h4 class="text-gold mb-3 brand-font d-flex align-items-center gap-2">
              <span class="badge bg-gold text-dark rounded-circle" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.85rem;">3</span>
              Player Contact Details
            </h4>

            <div class="row g-3">
              <div class="col-md-6">
                <label for="customerName" class="form-label">Your Full Name <span class="text-danger">*</span></label>
                <input type="text" name="full_name" id="customerName" class="form-control" placeholder="e.g. Aman Sharma" required>
              </div>

              <div class="col-md-6">
                <label for="customerPhone" class="form-label">Mobile Phone Number <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text bg-dark text-muted-iron border-secondary border-opacity-25">+91</span>
                  <input type="tel" name="phone" id="customerPhone" class="form-control" placeholder="10-digit mobile number" maxlength="10" pattern="[6-9][0-9]{9}" required>
                </div>
                <div class="form-text text-muted-iron small">Used for booking status lookups and confirmation SMS/WhatsApp.</div>
              </div>

              <div class="col-md-6">
                <label for="customerEmail" class="form-label">Email Address (Optional)</label>
                <input type="email" name="email" id="customerEmail" class="form-control" placeholder="yourname@gmail.com">
              </div>

              <div class="col-md-6">
                <label for="bookingNotes" class="form-label">Special Requests / Preferred Games (Optional)</label>
                <input type="text" name="notes" id="bookingNotes" class="form-control" placeholder="e.g. Valorant / FIFA 24 pre-installed">
              </div>
            </div>
          </div>

        </div>

        <!-- Right Column: Booking Summary & Payment Preview -->
        <div class="col-lg-4">
          <div class="iron-card p-4 sticky-top" style="top: 100px;">
            <h4 class="text-gold mb-3 brand-font">Session Summary</h4>

            <!-- Promo Code Input -->
            <div class="mb-3">
              <label class="form-label">Got a Promo Code?</label>
              <div class="input-group">
                <input type="text" id="promoCodeInput" name="promo_code" class="form-control text-uppercase font-monospace" placeholder="e.g. IRONCLAD10">
                <button type="button" id="applyPromoBtn" class="btn btn-iron btn-iron-outline btn-iron-sm">Apply</button>
              </div>
              <div id="promoMessage" class="mt-1"></div>
            </div>

            <!-- Price Breakdown Table -->
            <div class="p-3 rounded mb-4" style="background: rgba(0,0,0,0.3); border: 1px solid var(--border-color);">
              <div class="d-flex justify-content-between text-muted-iron small mb-2">
                <span>Standard Station Fee:</span>
                <span id="summaryBasePrice" class="text-light fw-bold">₹200.00</span>
              </div>
              <div class="d-flex justify-content-between text-muted-iron small mb-2">
                <span>Promotional Discount:</span>
                <span id="summaryDiscount" class="text-green fw-bold">-₹0.00</span>
              </div>
              <div class="pt-2 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
                <span class="fw-bold text-light">Total Payable:</span>
                <span id="summaryTotalAmount" class="fs-4 fw-bold text-gold brand-font">₹200.00</span>
              </div>
            </div>

            <!-- Payment Instructions Note -->
            <?php if (is_razorpay_enabled()): ?>
              <div class="p-3 rounded mb-4" style="background: rgba(214,168,75,0.08); border: 1px solid rgba(214,168,75,0.3);">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <h6 class="text-gold small mb-0 fw-bold"><i class="bi bi-lightning-charge-fill me-1"></i> Instant Online Checkout:</h6>
                  <span class="badge bg-gold text-dark" style="font-size: 0.65rem;">Auto-Confirm</span>
                </div>
                <p class="text-muted-iron small mb-0" style="font-size: 0.78rem;">
                  Pay securely with Razorpay (Google Pay, PhonePe, Paytm, Cards, NetBanking). Your session locks instantly with a verified 1-page pass.
                </p>
              </div>
            <?php else: ?>
              <div class="p-3 rounded mb-4" style="background: rgba(214,168,75,0.06); border: 1px solid rgba(214,168,75,0.25);">
                <h6 class="text-gold small mb-1 fw-bold"><i class="bi bi-shield-lock-fill me-1"></i> Two-Step UPI Workflow:</h6>
                <p class="text-muted-iron small mb-0" style="font-size: 0.78rem;">
                  1. Submit this reservation to generate your reference.<br>
                  2. Scan our official UPI QR on the next screen and submit your transaction ID for admin verification.
                </p>
              </div>
            <?php endif; ?>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-iron btn-iron-primary w-100 py-3">
              <i class="bi bi-check-circle-fill fs-5"></i> Confirm & Proceed to Pay
            </button>
            <div class="text-center text-muted-iron small mt-2">
              <i class="bi bi-lock me-1"></i> SSL Protected & Verified Booking
            </div>

          </div>
        </div>

      </div>
    </form>

  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

