<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Reports & Performance Analytics with Interactive Chart.js Visualizations
 */

$admin_title = 'Reports & Esports Analytics';
require_once __DIR__ . '/includes/admin-header.php';

$db = get_db();

// 1. Overall Totals
$total_rev = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'verified'")->fetchColumn();
$total_sessions = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE status IN ('confirmed', 'completed', 'in_session')")->fetchColumn();
$total_customers = (int)$db->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$total_hours = (int)$db->query("SELECT COALESCE(SUM(duration_hours), 0) FROM bookings WHERE status IN ('confirmed', 'completed', 'in_session')")->fetchColumn();

// 2. Category Revenue Breakdown
$cat_revenue_stmt = $db->query("
    SELECT c.name AS category_name, COUNT(b.id) AS total_sessions, 
           COALESCE(SUM(b.final_amount), 0) AS total_revenue
    FROM station_categories c
    LEFT JOIN stations s ON s.category_id = c.id
    LEFT JOIN bookings b ON b.station_id = s.id AND b.status IN ('confirmed', 'completed', 'in_session')
    GROUP BY c.id
    ORDER BY total_revenue DESC
");
$cat_revenue = $cat_revenue_stmt->fetchAll();

// 3. Payment Method Breakdown
$pay_methods_stmt = $db->query("
    SELECT payment_method, COUNT(*) AS txn_count, COALESCE(SUM(amount), 0) AS total_amount
    FROM payments
    WHERE status = 'verified'
    GROUP BY payment_method
");
$pay_methods = $pay_methods_stmt->fetchAll();

// 4. Peak Booking Hours (10 AM to 11 PM)
$peak_hours_stmt = $db->query("
    SELECT HOUR(start_time) AS booking_hour, COUNT(*) AS session_count
    FROM bookings
    WHERE status != 'cancelled'
    GROUP BY booking_hour
    ORDER BY booking_hour ASC
");
$peak_hours = $peak_hours_stmt->fetchAll();

// 5. Daily Revenue Trend for the Past 14 Days
$daily_rev_stmt = $db->query("
    SELECT DATE(b.booking_date) AS b_date, 
           COALESCE(SUM(b.final_amount), 0) AS daily_rev, 
           COUNT(b.id) AS daily_count
    FROM bookings b
    WHERE b.booking_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
      AND b.status IN ('confirmed', 'completed', 'in_session')
    GROUP BY DATE(b.booking_date)
    ORDER BY b_date ASC
");
$daily_rev = $daily_rev_stmt->fetchAll();

// 6. Station Utilization (Top Most Booked Rigs)
$station_util_stmt = $db->query("
    SELECT s.name AS station_name, s.code, c.name AS category_name,
           COUNT(b.id) AS sessions_count,
           COALESCE(SUM(b.duration_hours), 0) AS total_hours,
           COALESCE(SUM(b.final_amount), 0) AS total_revenue
    FROM stations s
    JOIN station_categories c ON s.category_id = c.id
    LEFT JOIN bookings b ON b.station_id = s.id AND b.status IN ('confirmed', 'completed', 'in_session')
    GROUP BY s.id
    ORDER BY total_hours DESC
");
$station_util = $station_util_stmt->fetchAll();

// Prepare JSON data for Chart.js
$trend_labels = [];
$trend_revenue = [];
$trend_counts = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $trend_labels[] = date('d M', strtotime($d));
    $found = false;
    foreach ($daily_rev as $dr) {
        if ($dr['b_date'] === $d) {
            $trend_revenue[] = (float)$dr['daily_rev'];
            $trend_counts[] = (int)$dr['daily_count'];
            $found = true;
            break;
        }
    }
    if (!$found) {
        $trend_revenue[] = 0;
        $trend_counts[] = 0;
    }
}

$cat_labels = [];
$cat_values = [];
foreach ($cat_revenue as $cr) {
    $cat_labels[] = $cr['category_name'];
    $cat_values[] = (float)$cr['total_revenue'];
}

$hours_map = [];
for ($h = 10; $h <= 22; $h++) {
    $hours_map[$h] = 0;
}
foreach ($peak_hours as $ph) {
    $hr_int = (int)$ph['booking_hour'];
    if (isset($hours_map[$hr_int])) {
        $hours_map[$hr_int] = (int)$ph['session_count'];
    }
}
$hourly_labels = [];
$hourly_data = [];
foreach ($hours_map as $hr => $cnt) {
    $hourly_labels[] = date('g A', strtotime("{$hr}:00:00"));
    $hourly_data[] = $cnt;
}

$util_labels = [];
$util_hours = [];
foreach ($station_util as $su) {
    $util_labels[] = $su['name'];
    $util_hours[] = (int)$su['total_hours'];
}
?>

<!-- Metric Cards Row -->
<div class="row g-3 mb-4">
  <div class="col-xl-3 col-md-6">
    <div class="kpi-card" style="border-left: 4px solid var(--gold);">
      <div class="kpi-title">All-Time Verified Revenue</div>
      <div class="kpi-value text-gold"><?= format_currency($total_rev) ?></div>
      <div class="kpi-sub">Total verified collections via UPI & Cash</div>
      <i class="bi bi-wallet-fill kpi-icon"></i>
    </div>
  </div>

  <div class="col-xl-3 col-md-6">
    <div class="kpi-card" style="border-left: 4px solid var(--rgb-cyan);">
      <div class="kpi-title">Confirmed Game Sessions</div>
      <div class="kpi-value text-cyan"><?= number_format($total_sessions) ?></div>
      <div class="kpi-sub"><?= number_format($total_hours) ?> Total hours played in arena</div>
      <i class="bi bi-controller kpi-icon"></i>
    </div>
  </div>

  <div class="col-xl-3 col-md-6">
    <div class="kpi-card" style="border-left: 4px solid var(--rgb-green);">
      <div class="kpi-title">Unique Registered Gamers</div>
      <div class="kpi-value text-green"><?= number_format($total_customers) ?></div>
      <div class="kpi-sub">Gamer community base in Sri Muktsar Sahib</div>
      <i class="bi bi-people-fill kpi-icon"></i>
    </div>
  </div>

  <div class="col-xl-3 col-md-6">
    <div class="kpi-card" style="border-left: 4px solid var(--electric-red);">
      <div class="kpi-title">Total Gaming Stations</div>
      <div class="kpi-value text-red"><?= count($station_util) ?></div>
      <div class="kpi-sub">Active PC rigs, console lounges & squad bays</div>
      <i class="bi bi-display kpi-icon"></i>
    </div>
  </div>
</div>

<!-- Esports Charts Row 1: Revenue Trajectory & Category Share -->
<div class="row g-4 mb-4">
  <!-- Chart 1: 14-Day Revenue & Session Trajectory -->
  <div class="col-lg-8">
    <div class="admin-table-card p-4 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="h5 text-light brand-font mb-1">
            <i class="bi bi-graph-up-arrow text-gold me-2"></i>Revenue Trajectory (Past 14 Days)
          </h4>
          <span class="text-muted-iron small">Daily financial collections and session counts across the arena.</span>
        </div>
        <span class="badge bg-gold text-dark font-monospace fw-bold">14-Day Trend</span>
      </div>
      <div style="position: relative; height: 300px; width: 100%;">
        <canvas id="revenueTrendChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Chart 2: Category Revenue Breakdown -->
  <div class="col-lg-4">
    <div class="admin-table-card p-4 h-100">
      <h4 class="h5 text-light brand-font mb-1">
        <i class="bi bi-pie-chart-fill text-cyan me-2"></i>Category Revenue
      </h4>
      <span class="text-muted-iron small d-block mb-3">Platform revenue share</span>
      <div style="position: relative; height: 260px; width: 100%;">
        <canvas id="categoryShareChart"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Esports Charts Row 2: Peak Gaming Traffic & Station Utilization -->
<div class="row g-4 mb-4">
  <!-- Chart 3: Peak Hourly Arena Demand -->
  <div class="col-lg-6">
    <div class="admin-table-card p-4 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="h5 text-light brand-font mb-1">
            <i class="bi bi-clock-history text-cyan me-2"></i>Peak Gaming Traffic by Hour
          </h4>
          <span class="text-muted-iron small">Hourly distribution of gamer check-ins from 10 AM to 11 PM.</span>
        </div>
      </div>
      <div style="position: relative; height: 280px; width: 100%;">
        <canvas id="peakHoursChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Chart 4: Station Utilization (Hours Played) -->
  <div class="col-lg-6">
    <div class="admin-table-card p-4 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="h5 text-light brand-font mb-1">
            <i class="bi bi-bar-chart-fill text-green me-2"></i>Station Rig Utilization (Hours Booked)
          </h4>
          <span class="text-muted-iron small">Total hours deployed per gaming setup.</span>
        </div>
      </div>
      <div style="position: relative; height: 280px; width: 100%;">
        <canvas id="stationUtilChart"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Detailed Data Tables Row -->
<div class="row g-4">
  <!-- Station Rig Performance Table -->
  <div class="col-lg-8">
    <div class="admin-table-card p-4">
      <h4 class="h5 text-light mb-3 brand-font">Battle Station Utilization Matrix</h4>
      <div class="table-responsive">
        <table class="table table-dark align-middle">
          <thead>
            <tr>
              <th>Station / Platform</th>
              <th>Category</th>
              <th class="text-center">Sessions</th>
              <th class="text-center">Hours Played</th>
              <th class="text-end">Revenue Generated</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($station_util as $su): ?>
              <tr>
                <td>
                  <strong class="text-light"><?= e($su['name']) ?></strong>
                  <span class="text-muted-iron small font-monospace d-block"><?= e($su['code']) ?></span>
                </td>
                <td><span class="badge bg-secondary"><?= e($su['category_name']) ?></span></td>
                <td class="text-center text-cyan fw-bold"><?= $su['sessions_count'] ?></td>
                <td class="text-center text-green fw-bold"><?= $su['total_hours'] ?> hrs</td>
                <td class="text-end font-monospace text-gold fw-bold"><?= format_currency($su['total_revenue']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Payment Channels Breakdown -->
  <div class="col-lg-4">
    <div class="admin-table-card p-4">
      <h4 class="h5 text-light mb-3 brand-font">Payment Channels</h4>
      <div class="table-responsive">
        <table class="table table-dark align-middle">
          <thead>
            <tr>
              <th>Channel</th>
              <th>Txns</th>
              <th class="text-end">Amount</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pay_methods as $pm): ?>
              <tr>
                <td><strong class="text-light text-uppercase"><?= e($pm['payment_method']) ?></strong></td>
                <td><span class="badge bg-secondary"><?= $pm['txn_count'] ?></span></td>
                <td class="text-end font-monospace text-green fw-bold"><?= format_currency($pm['total_amount']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Chart.js Configuration Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Chart.js Dark Mode Defaults
  Chart.defaults.color = '#A0AEC0';
  Chart.defaults.font.family = "'Segoe UI', sans-serif";

  // 1. Revenue Trend Line Chart
  const ctxRev = document.getElementById('revenueTrendChart').getContext('2d');
  const gradientRev = ctxRev.createLinearGradient(0, 0, 0, 300);
  gradientRev.addColorStop(0, 'rgba(214, 168, 75, 0.45)');
  gradientRev.addColorStop(1, 'rgba(214, 168, 75, 0.0)');

  new Chart(ctxRev, {
    type: 'line',
    data: {
      labels: <?= json_encode($trend_labels) ?>,
      datasets: [{
        label: 'Revenue (₹)',
        data: <?= json_encode($trend_revenue) ?>,
        borderColor: '#D6A84B',
        backgroundColor: gradientRev,
        borderWidth: 2.5,
        fill: true,
        tension: 0.35,
        pointBackgroundColor: '#00E5FF',
        pointBorderColor: '#D6A84B',
        pointRadius: 4,
        pointHoverRadius: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#151922',
          titleColor: '#D6A84B',
          bodyColor: '#F5F7FA',
          borderColor: 'rgba(214,168,75,0.4)',
          borderWidth: 1,
          callbacks: {
            label: function(context) {
              return ' Revenue: ₹' + context.parsed.y.toLocaleString();
            }
          }
        }
      },
      scales: {
        x: {
          grid: { color: 'rgba(255, 255, 255, 0.05)' },
          ticks: { color: '#8892b0' }
        },
        y: {
          grid: { color: 'rgba(255, 255, 255, 0.05)' },
          ticks: {
            color: '#8892b0',
            callback: function(val) { return '₹' + val; }
          }
        }
      }
    }
  });

  // 2. Category Share Doughnut Chart
  const ctxCat = document.getElementById('categoryShareChart').getContext('2d');
  new Chart(ctxCat, {
    type: 'doughnut',
    data: {
      labels: <?= json_encode($cat_labels) ?>,
      datasets: [{
        data: <?= json_encode($cat_values) ?>,
        backgroundColor: ['#D6A84B', '#00E5FF', '#E63946', '#39FF88', '#B026FF'],
        borderWidth: 2,
        borderColor: '#151922'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: { color: '#F5F7FA', boxWidth: 12, font: { size: 11 } }
        },
        tooltip: {
          backgroundColor: '#151922',
          borderColor: 'rgba(255,255,255,0.1)',
          borderWidth: 1,
          callbacks: {
            label: function(context) {
              return ' ' + context.label + ': ₹' + context.parsed.toLocaleString();
            }
          }
        }
      },
      cutout: '65%'
    }
  });

  // 3. Peak Hourly Demand Bar Chart
  const ctxPeak = document.getElementById('peakHoursChart').getContext('2d');
  new Chart(ctxPeak, {
    type: 'bar',
    data: {
      labels: <?= json_encode($hourly_labels) ?>,
      datasets: [{
        label: 'Sessions Volume',
        data: <?= json_encode($hourly_data) ?>,
        backgroundColor: 'rgba(0, 229, 255, 0.7)',
        borderColor: '#00E5FF',
        borderWidth: 1,
        borderRadius: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#151922',
          borderColor: 'rgba(0, 229, 255, 0.4)',
          borderWidth: 1
        }
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { color: '#8892b0', font: { size: 10 } }
        },
        y: {
          grid: { color: 'rgba(255, 255, 255, 0.05)' },
          ticks: { stepSize: 1, color: '#8892b0' }
        }
      }
    }
  });

  // 4. Station Utilization Horizontal Bar Chart
  const ctxUtil = document.getElementById('stationUtilChart').getContext('2d');
  new Chart(ctxUtil, {
    type: 'bar',
    data: {
      labels: <?= json_encode($util_labels) ?>,
      datasets: [{
        label: 'Hours Booked',
        data: <?= json_encode($util_hours) ?>,
        backgroundColor: 'rgba(57, 255, 136, 0.65)',
        borderColor: '#39FF88',
        borderWidth: 1,
        borderRadius: 4
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#151922',
          borderColor: 'rgba(57, 255, 136, 0.4)',
          borderWidth: 1,
          callbacks: {
            label: function(c) { return ' ' + c.parsed.x + ' hours deployed'; }
          }
        }
      },
      scales: {
        x: {
          grid: { color: 'rgba(255, 255, 255, 0.05)' },
          ticks: { color: '#8892b0' }
        },
        y: {
          grid: { display: false },
          ticks: { color: '#F5F7FA', font: { size: 10 } }
        }
      }
    }
  });
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
