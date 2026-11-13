<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Live Arena Availability Radar & Operating Hours Management
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();

$admin_user = current_admin();
$db = get_db();

// Handle Operating Hours Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_hours'])) {
    if (!verify_csrf()) {
        set_flash('danger', 'CSRF verification failed.');
    } else {
        $days = $_POST['days'] ?? [];
        try {
            $stmt = $db->prepare("
                UPDATE business_hours 
                SET is_open = :open, open_time = :ot, close_time = :ct 
                WHERE day_of_week = :dow
            ");
            for ($dow = 0; $dow <= 6; $dow++) {
                $is_open = isset($days[$dow]['is_open']) ? 1 : 0;
                $open_time = $days[$dow]['open_time'] ?? '10:00:00';
                $close_time = $days[$dow]['close_time'] ?? '23:00:00';
                $stmt->execute([
                    ':open' => $is_open,
                    ':ot'   => $open_time,
                    ':ct'   => $close_time,
                    ':dow'  => $dow
                ]);
            }
            log_admin_activity($admin_user['id'], 'update_hours', 'Updated weekly business operating hours');
            set_flash('success', 'Weekly operating hours updated successfully.');
            redirect(BASE_URL . 'admin/availability.php?tab=config');
        } catch (Exception $e) {
            set_flash('danger', 'Error updating hours: ' . $e->getMessage());
        }
    }
}

// Handle Add Blocked Date
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_block'])) {
    if (!verify_csrf()) {
        set_flash('danger', 'CSRF verification failed.');
    } else {
        $block_date = trim($_POST['block_date'] ?? '');
        $reason = trim($_POST['reason'] ?? 'Holiday / Scheduled Maintenance');

        if (empty($block_date)) {
            set_flash('danger', 'Please select a date to block.');
        } else {
            try {
                $stmt = $db->prepare("
                    INSERT INTO blocked_dates (block_date, reason) 
                    VALUES (:bdate, :r)
                    ON DUPLICATE KEY UPDATE reason = :r2
                ");
                $stmt->execute([':bdate' => $block_date, ':r' => $reason, ':r2' => $reason]);
                log_admin_activity($admin_user['id'], 'add_blocked_date', "Blocked date {$block_date}: {$reason}");
                set_flash('success', "Date {$block_date} blocked from public booking.");
                redirect(BASE_URL . 'admin/availability.php?tab=config');
            } catch (Exception $e) {
                set_flash('danger', 'Error blocking date: ' . $e->getMessage());
            }
        }
    }
}

// Handle Remove Blocked Date
if (isset($_GET['delete_block'])) {
    $del_id = (int)$_GET['delete_block'];
    if (verify_csrf($_GET['token'] ?? '')) {
        $db->prepare("DELETE FROM blocked_dates WHERE id = :id")->execute([':id' => $del_id]);
        log_admin_activity($admin_user['id'], 'delete_blocked_date', "Removed blocked date ID {$del_id}");
        set_flash('success', 'Blocked date unblocked.');
        redirect(BASE_URL . 'admin/availability.php?tab=config');
    }
}

// Handle Early Rig Release (Gamer left in between -> immediately release station)
if (isset($_GET['free_rig'])) {
    $free_booking_id = (int)$_GET['free_rig'];
    if (!verify_csrf($_GET['token'] ?? '')) {
        set_flash('danger', 'Invalid security token.');
    } else {
        $res = release_station_early($free_booking_id, 'Admin released rig early from Live Radar', $admin_user['id']);
        if ($res['success']) {
            set_flash('success', $res['message']);
        } else {
            set_flash('danger', $res['message']);
        }
        $redirect_date = trim($_GET['date'] ?? date('Y-m-d'));
        redirect(BASE_URL . 'admin/availability.php?tab=matrix&date=' . urlencode($redirect_date));
    }
}

// Active tab ('matrix' or 'config')
$active_tab = ($_GET['tab'] ?? '') === 'config' ? 'config' : 'matrix';

// Selected Date for Schedule Matrix
$selected_date = trim($_GET['date'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date)) {
    $selected_date = date('Y-m-d');
}

// Fetch Live Status Right Now
$live_radar = get_live_arena_status();

// Fetch Schedule Matrix for Selected Date
$schedule_matrix = get_station_schedule_matrix($selected_date);

// Fetch Business Hours & Blocked Dates for Config tab
$hours = $db->query("SELECT * FROM business_hours ORDER BY day_of_week ASC")->fetchAll();
$blocked_dates = $db->query("SELECT * FROM blocked_dates ORDER BY block_date ASC")->fetchAll();

$admin_title = 'Availability & Schedule Matrix';
require_once __DIR__ . '/includes/admin-header.php';
?>

<!-- Tab Navigation Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
  <div>
    <h3 class="h4 text-gold brand-font mb-1">
      <i class="bi bi-clock-history text-cyan me-2"></i>Arena Availability & Live Radar
    </h3>
    <span class="text-muted-iron small">Monitor station occupancy right now and inspect free vs booked slots on any date.</span>
  </div>

  <div class="d-flex gap-2">
    <a href="<?= BASE_URL ?>admin/availability.php?tab=matrix&date=<?= urlencode($selected_date) ?>" class="btn btn-iron <?= $active_tab === 'matrix' ? 'btn-iron-primary' : 'btn-iron-outline' ?> btn-iron-sm">
      <i class="bi bi-grid-3x3-gap-fill me-1"></i> Live Radar & Schedule Grid
    </a>
    <a href="<?= BASE_URL ?>admin/availability.php?tab=config" class="btn btn-iron <?= $active_tab === 'config' ? 'btn-iron-primary' : 'btn-iron-outline' ?> btn-iron-sm">
      <i class="bi bi-sliders me-1"></i> Operating Hours & Closures
    </a>
  </div>
</div>

<?php if ($active_tab === 'matrix'): ?>

  <!-- 1. LIVE RADAR (CURRENT TIME / RIGHT NOW) -->
  <div class="iron-card p-4 mb-4" style="background: radial-gradient(circle at top right, rgba(0, 229, 255, 0.08), var(--card-bg) 60%); border-color: rgba(0, 229, 255, 0.3);">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-3 border-bottom border-secondary border-opacity-25 gap-3">
      <div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-danger rounded-pill pulse-badge" style="font-size: 0.75rem; letter-spacing: 0.06em;">LIVE ARENA RADAR</span>
          <span class="text-light fw-bold">Current Time: <?= date('g:i A') ?></span>
          <span class="text-muted-iron small">(<?= date('l, d M Y') ?>)</span>
        </div>
        <div class="text-muted-iron small mt-1">
          Operating Hours: <?= date('g:i A', strtotime($live_radar['open_time'])) ?> – <?= date('g:i A', strtotime($live_radar['close_time'])) ?>
          <?= $live_radar['is_open_now'] ? '<span class="text-green ms-2 fw-bold"><i class="bi bi-door-open-fill"></i> ARENA OPEN</span>' : '<span class="text-red ms-2 fw-bold"><i class="bi bi-door-closed-fill"></i> ARENA CLOSED</span>' ?>
        </div>
      </div>

      <div class="d-flex align-items-center gap-3">
        <div class="text-end">
          <span class="text-muted-iron small d-block">Stations Status</span>
          <span class="text-green fw-bold fs-6"><i class="bi bi-check-circle-fill"></i> <?= $live_radar['total_free'] ?> Free</span>
          <span class="text-muted-iron mx-1">|</span>
          <span class="text-red fw-bold fs-6"><i class="bi bi-controller"></i> <?= $live_radar['total_in_session'] ?> In Session</span>
        </div>
        <a href="<?= BASE_URL ?>admin/booking-create.php?date=<?= date('Y-m-d') ?>&start_time=<?= date('H:i') ?>" class="btn btn-iron btn-iron-primary btn-iron-sm">
          <i class="bi bi-plus-circle me-1"></i> New Walk-In
        </a>
      </div>
    </div>

    <!-- Live Station Cards Grid -->
    <div class="row g-3">
      <?php foreach ($live_radar['stations'] as $st): ?>
        <div class="col-xl-3 col-lg-4 col-md-6">
          <div class="p-3 rounded h-100 position-relative" style="background: rgba(10, 13, 19, 0.7); border: 1px solid <?= $st['is_occupied'] ? 'rgba(230, 57, 70, 0.4)' : 'rgba(57, 255, 136, 0.3)' ?>; box-shadow: 0 4px 15px <?= $st['is_occupied'] ? 'rgba(230, 57, 70, 0.08)' : 'rgba(57, 255, 136, 0.05)' ?>;">
            
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <span class="fw-bold text-light d-block fs-6"><?= e($st['name']) ?></span>
                <span class="text-muted-iron small" style="font-size: 0.75rem;"><?= e($st['category_name']) ?> &bull; ₹<?= number_format($st['hourly_rate'], 0) ?>/hr</span>
              </div>
              <span class="badge <?= $st['status_badge_class'] ?> small py-1 px-2">
                <?= $st['status_label'] ?>
              </span>
            </div>

            <?php if ($st['is_occupied'] && !empty($st['current_booking'])): 
              $cb = $st['current_booking'];
            ?>
              <!-- Occupied Info -->
              <div class="p-2 rounded mb-2" style="background: rgba(230, 57, 70, 0.1); border: 1px solid rgba(230, 57, 70, 0.2);">
                <div class="d-flex justify-content-between small">
                  <span class="text-light fw-bold text-truncate" style="max-width: 140px;"><?= e($cb['customer_name']) ?></span>
                  <a href="<?= BASE_URL ?>admin/booking-detail.php?id=<?= $cb['id'] ?>" class="text-gold text-decoration-none fw-bold" title="Open Booking">#<?= e($cb['booking_ref']) ?></a>
                </div>
                <div class="d-flex justify-content-between text-muted-iron small mt-1" style="font-size: 0.72rem;">
                  <span><?= date('g:i A', strtotime($cb['start_time'])) ?> – <?= date('g:i A', strtotime($cb['end_time'])) ?></span>
                  <span class="text-red fw-bold"><?= $st['minutes_left'] ?>m left</span>
                </div>
                <div class="progress mt-2" style="height: 4px; background: rgba(255,255,255,0.1);">
                  <div class="progress-bar bg-danger" style="width: <?= $st['percent_elapsed'] ?>%;"></div>
                </div>
              </div>
              <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>admin/booking-detail.php?id=<?= $cb['id'] ?>" class="btn btn-outline-secondary btn-sm flex-fill py-1" style="font-size: 0.75rem;">
                  <i class="bi bi-eye"></i> Details
                </a>
                <a href="<?= BASE_URL ?>admin/availability.php?free_rig=<?= $cb['id'] ?>&token=<?= csrf_token() ?>&date=<?= urlencode($selected_date) ?>" 
                   class="btn btn-outline-danger btn-sm py-1 px-2 fw-bold" 
                   style="font-size: 0.72rem;" 
                   onclick="return confirm('Release <?= e($st['name']) ?> now? This marks session completed immediately and makes this rig 100% FREE for new walk-in gamers.');" 
                   title="Gamer left early? Click to end session & immediately free station for new players">
                  <i class="bi bi-box-arrow-right me-1"></i> Free Rig
                </a>
                <?php $cb_wa = generate_whatsapp_url('reminder', $cb); ?>
                <a href="<?= $cb_wa ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-success btn-sm py-1 px-2" title="WhatsApp Message to Gamer">
                  <i class="bi bi-whatsapp"></i>
                </a>
              </div>

            <?php else: ?>
              <!-- Free Station Info -->
              <div class="p-2 rounded mb-2 text-muted-iron small" style="background: rgba(57, 255, 136, 0.05); border: 1px solid rgba(57, 255, 136, 0.15); min-height: 58px;">
                <?php if (!empty($st['upcoming_booking'])): 
                  $ub = $st['upcoming_booking'];
                ?>
                  <span class="text-green small d-block"><i class="bi bi-clock me-1"></i>Next booking at <strong><?= date('g:i A', strtotime($ub['start_time'])) ?></strong></span>
                  <span class="text-muted-iron small" style="font-size: 0.7rem;">Booked by <?= e($ub['customer_name']) ?> (#<?= e($ub['booking_ref']) ?>)</span>
                <?php else: ?>
                  <span class="text-green small d-block"><i class="bi bi-check2-circle me-1"></i>Available for walk-in session</span>
                  <span class="text-muted-iron small" style="font-size: 0.7rem;">No bookings scheduled right now</span>
                <?php endif; ?>
              </div>
              <a href="<?= BASE_URL ?>admin/booking-create.php?station_id=<?= $st['id'] ?>&booking_date=<?= date('Y-m-d') ?>&start_time=<?= date('H:i') ?>" class="btn btn-iron btn-iron-cyan btn-sm w-100 py-1" style="font-size: 0.75rem;">
                <i class="bi bi-lightning-charge-fill me-1"></i> Start Walk-In (<?= date('g:i A') ?>)
              </a>
            <?php endif; ?>

          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- 2. INTERACTIVE DATE TIMETABLE & SLOT MATRIX -->
  <div class="admin-table-card p-4">
    <!-- Filter and Date Jump Controls -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
      <div>
        <h4 class="h5 text-light brand-font mb-1">
          <i class="bi bi-calendar-range text-gold me-2"></i>Station Timetable & Hourly Availability
        </h4>
        <span class="text-muted-iron small">Viewing schedule for <strong><?= date('l, d F Y', strtotime($selected_date)) ?></strong>. Click any green slot to book instantly!</span>
      </div>

      <!-- Date Jump Bar -->
      <form method="GET" action="<?= BASE_URL ?>admin/availability.php" class="d-flex flex-wrap align-items-center gap-2">
        <input type="hidden" name="tab" value="matrix">
        
        <div class="btn-group btn-group-sm" role="group">
          <a href="<?= BASE_URL ?>admin/availability.php?tab=matrix&date=<?= date('Y-m-d') ?>" class="btn btn-outline-secondary <?= $selected_date === date('Y-m-d') ? 'active text-gold border-gold' : '' ?>">Today</a>
          <a href="<?= BASE_URL ?>admin/availability.php?tab=matrix&date=<?= date('Y-m-d', strtotime('+1 day')) ?>" class="btn btn-outline-secondary <?= $selected_date === date('Y-m-d', strtotime('+1 day')) ? 'active text-gold border-gold' : '' ?>">Tomorrow</a>
          <a href="<?= BASE_URL ?>admin/availability.php?tab=matrix&date=<?= date('Y-m-d', strtotime('+2 days')) ?>" class="btn btn-outline-secondary <?= $selected_date === date('Y-m-d', strtotime('+2 days')) ? 'active text-gold border-gold' : '' ?>">+2 Days</a>
        </div>

        <div class="input-group input-group-sm" style="max-width: 200px;">
          <input type="date" name="date" class="form-control form-control-sm" value="<?= e($selected_date) ?>" onchange="this.form.submit()">
          <button class="btn btn-iron btn-iron-outline btn-iron-sm" type="submit">Go</button>
        </div>
      </form>
    </div>

    <!-- Legend -->
    <div class="d-flex flex-wrap align-items-center gap-3 p-3 rounded mb-4" style="background: rgba(0,0,0,0.25); border: 1px solid var(--border-color); font-size: 0.8rem;">
      <span class="text-muted-iron fw-bold me-1">Slot Legend:</span>
      <span class="d-flex align-items-center gap-1 text-green">
        <span class="badge bg-success" style="width: 14px; height: 14px; padding: 0;">&nbsp;</span> Available (Click to Book)
      </span>
      <span class="d-flex align-items-center gap-1 text-red">
        <span class="badge bg-danger" style="width: 14px; height: 14px; padding: 0;">&nbsp;</span> Booked / Occupied
      </span>
      <span class="d-flex align-items-center gap-1 text-gold">
        <span class="badge bg-warning text-dark" style="width: 14px; height: 14px; padding: 0;">&nbsp;</span> In Session Right Now
      </span>
      <span class="d-flex align-items-center gap-1 text-secondary">
        <span class="badge bg-secondary opacity-75" style="width: 14px; height: 14px; padding: 0;">&nbsp;</span> Past / Expired (Time Passed)
      </span>
    </div>

    <?php if ($schedule_matrix['is_closed']): ?>
      <div class="alert alert-warning py-4 text-center my-4">
        <i class="bi bi-slash-circle fs-3 d-block mb-2 text-red"></i>
        <h5 class="alert-heading fw-bold mb-1">Sanctuary Closed on <?= date('d M Y', strtotime($selected_date)) ?></h5>
        <p class="mb-0 small text-muted-iron"><?= e($schedule_matrix['reason']) ?></p>
      </div>
    <?php else: ?>

      <!-- Visual Hourly Matrix Grid Table -->
      <div class="table-responsive" style="max-height: 650px;">
        <table class="table table-dark table-bordered align-middle text-center mb-0" style="font-size: 0.82rem; min-width: 980px;">
          <thead style="position: sticky; top: 0; z-index: 5; background: #0c1017;">
            <tr>
              <th class="text-start py-3 ps-3 text-gold" style="width: 220px; min-width: 200px;">Station / Platform</th>
              <?php foreach ($schedule_matrix['hours'] as $h): ?>
                <th class="py-3 px-1 text-light" style="min-width: 68px;">
                  <span class="d-block fw-bold"><?= $h['display'] ?></span>
                </th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($schedule_matrix['stations'] as $st): ?>
              <tr>
                <!-- Station Title Column -->
                <td class="text-start ps-3 py-3" style="background: rgba(21, 25, 34, 0.95);">
                  <div class="fw-bold text-light"><?= e($st['name']) ?></div>
                  <div class="text-muted-iron" style="font-size: 0.72rem;"><?= e($st['category_name']) ?> &bull; ₹<?= number_format($st['hourly_rate'], 0) ?>/hr</div>
                </td>

                <!-- Hourly Slot Cells -->
                <?php foreach ($st['slots'] as $slot_time => $slot): ?>
                  <?php if ($slot['state'] === 'in_session'): 
                    $b = $slot['booking'];
                  ?>
                    <!-- IN SESSION NOW (Gold Glow) -->
                    <td class="p-1" style="background: rgba(214, 168, 75, 0.15);">
                      <a href="<?= BASE_URL ?>admin/booking-detail.php?id=<?= $b['id'] ?>" class="d-block p-1 rounded text-decoration-none" style="background: rgba(214, 168, 75, 0.35); border: 1px solid var(--gold); color: #fff;" title="IN SESSION NOW: Booked by <?= e($b['customer_name']) ?> (#<?= e($b['booking_ref']) ?>)">
                        <span class="d-block fw-bold" style="font-size: 0.7rem; color: var(--gold);">SESSION</span>
                        <span class="d-block text-truncate" style="font-size: 0.65rem;"><?= e($b['customer_name']) ?></span>
                      </a>
                    </td>

                  <?php elseif ($slot['state'] === 'booked' || $slot['state'] === 'pending'): 
                    $b = $slot['booking'];
                    $is_pend = ($slot['state'] === 'pending');
                  ?>
                    <!-- BOOKED / OCCUPIED (Red) -->
                    <td class="p-1" style="background: rgba(230, 57, 70, 0.1);">
                      <a href="<?= BASE_URL ?>admin/booking-detail.php?id=<?= $b['id'] ?>" class="d-block p-1 rounded text-decoration-none" style="background: <?= $is_pend ? 'rgba(255, 193, 7, 0.25)' : 'rgba(230, 57, 70, 0.35)' ?>; border: 1px solid <?= $is_pend ? '#ffc107' : 'rgba(230, 57, 70, 0.6)' ?>; color: #fff;" title="<?= $is_pend ? 'Pending Verification' : 'Confirmed Booking' ?>: <?= e($b['customer_name']) ?> (<?= date('g:i A', strtotime($b['start_time'])) ?> - <?= date('g:i A', strtotime($b['end_time'])) ?>)">
                        <span class="d-block fw-bold" style="font-size: 0.7rem; color: <?= $is_pend ? '#ffc107' : '#FF8A93' ?>;"><?= $is_pend ? 'PENDING' : 'BOOKED' ?></span>
                        <span class="d-block text-truncate" style="font-size: 0.65rem;"><?= e($b['customer_name']) ?></span>
                      </a>
                    </td>

                  <?php elseif ($slot['state'] === 'past_booked'): 
                    $b = $slot['booking'];
                  ?>
                    <!-- PAST BOOKING (Already Completed) -->
                    <td class="p-1" style="background: rgba(230, 57, 70, 0.05);">
                      <a href="<?= BASE_URL ?>admin/booking-detail.php?id=<?= $b['id'] ?>" class="d-block p-1 rounded text-decoration-none" style="background: rgba(230, 57, 70, 0.2); border: 1px dashed rgba(230, 57, 70, 0.4); color: #fff;" title="Past Booking: <?= e($b['customer_name']) ?> (#<?= e($b['booking_ref']) ?>)">
                        <span class="d-block fw-bold text-muted" style="font-size: 0.65rem;">PAST BOOKED</span>
                        <span class="d-block text-truncate text-secondary" style="font-size: 0.62rem;"><?= e($b['customer_name']) ?></span>
                      </a>
                    </td>

                  <?php elseif ($slot['state'] === 'expired' || $slot['state'] === 'past'): ?>
                    <!-- EXPIRED / PAST TIME SLOT (Time Passed, Cannot Book) -->
                    <td class="p-1" style="background: rgba(255, 255, 255, 0.02);">
                      <div class="py-1 px-1 rounded text-center" style="background: rgba(255, 255, 255, 0.04); border: 1px dashed rgba(255, 255, 255, 0.15);" title="Slot Expired / Time has passed">
                        <span class="badge bg-secondary opacity-75 d-block mb-1 py-1" style="font-size: 0.62rem; letter-spacing: 0.04em;"><i class="bi bi-clock-history"></i> EXPIRED</span>
                        <span class="text-muted-iron d-block" style="font-size: 0.6rem;">Past Time</span>
                      </div>
                    </td>

                  <?php else: ?>
                    <!-- FREE & AVAILABLE (Click to Book Walk-in) -->
                    <td class="p-1" style="background: rgba(57, 255, 136, 0.03);">
                      <a href="<?= BASE_URL ?>admin/booking-create.php?station_id=<?= $st['id'] ?>&booking_date=<?= urlencode($selected_date) ?>&start_time=<?= urlencode($slot_time) ?>" class="btn btn-sm w-100 p-1 text-decoration-none text-green fw-bold rounded slot-free-hover" style="background: rgba(57, 255, 136, 0.12); border: 1px solid rgba(57, 255, 136, 0.3); font-size: 0.7rem;" title="Free Slot! Click to book walk-in reservation for <?= e($st['name']) ?> at <?= $slot['display_full'] ?>">
                        <i class="bi bi-plus"></i> FREE
                      </a>
                    </td>
                  <?php endif; ?>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    <?php endif; ?>
  </div>

<?php else: ?>

  <!-- TAB 2: OPERATING HOURS & BLOCKED DATES CONFIGURATION -->
  <div class="row g-4">
    <!-- Weekly Operating Hours Form -->
    <div class="col-lg-7">
      <div class="iron-card p-4">
        <h4 class="h5 text-gold brand-font mb-2">Weekly Business Operating Hours</h4>
        <p class="text-muted-iron small mb-4">
          Configure the arena opening and closing times for each day of the week. Slot generator adheres strictly to these hours.
        </p>

        <form method="POST" action="<?= BASE_URL ?>admin/availability.php?tab=config">
          <?= csrf_field() ?>
          <input type="hidden" name="update_hours" value="1">

          <div class="table-responsive">
            <table class="table table-dark align-middle">
              <thead>
                <tr>
                  <th>Day</th>
                  <th>Status</th>
                  <th>Opening Time</th>
                  <th>Closing Time</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($hours as $h): ?>
                  <tr>
                    <td class="fw-bold text-light"><?= e($h['day_name']) ?></td>
                    <td>
                      <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="days[<?= $h['day_of_week'] ?>][is_open]" value="1" <?= $h['is_open'] ? 'checked' : '' ?>>
                        <label class="form-check-label text-muted-iron small"><?= $h['is_open'] ? 'Open' : 'Closed' ?></label>
                      </div>
                    </td>
                    <td>
                      <input type="time" name="days[<?= $h['day_of_week'] ?>][open_time]" class="form-control form-control-sm" value="<?= date('H:i', strtotime($h['open_time'])) ?>">
                    </td>
                    <td>
                      <input type="time" name="days[<?= $h['day_of_week'] ?>][close_time]" class="form-control form-control-sm" value="<?= date('H:i', strtotime($h['close_time'])) ?>">
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <button type="submit" class="btn btn-iron btn-iron-primary mt-3">
            <i class="bi bi-save me-1"></i> Save Operating Schedule
          </button>
        </form>
      </div>
    </div>

    <!-- Blocked Dates (Holidays / Private Tournaments) -->
    <div class="col-lg-5">
      <div class="iron-card p-4 mb-4">
        <h4 class="h5 text-gold brand-font mb-2">Block Specific Dates</h4>
        <p class="text-muted-iron small mb-4">
          Block days for arena maintenance, private squad tournaments, or official public holidays.
        </p>

        <form method="POST" action="<?= BASE_URL ?>admin/availability.php?tab=config">
          <?= csrf_field() ?>
          <input type="hidden" name="add_block" value="1">

          <div class="mb-3">
            <label class="form-label">Closure Date <span class="text-danger">*</span></label>
            <input type="date" name="block_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Reason for Closure</label>
            <input type="text" name="reason" class="form-control" placeholder="e.g. LAN Tournament / Deep Cleaning" required>
          </div>

          <button type="submit" class="btn btn-iron btn-iron-primary btn-iron-sm w-100">
            <i class="bi bi-slash-circle me-1"></i> Block This Date
          </button>
        </form>
      </div>

      <!-- Active Blocked Dates List -->
      <div class="iron-card p-4">
        <h5 class="h6 text-light mb-3">Scheduled Arena Closures</h5>
        <?php if (!empty($blocked_dates)): ?>
          <ul class="list-group list-group-flush bg-transparent">
            <?php foreach ($blocked_dates as $bd): ?>
              <li class="list-group-item bg-transparent text-light border-secondary border-opacity-25 px-0 d-flex justify-content-between align-items-center">
                <div>
                  <strong class="text-red d-block"><?= date('d M Y', strtotime($bd['block_date'])) ?></strong>
                  <span class="text-muted-iron small"><?= e($bd['reason']) ?></span>
                </div>
                <a href="<?= BASE_URL ?>admin/availability.php?delete_block=<?= $bd['id'] ?>&token=<?= csrf_token() ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Unblock this date?');">
                  <i class="bi bi-trash"></i>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="text-muted-iron small text-center py-3">
            No dates currently blocked. Sanctuary is open according to weekly schedule.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

<?php endif; ?>

<style>
.slot-free-hover:hover {
  background: rgba(57, 255, 136, 0.3) !important;
  color: #fff !important;
  border-color: var(--rgb-green) !important;
  transform: scale(1.04);
}
.pulse-badge {
  animation: pulse-red 2s infinite;
}
@keyframes pulse-red {
  0% { box-shadow: 0 0 0 0 rgba(230, 57, 70, 0.7); }
  70% { box-shadow: 0 0 0 6px rgba(230, 57, 70, 0); }
  100% { box-shadow: 0 0 0 0 rgba(230, 57, 70, 0); }
}
</style>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
