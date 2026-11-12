<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Admin Control Panel Dashboard with Real-Time KPIs & Performance Graphs
 */

$admin_title = 'Dashboard Overview';
require_once __DIR__ . '/includes/admin-header.php';

$db = get_db();
$today = date('Y-m-d');
$first_day_month = date('Y-m-01');

// 1. KPI Aggregations
$total_bookings = (int)$db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();

// Today's Bookings
$today_stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE booking_date = :td");
$today_stmt->execute([':td' => $today]);
$today_bookings = (int)$today_stmt->fetchColumn();

// Status counts
$pending_bookings = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending_payment'")->fetchColumn();
$confirmed_bookings = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
$in_session_bookings = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE status = 'in_session'")->fetchColumn();
$completed_bookings = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE status = 'completed'")->fetchColumn();
$cancelled_bookings = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE status IN ('cancelled', 'rejected')")->fetchColumn();

// Pending Payments awaiting verification
$pending_payments = (int)$db->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn();

// WhatsApp Pending Notifications
$pending_wa_count = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE whatsapp_status = 'pending'")->fetchColumn();

// Today's Revenue (from verified payments or confirmed bookings today)
$rev_today_stmt = $db->prepare("
    SELECT COALESCE(SUM(b.final_amount), 0) 
    FROM bookings b
    JOIN payments p ON p.booking_id = b.id
    WHERE b.booking_date = :td AND p.status = 'verified'
");
$rev_today_stmt->execute([':td' => $today]);
$today_revenue = (float)$rev_today_stmt->fetchColumn();

// Monthly Revenue
$rev_month_stmt = $db->prepare("
    SELECT COALESCE(SUM(b.final_amount), 0) 
    FROM bookings b
    JOIN payments p ON p.booking_id = b.id
    WHERE b.booking_date >= :fdm AND p.status = 'verified'
");
$rev_month_stmt->execute([':fdm' => $first_day_month]);
$monthly_revenue = (float)$rev_month_stmt->fetchColumn();

// 2. Most Booked Stations (Top 5)
$popular_stations_stmt = $db->query("
    SELECT s.name, s.code, c.name AS category_name, COUNT(b.id) AS total_sessions, COALESCE(SUM(b.final_amount), 0) AS total_revenue
    FROM stations s
    JOIN station_categories c ON s.category_id = c.id
    LEFT JOIN bookings b ON b.station_id = s.id AND b.status IN ('confirmed', 'completed', 'in_session')
    GROUP BY s.id
    ORDER BY total_sessions DESC, s.display_order ASC
    LIMIT 5
");
$popular_stations = $popular_stations_stmt->fetchAll();

// 3. Recent Bookings (Latest 8)
$recent_stmt = $db->query("
    SELECT b.*, s.name AS station_name, cust.full_name AS customer_name, cust.phone AS customer_phone,
           p.status AS payment_status, p.upi_transaction_id
    FROM bookings b
    JOIN stations s ON b.station_id = s.id
    JOIN customers cust ON b.customer_id = cust.id
    LEFT JOIN payments p ON p.booking_id = b.id
    ORDER BY b.id DESC
    LIMIT 8
");
$recent_bookings = $recent_stmt->fetchAll();

// 4. Live Arena Status
$dash_radar = get_live_arena_status();

// 5. 7-Day Trend for Dashboard Graph
$dash_trend_stmt = $db->query("
    SELECT DATE(b.booking_date) AS b_date, 
           COALESCE(SUM(b.final_amount), 0) AS daily_rev, 
           COUNT(b.id) AS daily_count
    FROM bookings b
    WHERE b.booking_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
      AND b.status IN ('confirmed', 'completed', 'in_session')
    GROUP BY DATE(b.booking_date)
    ORDER BY b_date ASC
");
$dash_daily = $dash_trend_stmt->fetchAll();

$d7_labels = [];
$d7_rev = [];
$d7_counts = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $d7_labels[] = date('D (d M)', strtotime($d));
    $found = false;
    foreach ($dash_daily as $dd) {
        if ($dd['b_date'] === $d) {
            $d7_rev[] = (float)$dd['daily_rev'];
            $d7_counts[] = (int)$dd['daily_count'];
            $found = true;
            break;
        }
    }
    if (!$found) {
        $d7_rev[] = 0;
        $d7_counts[] = 0;
    }
}
?>

<!-- KPI Stat Row 1: Bookings Counts -->
<div class="row g-3 mb-4">
  <div class="col-xl-3 col-md-6">
    <div class="kpi-card">
      <div class="kpi-title">Total Bookings</div>
      <div class="kpi-value text-gold"><?= number_format($total_bookings) ?></div>
      <div class="kpi-sub">All-time customer reservations</div>
      <i class="bi bi-calendar2-range kpi-icon"></i>
    </div>
  </div>

  <div class="col-xl-3 col-md-6">
    <div class="kpi-card">
      <div class="kpi-title">Today's Bookings</div>
      <div class="kpi-value text-cyan"><?= number_format($today_bookings) ?></div>
      <div class="kpi-sub"><?= date('l, d M Y') ?></div>
      <i class="bi bi-controller kpi-icon"></i>
    </div>
  </div>

  <div class="col-xl-3 col-md-6">
    <div class="kpi-card">
      <div class="kpi-title">Pending Payment / Action</div>
      <div class="kpi-value text-warning"><?= number_format($pending_bookings) ?></div>
      <div class="kpi-sub"><?= $pending_payments ?> payment(s) awaiting verification</div>
      <i class="bi bi-hourglass-split kpi-icon"></i>
    </div>
  </div>

  <div class="col-xl-3 col-md-6">
    <div class="kpi-card">
      <div class="kpi-title">Confirmed Slots</div>
      <div class="kpi-value text-green"><?= number_format($confirmed_bookings) ?></div>
      <div class="kpi-sub"><?= $cancelled_bookings ?> cancelled/rejected</div>
      <i class="bi bi-check-circle kpi-icon"></i>
    </div>
  </div>
</div>

<!-- KPI Stat Row 2: Financials & Revenue -->
<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="kpi-card" style="border-left: 4px solid var(--gold);">
      <div class="kpi-title">Today's Verified Revenue</div>
      <div class="kpi-value text-gold"><?= format_currency($today_revenue) ?></div>
      <div class="kpi-sub">Verified UPI & cash bookings for today</div>
      <i class="bi bi-currency-rupee kpi-icon"></i>
    </div>
  </div>

  <div class="col-md-6">
    <div class="kpi-card" style="border-left: 4px solid var(--rgb-cyan);">
      <div class="kpi-title">Month-To-Date Revenue (<?= date('F Y') ?>)</div>
      <div class="kpi-value text-cyan"><?= format_currency($monthly_revenue) ?></div>
      <div class="kpi-sub">Verified monthly sanctuary collections</div>
      <i class="bi bi-wallet2 kpi-icon"></i>
    </div>
  </div>
</div>

<!-- WhatsApp Pending Notification Banner -->
<?php if ($pending_wa_count > 0): ?>
  <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-3 p-3 mb-4 shadow-sm" style="background: rgba(214, 168, 75, 0.12); border: 1.5px solid var(--gold); color: #fff;">
    <div>
      <h5 class="alert-heading fw-bold mb-1 text-gold">
        <i class="bi bi-whatsapp fs-5 me-2"></i><?= $pending_wa_count ?> Gamer Notification<?= $pending_wa_count > 1 ? 's' : '' ?> Waiting for WhatsApp Dispatch
      </h5>
      <span class="small text-light">
        Customer passes, payment verification notices, or timing reschedules remain unsent on WhatsApp.
      </span>
    </div>
    <a href="<?= BASE_URL ?>admin/bookings.php?wa_status=pending" class="btn btn-warning text-dark fw-bold btn-sm px-3 py-2">
      <i class="bi bi-send-fill me-1"></i> Review & WhatsApp Now (<?= $pending_wa_count ?>)
    </a>
  </div>
<?php endif; ?>

<!-- Live Arena Status Radar Strip -->
<div class="iron-card p-3 mb-4" style="background: radial-gradient(circle at 10% 50%, rgba(0, 229, 255, 0.08), var(--card-bg) 60%); border-color: rgba(0, 229, 255, 0.25);">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
      <div class="d-flex align-items-center gap-2">
        <span class="badge bg-danger rounded-pill pulse-badge" style="font-size: 0.72rem;">LIVE RADAR</span>
        <strong class="text-light fs-6">Arena Real-Time Status</strong>
        <span class="text-muted-iron small">&bull; <?= date('g:i A') ?></span>
      </div>
      <div class="text-muted-iron small mt-1">
        Currently: <span class="text-green fw-bold"><?= $dash_radar['total_free'] ?> Rigs Free</span> &bull; 
        <span class="text-red fw-bold"><?= $dash_radar['total_in_session'] ?> In Session</span> &bull; 
        Total: <?= $dash_radar['total_stations'] ?> Stations
      </div>
    </div>
    
    <div class="d-flex flex-wrap gap-2">
      <a href="<?= BASE_URL ?>admin/availability.php" class="btn btn-iron btn-iron-cyan btn-iron-sm">
        <i class="bi bi-grid-3x3-gap-fill me-1"></i> Full Schedule Matrix
      </a>
      <a href="<?= BASE_URL ?>admin/booking-create.php?date=<?= date('Y-m-d') ?>&start_time=<?= date('H:i') ?>" class="btn btn-iron btn-iron-primary btn-iron-sm">
        <i class="bi bi-plus-circle me-1"></i> Quick Walk-In
      </a>
      <a href="<?= BASE_URL ?>admin/bookings.php?wa_status=pending" class="btn btn-iron btn-iron-sm <?= $pending_wa_count > 0 ? 'btn-iron-gold' : 'btn-iron-outline' ?>">
        <i class="bi bi-whatsapp me-1"></i> WA Pending (<?= $pending_wa_count ?>)
      </a>
      <a href="<?= BASE_URL ?>admin/payments.php" class="btn btn-iron btn-iron-outline btn-iron-sm">
        <i class="bi bi-shield-check me-1"></i> Payments (<?= $pending_payments ?>)
      </a>
    </div>
  </div>
</div>

<!-- Esports Dashboard Graphs Row -->
<div class="row g-4 mb-4">
  <!-- 7-Day Revenue Velocity -->
  <div class="col-lg-8">
    <div class="admin-table-card p-4 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="h5 text-light brand-font mb-1">
            <i class="bi bi-graph-up text-gold me-2"></i>7-Day Revenue Velocity
          </h4>
          <span class="text-muted-iron small">Daily collections trajectory over the last 7 days.</span>
        </div>
        <span class="badge bg-gold text-dark font-monospace">Past 7 Days</span>
      </div>
      <div style="position: relative; height: 250px; width: 100%;">
        <canvas id="dashRevenueChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Booking Status Breakdown -->
  <div class="col-lg-4">
    <div class="admin-table-card p-4 h-100">
      <h4 class="h5 text-light brand-font mb-1">
        <i class="bi bi-pie-chart text-cyan me-2"></i>Booking Pipeline Status
      </h4>
      <span class="text-muted-iron small d-block mb-3">All-time booking status breakdown</span>
      <div style="position: relative; height: 230px; width: 100%;">
        <canvas id="dashStatusChart"></canvas>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- Recent Bookings Table -->
  <div class="col-xl-8">
    <div class="admin-table-card p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="h5 text-light mb-0 brand-font">Recent Bookings</h4>
        <a href="<?= BASE_URL ?>admin/bookings.php" class="text-gold small text-decoration-none fw-bold">
          View All <i class="bi bi-arrow-right"></i>
        </a>
      </div>

      <div class="table-responsive">
        <table class="table table-dark align-middle">
          <thead>
            <tr>
              <th>Ref / Customer</th>
              <th>Station / Date</th>
              <th>Time Slot</th>
              <th>Amount</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($recent_bookings)): ?>
              <?php foreach ($recent_bookings as $b): ?>
                <tr>
                  <td>
                    <span class="font-monospace text-gold fw-bold d-block"><?= e($b['booking_ref']) ?></span>
                    <strong class="text-light small"><?= e($b['customer_name']) ?></strong>
                    <span class="text-secondary small d-block"><?= e($b['customer_phone']) ?></span>
                  </td>
                  <td>
                    <strong class="text-cyan small d-block"><?= e($b['station_name']) ?></strong>
                    <span class="text-muted-iron small"><?= date('d M Y', strtotime($b['booking_date'])) ?></span>
                  </td>
                  <td>
                    <span class="text-light small d-block"><?= date('g:i A', strtotime($b['start_time'])) ?></span>
                    <span class="text-secondary small"><?= $b['duration_hours'] ?> hr(s)</span>
                  </td>
                  <td>
                    <strong class="text-light"><?= format_currency($b['final_amount']) ?></strong>
                    <?php if ($b['payment_status'] === 'verified'): ?>
                      <span class="text-green small d-block"><i class="bi bi-check2"></i> Paid</span>
                    <?php else: ?>
                      <span class="text-warning small d-block"><i class="bi bi-clock"></i> Unverified</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?= booking_status_badge($b['status']) ?>
                  </td>
                  <td class="text-end">
                    <div class="d-flex gap-1 justify-content-end">
                      <a href="<?= BASE_URL ?>admin/booking-detail.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-warning" title="View & Edit Booking">
                        <i class="bi bi-eye"></i>
                      </a>
                      <?php $wa_btn = generate_whatsapp_url('confirmed', $b); ?>
                      <a href="<?= $wa_btn ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-success" title="WhatsApp Gamer">
                        <i class="bi bi-whatsapp"></i>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="text-center py-4 text-muted-iron small">
                  No bookings logged in the system yet.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Popular Stations Sidebar -->
  <div class="col-xl-4">
    <div class="admin-table-card p-4 h-100">
      <h4 class="h5 text-light mb-3 brand-font">Station Utilization</h4>
      <p class="text-muted-iron small mb-4">Most booked battle stations and rigs in Sri Muktsar Sahib.</p>

      <?php if (!empty($popular_stations)): ?>
        <?php foreach ($popular_stations as $pst): ?>
          <div class="p-3 rounded mb-3" style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color);">
            <div class="d-flex justify-content-between align-items-start mb-1">
              <strong class="text-light"><?= e($pst['name']) ?></strong>
              <span class="badge bg-gold text-dark"><?= $pst['total_sessions'] ?> Sessions</span>
            </div>
            <div class="d-flex justify-content-between small text-muted-iron">
              <span><?= e($pst['category_name']) ?></span>
              <span class="text-green"><?= format_currency($pst['total_revenue']) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <div class="mt-4 pt-3 border-top border-secondary border-opacity-25 text-center">
        <a href="<?= BASE_URL ?>admin/reports.php" class="btn btn-iron btn-iron-outline btn-iron-sm w-100">
          <i class="bi bi-pie-chart-fill me-1"></i> View Full Utilization Report
        </a>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  Chart.defaults.color = '#A0AEC0';
  Chart.defaults.font.family = "'Segoe UI', sans-serif";

  // 1. Dash Revenue Chart
  const ctxDash = document.getElementById('dashRevenueChart').getContext('2d');
  const gradDash = ctxDash.createLinearGradient(0, 0, 0, 250);
  gradDash.addColorStop(0, 'rgba(214, 168, 75, 0.4)');
  gradDash.addColorStop(1, 'rgba(214, 168, 75, 0.0)');

  new Chart(ctxDash, {
    type: 'line',
    data: {
      labels: <?= json_encode($d7_labels) ?>,
      datasets: [{
        label: 'Revenue (₹)',
        data: <?= json_encode($d7_rev) ?>,
        borderColor: '#D6A84B',
        backgroundColor: gradDash,
        borderWidth: 2,
        fill: true,
        tension: 0.35,
        pointBackgroundColor: '#00E5FF',
        pointBorderColor: '#D6A84B',
        pointRadius: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#151922',
          borderColor: 'rgba(214, 168, 75, 0.3)',
          borderWidth: 1,
          callbacks: {
            label: function(c) { return ' ₹' + c.parsed.y.toLocaleString(); }
          }
        }
      },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#8892b0', font: { size: 10 } } },
        y: {
          grid: { color: 'rgba(255,255,255,0.05)' },
          ticks: { color: '#8892b0', callback: function(v) { return '₹' + v; } }
        }
      }
    }
  });

  // 2. Dash Status Breakdown
  const ctxStatus = document.getElementById('dashStatusChart').getContext('2d');
  new Chart(ctxStatus, {
    type: 'doughnut',
    data: {
      labels: ['Confirmed', 'Pending', 'In Session', 'Completed', 'Cancelled'],
      datasets: [{
        data: [
          <?= $confirmed_bookings ?>,
          <?= $pending_bookings ?>,
          <?= $in_session_bookings ?>,
          <?= $completed_bookings ?>,
          <?= $cancelled_bookings ?>
        ],
        backgroundColor: ['#39FF88', '#FFC107', '#D6A84B', '#00E5FF', '#E63946'],
        borderWidth: 2,
        borderColor: '#151922'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom', labels: { color: '#F5F7FA', boxWidth: 10, font: { size: 10 } } }
      },
      cutout: '65%'
    }
  });
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
