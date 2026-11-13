<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * WhatsApp Dispatch Logger & Tracking Router
 * Automatically tracks delivery status, updates send counts, and redirects to WhatsApp Web/App
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();

$admin_user = current_admin();
$db = get_db();

$booking_id = (int)($_GET['id'] ?? 0);
$type = trim($_GET['type'] ?? 'confirmed');
$reason = trim($_GET['reason'] ?? '');
$return = trim($_GET['return'] ?? '');

if ($booking_id <= 0) {
    set_flash('danger', 'Invalid booking specified for WhatsApp dispatch.');
    redirect(BASE_URL . 'admin/bookings.php');
}

// Fetch booking with customer and station info
$stmt = $db->prepare("
    SELECT b.*, s.name AS station_name, s.code AS station_code,
           c.name AS category_name, cust.full_name AS customer_name, 
           cust.phone AS customer_phone, cust.email AS customer_email
    FROM bookings b
    JOIN stations s ON b.station_id = s.id
    JOIN station_categories c ON s.category_id = c.id
    JOIN customers cust ON b.customer_id = cust.id
    WHERE b.id = :id
");
$stmt->execute([':id' => $booking_id]);
$booking = $stmt->fetch();

if (!$booking) {
    set_flash('danger', 'Booking record not found.');
    redirect(BASE_URL . 'admin/bookings.php');
}

// Update WhatsApp Delivery Status in Database
$new_count = (int)($booking['whatsapp_send_count'] ?? 0) + 1;
try {
    $upd = $db->prepare("
        UPDATE bookings 
        SET whatsapp_status = 'sent',
            whatsapp_sent_at = NOW(),
            whatsapp_last_type = :type,
            whatsapp_send_count = whatsapp_send_count + 1,
            whatsapp_pending_reason = NULL
        WHERE id = :id
    ");
    $upd->execute([
        ':type' => $type,
        ':id'   => $booking_id
    ]);

    log_admin_activity(
        $admin_user['id'], 
        'whatsapp_sent', 
        "Dispatched {$type} WhatsApp to gamer {$booking['customer_name']} (+91{$booking['customer_phone']}) for #{$booking['booking_ref']}"
    );

} catch (Exception $e) {
    error_log('WhatsApp Log Error: ' . $e->getMessage());
}

// Generate the target WhatsApp URL with formatted message & 1-page receipt and PDF pass links
$extra = [];
if (!empty($reason)) {
    $extra['reason'] = $reason;
}
$wa_url = generate_whatsapp_url($type, $booking, $extra);
$pdf_pass_url = BASE_URL . 'receipt.php?ref=' . urlencode($booking['booking_ref']) . '&download=pdf';

// If direct redirect requested
if (isset($_GET['direct']) && $_GET['direct'] == '1') {
    header("Location: " . $wa_url);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>WhatsApp & PDF Pass Dispatch | IRONCLAD Sanctuary</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=Segoe+UI:wght@400;600;700&display=swap" rel="stylesheet">
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= ASSETS_URL ?>css/admin.css">
  <link rel="icon" type="image/png" href="<?= BASE_URL ?>images/NOBG_IRONCLADLOGO.png">
  
  <style>
    .brand-font { font-family: 'Rajdhani', sans-serif; text-transform: uppercase; letter-spacing: 0.05em; }
    .dispatch-card {
      background: #151922;
      border: 1.5px solid var(--gold);
      border-radius: 12px;
      box-shadow: 0 12px 40px rgba(0,0,0,0.7);
      max-width: 600px;
      width: 92%;
    }
  </style>
</head>
<body class="bg-dark text-light d-flex align-items-center justify-content-center p-3" style="min-height: 100vh; background: #090B10;">

  <div class="dispatch-card p-4 p-md-5 text-center">
    <!-- Authentic Logo -->
    <img src="<?= BASE_URL ?>images/NOBG_IRONCLADLOGO.png" alt="Ironclad Logo" style="height: 64px;" class="mb-3">
    
    <h3 class="text-gold brand-font mb-1">WhatsApp & PDF Pass Dispatch</h3>
    <p class="text-muted-iron small mb-4">
      Reservation <strong class="text-light">#<?= e($booking['booking_ref']) ?></strong> &bull; Gamer: <strong class="text-light"><?= e($booking['customer_name']) ?></strong> (+91 <?= e($booking['customer_phone']) ?>)
    </p>

    <!-- Delivery Status Confirmation Badge -->
    <div class="alert alert-success d-flex align-items-center justify-content-between p-3 mb-4" style="background: rgba(57, 255, 136, 0.12); border: 1.5px solid var(--rgb-green); color: #fff;">
      <div class="d-flex align-items-center gap-2 text-start">
        <i class="bi bi-check-circle-fill text-green fs-4"></i>
        <div>
          <strong class="text-green d-block">WhatsApp Dispatch Status: LOGGED</strong>
          <span class="small text-light">Booking updated in database (Total Dispatches: <?= $new_count ?>)</span>
        </div>
      </div>
      <span class="badge bg-dark border border-success text-green font-monospace"><?= date('h:i A') ?></span>
    </div>

    <!-- Primary Action Buttons -->
    <div class="d-grid gap-3 mb-4">
      <!-- 1. Open WhatsApp Chat -->
      <a href="<?= $wa_url ?>" target="_blank" rel="noopener noreferrer" class="btn btn-success btn-lg fw-bold py-3 shadow-sm d-flex align-items-center justify-content-center gap-2" id="btnOpenWA">
        <i class="bi bi-whatsapp fs-4"></i>
        <span>1. Open WhatsApp Chat & Send</span>
      </a>

      <!-- 2. Download PDF Pass to Attach -->
      <a href="<?= $pdf_pass_url ?>" target="_blank" class="btn btn-danger btn-lg fw-bold py-3 shadow-sm d-flex align-items-center justify-content-center gap-2" id="btnDownloadPDF">
        <i class="bi bi-file-earmark-pdf-fill fs-4"></i>
        <span>2. Download PDF Pass (To Attach in WhatsApp)</span>
      </a>
    </div>

    <!-- Helpful Attachment Instructions -->
    <div class="p-3 rounded text-start mb-4" style="background: rgba(0,0,0,0.35); border: 1px solid var(--border-color); font-size: 0.8rem;">
      <div class="text-cyan fw-bold mb-1"><i class="bi bi-info-circle-fill me-1"></i> Two Ways Gamer Receives Their PDF:</div>
      <ul class="mb-0 ps-3 text-muted-iron">
        <li class="mb-1"><strong>Via Message Link:</strong> The pre-filled WhatsApp message already includes a direct 1-click download link to their official 1-page PDF pass.</li>
        <li><strong>Via File Attachment:</strong> Click <strong>Download PDF Pass</strong> above to save the file to your computer, then drag & drop or attach it (📎) into your WhatsApp chat!</li>
      </ul>
    </div>

    <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-25">
      <a href="<?= BASE_URL ?>admin/bookings.php" class="btn btn-iron btn-iron-outline btn-iron-sm">
        <i class="bi bi-arrow-left me-1"></i> Return to Bookings
      </a>
      <a href="<?= BASE_URL ?>admin/booking-detail.php?id=<?= $booking['id'] ?>" class="btn btn-iron btn-iron-cyan btn-iron-sm">
        <i class="bi bi-receipt me-1"></i> View Booking Details
      </a>
    </div>
  </div>

  <script>
    // Optional: auto-trigger download if wanted
    document.getElementById('btnOpenWA').addEventListener('click', function() {
      // Small visual feedback
      this.classList.add('opacity-75');
    });
  </script>

</body>
</html>
<?php
exit;

