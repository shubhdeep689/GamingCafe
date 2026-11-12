<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Counter Walk-In Booking Creator with Visual Slot Verification & Instant WhatsApp Flow
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();

$admin_user = current_admin();
$db = get_db();
$errors = [];

// Fetch active stations
$stations = $db->query("
    SELECT s.*, c.name AS category_name 
    FROM stations s 
    JOIN station_categories c ON s.category_id = c.id 
    WHERE s.status = 'active' 
    ORDER BY s.display_order ASC
")->fetchAll();

// Pre-fills from query params (e.g. from availability timetable clicks)
$preset_station_id = (int)($_GET['station_id'] ?? 0);
if ($preset_station_id <= 0 && !empty($stations)) {
    $preset_station_id = (int)$stations[0]['id'];
}
$preset_date = trim($_GET['booking_date'] ?? $_GET['date'] ?? ((date('H') >= 22) ? date('Y-m-d', strtotime('+1 day')) : date('Y-m-d')));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $preset_date)) {
    $preset_date = date('Y-m-d');
}
$preset_time = trim($_GET['start_time'] ?? date('H:i'));
if (strlen($preset_time) === 5) {
    $preset_time .= ':00';
}
$preset_duration = (int)($_GET['duration_hours'] ?? 2);
if ($preset_duration < 1 || $preset_duration > 12) {
    $preset_duration = 2;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Invalid security token.';
    } else {
        $station_id = (int)($_POST['station_id'] ?? 0);
        $booking_date = trim($_POST['booking_date'] ?? date('Y-m-d'));
        $start_time = trim($_POST['start_time'] ?? '');
        $duration_hours = (float)($_POST['duration_hours'] ?? 1.0);
        if ($duration_hours < 0.5) $duration_hours = 0.5;
        $full_name = to_title_case($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $payment_method = trim($_POST['payment_method'] ?? 'cash');
        $payment_status = trim($_POST['payment_status'] ?? 'verified');
        $notes = trim($_POST['notes'] ?? '');

        // Phone validation (Strict 10-digit Indian mobile standard)
        $clean_phone = clean_indian_phone($phone);

        if ($station_id <= 0) $errors[] = 'Please select a gaming station.';
        if (empty($start_time)) $errors[] = 'Please select or enter a start time.';
        if (strlen($start_time) === 5) $start_time .= ':00';
        if (empty($full_name) || mb_strlen($full_name) < 2) $errors[] = 'Please enter a valid customer name.';
        
        if (!validate_indian_phone($phone)) {
            $errors[] = 'Please enter a valid 10-digit Indian mobile number (e.g. 9876543210).';
        }

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (empty($errors)) {
            try {
                $db->beginTransaction();

                if (!is_slot_available($station_id, $booking_date, $start_time, $duration_hours)) {
                    $db->rollBack();
                    $errors[] = 'Collision / Hours Error: Station is already booked during that time or session extends past sanctuary operating hours (Sanctuary closes at 11:00 PM). Please select a different station, time, or shorter duration.';
                } else {
                    // Fetch station rate
                    $st_stmt = $db->prepare("SELECT * FROM stations WHERE id = :id FOR UPDATE");
                    $st_stmt->execute([':id' => $station_id]);
                    $station = $st_stmt->fetch();

                    $end_time = calculate_end_time($start_time, $duration_hours);
                    $rate = (float)$station['hourly_rate'];
                    $final_amount = $rate * $duration_hours;

                    // Find or create customer
                    $stmt_cust = $db->prepare("SELECT id FROM customers WHERE phone = :p LIMIT 1");
                    $stmt_cust->execute([':p' => $clean_phone]);
                    $customer_id = $stmt_cust->fetchColumn();

                    if (!$customer_id) {
                        $ins_cust = $db->prepare("
                            INSERT INTO customers (full_name, phone, email, total_bookings, total_spent) 
                            VALUES (:n, :p, :e, 1, :s)
                        ");
                        $ins_cust->execute([
                            ':n' => $full_name, 
                            ':p' => $clean_phone, 
                            ':e' => $email ?: null,
                            ':s' => $final_amount
                        ]);
                        $customer_id = (int)$db->lastInsertId();
                    } else {
                        $upd_cust = $db->prepare("
                            UPDATE customers 
                            SET full_name = :n, 
                                email = COALESCE(:e, email),
                                total_bookings = total_bookings + 1, 
                                total_spent = total_spent + :s 
                            WHERE id = :id
                        ");
                        $upd_cust->execute([
                            ':n' => $full_name, 
                            ':e' => $email ?: null,
                            ':s' => $final_amount, 
                            ':id' => $customer_id
                        ]);
                    }

                    $booking_ref = generate_booking_ref();
                    $booking_status = ($payment_status === 'verified') ? 'confirmed' : 'pending_payment';

                    $stmt_b = $db->prepare("
                        INSERT INTO bookings (
                            booking_ref, customer_id, station_id, booking_date, 
                            start_time, end_time, duration_hours, total_amount, 
                            discount_amount, final_amount, status, booking_type, notes,
                            whatsapp_status, whatsapp_pending_reason
                        ) VALUES (
                            :ref, :cid, :sid, :bdate, 
                            :stime, :etime, :dur, :tot, 
                            0.00, :fin, :bst, 'walkin', :notes,
                            'pending', 'New Walk-In Pass Pending'
                        )
                    ");
                    $stmt_b->execute([
                        ':ref'   => $booking_ref,
                        ':cid'   => $customer_id,
                        ':sid'   => $station_id,
                        ':bdate' => $booking_date,
                        ':stime' => $start_time,
                        ':etime' => $end_time,
                        ':dur'   => $duration_hours,
                        ':tot'   => $final_amount,
                        ':fin'   => $final_amount,
                        ':bst'   => $booking_status,
                        ':notes' => $notes ?: 'Walk-in booking counter created'
                    ]);
                    $booking_id = (int)$db->lastInsertId();

                    $verified_at = ($payment_status === 'verified') ? date('Y-m-d H:i:s') : null;
                    $stmt_p = $db->prepare("
                        INSERT INTO payments (booking_id, amount, payment_method, status, verified_by, verified_at)
                        VALUES (:bid, :amt, :pm, :pst, :vby, :vat)
                    ");
                    $stmt_p->execute([
                        ':bid' => $booking_id,
                        ':amt' => $final_amount,
                        ':pm'  => $payment_method,
                        ':pst' => $payment_status,
                        ':vby' => $admin_user['id'],
                        ':vat' => $verified_at
                    ]);

                    $db->commit();
                    log_admin_activity($admin_user['id'], 'walkin_booking_created', "Created walk-in booking #{$booking_ref} for {$full_name}");

                    // Tracked WhatsApp dispatch router link
                    $_SESSION['flash_wa_url'] = BASE_URL . "admin/whatsapp-send.php?id={$booking_id}&type=confirmed";
                    $_SESSION['flash_new_ref'] = $booking_ref;

                    set_flash('success', "Walk-in booking #{$booking_ref} registered! Send confirmation pass to customer's WhatsApp.");
                    redirect(BASE_URL . "admin/booking-detail.php?id={$booking_id}&new=1");
                }
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                error_log('Walkin Error: ' . $e->getMessage());
                $errors[] = 'Failed to create booking: ' . $e->getMessage();
            }
        }
    }
}

$admin_title = 'Create Walk-In Booking';
require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row g-4 justify-content-center">
  <div class="col-xl-9 col-lg-10">
    <div class="iron-card p-4 p-md-5">
      
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary border-opacity-25 gap-2">
        <div>
          <h3 class="h4 text-gold brand-font mb-1">
            <i class="bi bi-controller text-cyan me-2"></i>New Walk-In Reservation
          </h3>
          <span class="text-muted-iron small">Register a gamer arriving directly at the sanctuary reception counter.</span>
        </div>
        <div class="d-flex gap-2">
          <a href="<?= BASE_URL ?>admin/availability.php" class="btn btn-iron btn-iron-cyan btn-iron-sm">
            <i class="bi bi-grid-3x3-gap me-1"></i> View Live Radar
          </a>
          <a href="<?= BASE_URL ?>admin/bookings.php" class="btn btn-iron btn-iron-outline btn-iron-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
          </a>
        </div>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger shadow-sm mb-4">
          <h6 class="alert-heading fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please resolve:</h6>
          <ul class="mb-0 small ps-3">
            <?php foreach ($errors as $err): ?>
              <li><?= e($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" action="<?= BASE_URL ?>admin/booking-create.php" id="walkinForm">
        <?= csrf_field() ?>

        <div class="row g-3 mb-4">
          
          <!-- Station Selection -->
          <div class="col-md-6">
            <label for="stSelect" class="form-label">Gaming Station / Battle Rig <span class="text-danger">*</span></label>
            <select name="station_id" id="stSelect" class="form-select" required>
              <option value="">-- Choose Station --</option>
              <?php foreach ($stations as $st): ?>
                <option value="<?= $st['id'] ?>" data-rate="<?= $st['hourly_rate'] ?>" <?= ($preset_station_id === (int)$st['id']) ? 'selected' : '' ?>>
                  <?= e($st['name']) ?> (<?= e($st['category_name']) ?>) — ₹<?= number_format((float)$st['hourly_rate'], 0) ?>/hr
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Date Selection -->
          <div class="col-md-3">
            <label for="bDate" class="form-label">Booking Date <span class="text-danger">*</span></label>
            <input type="date" name="booking_date" id="bDate" class="form-control" value="<?= e($preset_date) ?>" required>
          </div>

          <!-- Duration Selection -->
          <div class="col-md-3">
            <label for="bDur" class="form-label">Session Duration</label>
            <select name="duration_hours" id="bDur" class="form-select">
              <option value="0.5" <?= (abs($preset_duration - 0.5) < 0.01) ? 'selected' : '' ?>>30 Minutes (Quick Match)</option>
              <option value="1" <?= (abs($preset_duration - 1) < 0.01) ? 'selected' : '' ?>>1 Hour (Standard Session)</option>
              <option value="1.5" <?= (abs($preset_duration - 1.5) < 0.01) ? 'selected' : '' ?>>1.5 Hours (Extended Match)</option>
              <option value="2" <?= (abs($preset_duration - 2) < 0.01) ? 'selected' : '' ?>>2 Hours (Championship Battle)</option>
              <option value="2.5" <?= (abs($preset_duration - 2.5) < 0.01) ? 'selected' : '' ?>>2.5 Hours (Power Session)</option>
              <option value="3" <?= (abs($preset_duration - 3) < 0.01) ? 'selected' : '' ?>>3 Hours (Squad Raid)</option>
              <option value="4" <?= (abs($preset_duration - 4) < 0.01) ? 'selected' : '' ?>>4 Hours (Marathon Play)</option>
              <option value="5" <?= (abs($preset_duration - 5) < 0.01) ? 'selected' : '' ?>>5 Hours (Champion All-Day)</option>
            </select>
          </div>

          <!-- Visual Slot Picker & Free / Booked Inspector -->
          <div class="col-12">
            <div class="p-3 rounded" style="background: rgba(0, 0, 0, 0.35); border: 1px solid var(--border-color);">
              <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <button type="button" class="btn btn-warning btn-sm text-dark fw-bold px-3 py-1 shadow-sm" id="btnStartRightNow">
                    <i class="bi bi-lightning-charge-fill me-1"></i> Start Right Now (<?= date('g:i A') ?>)
                  </button>
                  <div class="d-flex align-items-center gap-1">
                    <span class="text-muted-iron small">Start Time:</span>
                    <input type="time" name="start_time" id="bTime" value="<?= e(substr($preset_time, 0, 5)) ?>" class="form-control form-control-sm text-center font-monospace" style="max-width: 110px;" required>
                  </div>
                </div>
                <div class="d-flex gap-3 small text-muted-iron" style="font-size: 0.75rem;">
                  <span><span class="badge bg-success py-1 px-1">&nbsp;</span> Green = Free</span>
                  <span><span class="badge bg-danger py-1 px-1">&nbsp;</span> Red = Booked</span>
                  <span><span class="badge bg-secondary py-1 px-1">&nbsp;</span> Grey = Past</span>
                </div>
              </div>

              <!-- Interactive Slot Buttons Container (30-min intervals) -->
              <div id="walkinSlotsContainer" class="d-flex flex-wrap gap-2 pt-1 pb-1">
                <span class="text-muted-iron small p-2">Select a gaming station above to inspect live slot availability.</span>
              </div>

              <div id="selectedSlotNotice" class="text-gold small mt-2 fw-bold" style="display: none;"></div>
            </div>
          </div>

          <!-- Customer Name -->
          <div class="col-md-6">
            <label for="custName" class="form-label">Player / Gamer Name <span class="text-danger">*</span></label>
            <input type="text" name="full_name" id="custName" class="form-control" placeholder="e.g. Gurpreet Singh" required>
          </div>

          <!-- Mobile Phone Number with Strict Indian Validation -->
          <div class="col-md-6">
            <label for="custPhone" class="form-label">WhatsApp / Mobile Number <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text bg-dark border-secondary border-opacity-25 text-muted-iron">+91</span>
              <input type="tel" name="phone" id="custPhone" class="form-control" placeholder="10-digit mobile number" maxlength="10" pattern="[6-9][0-9]{9}" required>
            </div>
            <div class="form-text text-muted-iron small" id="phoneHint">
              Must be a 10-digit Indian mobile number (e.g. 9812345678). Used for WhatsApp e-receipt & pass.
            </div>
          </div>

          <!-- Customer Email -->
          <div class="col-md-4">
            <label for="custEmail" class="form-label">Email Address (Optional)</label>
            <input type="email" name="email" id="custEmail" class="form-control" placeholder="player@gmail.com">
          </div>

          <!-- Payment Method -->
          <div class="col-md-4">
            <label for="payMethod" class="form-label">Counter Payment Method</label>
            <select name="payment_method" id="payMethod" class="form-select">
              <option value="cash" selected>Counter Cash</option>
              <option value="upi_qr">Counter UPI QR Scan</option>
              <option value="card">Card / POS Terminal</option>
            </select>
          </div>

          <!-- Payment Status -->
          <div class="col-md-4">
            <label for="pStatus" class="form-label">Payment Status</label>
            <select name="payment_status" id="pStatus" class="form-select">
              <option value="verified" selected>Paid & Verified (Confirm)</option>
              <option value="pending">Pay Later / Pending</option>
            </select>
          </div>

          <!-- Live Pricing Calculation Bar -->
          <div class="col-12">
            <div class="p-3 rounded d-flex justify-content-between align-items-center" style="background: rgba(214, 168, 75, 0.08); border: 1px solid rgba(214, 168, 75, 0.25);">
              <div>
                <span class="text-muted-iron small d-block">Session Calculation:</span>
                <span class="text-light fw-bold" id="calcDetail">Choose station and duration</span>
              </div>
              <div class="text-end">
                <span class="text-muted-iron small d-block">Total Due:</span>
                <span class="text-gold fw-bold fs-4 brand-font" id="calcTotal">₹0.00</span>
              </div>
            </div>
          </div>

          <!-- Counter Notes -->
          <div class="col-12">
            <label for="bNotes" class="form-label">Counter Notes / Game Request</label>
            <input type="text" name="notes" id="bNotes" class="form-control" placeholder="Optional notes (e.g. Regular player, requested CS2 pre-loaded)">
          </div>

        </div>

        <button type="submit" class="btn btn-iron btn-iron-primary w-100 py-3 fs-6">
          <i class="bi bi-check-circle-fill me-2"></i> Register & Lock Walk-In Session
        </button>

      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const stationSelect = document.getElementById('stSelect');
  const dateInput = document.getElementById('bDate');
  const durSelect = document.getElementById('bDur');
  const timeInput = document.getElementById('bTime');
  const slotsContainer = document.getElementById('walkinSlotsContainer');
  const noticeEl = document.getElementById('selectedSlotNotice');
  const calcDetail = document.getElementById('calcDetail');
  const calcTotal = document.getElementById('calcTotal');
  const phoneInput = document.getElementById('custPhone');
  const phoneHint = document.getElementById('phoneHint');

  // Strict Phone Validation Visual Feedback
  if (phoneInput) {
    phoneInput.addEventListener('input', function() {
      const val = this.value.replace(/\D/g, '');
      this.value = val.slice(0, 10);
      if (val.length === 10 && /^[6-9]/.test(val)) {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
        if (phoneHint) phoneHint.innerHTML = '<span class="text-green small"><i class="bi bi-check-circle-fill me-1"></i> Valid Indian mobile number. Ready for WhatsApp pass.</span>';
      } else {
        this.classList.remove('is-valid');
        if (val.length > 0) {
          this.classList.add('is-invalid');
          if (phoneHint) phoneHint.innerHTML = '<span class="text-danger small"><i class="bi bi-exclamation-circle me-1"></i> Must be 10 digits starting with 6, 7, 8, or 9.</span>';
        } else {
          this.classList.remove('is-invalid');
          if (phoneHint) phoneHint.textContent = 'Must be a 10-digit Indian mobile number (e.g. 9812345678). Used for WhatsApp e-receipt & pass.';
        }
      }
    });
  }

  // Update Pricing
  function updatePrice() {
    const opt = stationSelect.options[stationSelect.selectedIndex];
    if (!opt || !opt.value) {
      calcDetail.textContent = 'Choose station and duration';
      calcTotal.textContent = '₹0.00';
      return;
    }
    const rate = parseFloat(opt.getAttribute('data-rate') || 0);
    const dur = parseFloat(durSelect.value) || 1;
    const total = rate * dur;
    const durLabel = dur < 1 ? `${Math.round(dur * 60)} Mins` : `${dur} Hour(s)`;
    calcDetail.textContent = `${opt.text.split('—')[0].trim()} @ ₹${rate.toFixed(0)}/hr × ${durLabel}`;
    calcTotal.textContent = `₹${total.toFixed(2)}`;
  }

  // Button: Start Right Now
  const btnStartRightNow = document.getElementById('btnStartRightNow');
  if (btnStartRightNow) {
    btnStartRightNow.addEventListener('click', function() {
      const now = new Date();
      const hh = String(now.getHours()).padStart(2, '0');
      const mm = String(now.getMinutes()).padStart(2, '0');
      timeInput.value = `${hh}:${mm}`;
      const today = new Date().toISOString().split('T')[0];
      if (dateInput.value !== today) {
        dateInput.value = today;
      }
      noticeEl.style.display = 'block';
      noticeEl.innerHTML = `<i class="bi bi-lightning-charge-fill text-warning me-1"></i> Immediate walk-in start time set to <strong>${hh}:${mm}</strong> (${now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})})`;
      loadSlots();
    });
  }

  timeInput.addEventListener('change', function() {
    noticeEl.style.display = 'block';
    noticeEl.innerHTML = `<i class="bi bi-clock-check text-cyan me-1"></i> Custom Walk-In Start Time: <strong>${this.value}</strong>`;
  });

  // Fetch Slots
  function loadSlots() {
    const sid = stationSelect.value;
    const date = dateInput.value;
    const dur = durSelect.value;

    if (!sid || !date) {
      slotsContainer.innerHTML = '<span class="text-muted-iron small p-2">Select a gaming station and date to inspect slot availability.</span>';
      return;
    }

    slotsContainer.innerHTML = '<span class="text-muted-iron small p-2"><div class="spinner-border spinner-border-sm text-cyan me-2" role="status"></div>Checking real-time slot availability...</span>';

    fetch(`<?= BASE_URL ?>api/availability.php?station_id=${encodeURIComponent(sid)}&date=${encodeURIComponent(date)}&duration=${encodeURIComponent(dur)}&is_admin=1`)
      .then(res => res.json())
      .then(data => {
        if (!data.success) {
          slotsContainer.innerHTML = `<span class="text-danger small p-2">${data.message || 'Error loading slots.'}</span>`;
          return;
        }

        if (data.is_closed) {
          slotsContainer.innerHTML = `<span class="text-danger small p-2"><i class="bi bi-slash-circle me-1"></i> ${data.message}</span>`;
          return;
        }

        if (!data.slots || data.slots.length === 0) {
          slotsContainer.innerHTML = '<span class="text-warning small p-2">No operating slots configured for this date.</span>';
          return;
        }

        let html = '';
        const curVal = timeInput.value;
        let selectedFound = false;

        data.slots.forEach(s => {
          const isSelected = (curVal && curVal.startsWith(s.time.substring(0, 5)));
          if (isSelected && s.available) selectedFound = true;

          if (s.available) {
            html += `
              <button type="button" class="btn btn-sm ${isSelected ? 'btn-success fw-bold shadow-sm' : 'btn-outline-success'} walkin-slot-btn" data-time="${s.time}" data-display="${s.display}" style="font-size: 0.8rem; border-width: 1.5px;">
                <i class="bi bi-check2"></i> ${s.display}
              </button>
            `;
          } else {
            const reason = s.is_past ? 'Past' : 'Booked';
            html += `
              <button type="button" class="btn btn-sm ${s.is_past ? 'btn-outline-secondary' : 'btn-outline-danger'} opacity-50" disabled style="font-size: 0.8rem;" title="${reason}">
                ${s.display} <span style="font-size:0.65rem;">(${reason})</span>
              </button>
            `;
          }
        });

        slotsContainer.innerHTML = html;

        // Wire slot click events
        document.querySelectorAll('.walkin-slot-btn').forEach(btn => {
          btn.addEventListener('click', function() {
            document.querySelectorAll('.walkin-slot-btn').forEach(b => {
              b.classList.remove('btn-success', 'fw-bold', 'shadow-sm');
              b.classList.add('btn-outline-success');
            });
            this.classList.remove('btn-outline-success');
            this.classList.add('btn-success', 'fw-bold', 'shadow-sm');
            timeInput.value = this.getAttribute('data-time');
            noticeEl.style.display = 'block';
            noticeEl.innerHTML = `<i class="bi bi-clock-check-fill text-green me-1"></i> Selected Start Slot: ${this.getAttribute('data-display')} (${durSelect.value} hr session)`;
          });
        });

        if (selectedFound) {
          noticeEl.style.display = 'block';
          noticeEl.innerHTML = `<i class="bi bi-clock-check-fill text-green me-1"></i> Start Slot pre-selected: ${curVal.substring(0, 5)}`;
        }
      })
      .catch(err => {
        console.error(err);
        slotsContainer.innerHTML = '<span class="text-danger small p-2">Failed to connect to slot server.</span>';
      });
  }

  stationSelect.addEventListener('change', () => {
    updatePrice();
    loadSlots();
  });
  dateInput.addEventListener('change', loadSlots);
  durSelect.addEventListener('change', () => {
    updatePrice();
    loadSlots();
  });

  // Initial load if station preselected
  updatePrice();
  if (stationSelect.value) {
    loadSlots();
  }
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
