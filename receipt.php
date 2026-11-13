<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Official 1-Page Digital Receipt & Entry Pass
 * Optimized for Single-Page Printing & High-Impact Visuals
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$db = get_db();
$ref = strtoupper(trim($_GET['ref'] ?? ''));
$id = (int)($_GET['id'] ?? 0);
$phone = trim($_GET['phone'] ?? '');
$clean_phone = clean_indian_phone($phone);
$auto_print = isset($_GET['print']) && $_GET['print'] == '1';

$booking = null;
$payment = null;

if (!empty($ref)) {
    $stmt = $db->prepare("
        SELECT b.*, s.name AS station_name, s.code AS station_code, s.hourly_rate,
               c.name AS category_name, cust.full_name AS customer_name, 
               cust.phone AS customer_phone, cust.email AS customer_email
        FROM bookings b
        JOIN stations s ON b.station_id = s.id
        JOIN station_categories c ON s.category_id = c.id
        JOIN customers cust ON b.customer_id = cust.id
        WHERE b.booking_ref = :ref
    ");
    $stmt->execute([':ref' => $ref]);
    $booking = $stmt->fetch();
} elseif ($id > 0) {
    $stmt = $db->prepare("
        SELECT b.*, s.name AS station_name, s.code AS station_code, s.hourly_rate,
               c.name AS category_name, cust.full_name AS customer_name, 
               cust.phone AS customer_phone, cust.email AS customer_email
        FROM bookings b
        JOIN stations s ON b.station_id = s.id
        JOIN station_categories c ON s.category_id = c.id
        JOIN customers cust ON b.customer_id = cust.id
        WHERE b.id = :id
    ");
    $stmt->execute([':id' => $id]);
    $booking = $stmt->fetch();
}

if (!$booking) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <title>Receipt Not Found | <?= e(get_brand_short()) ?></title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
      <link rel="stylesheet" href="<?= ASSETS_URL ?>css/style.css">
    </head>
    <body class="bg-dark text-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">
      <div class="text-center p-5 rounded" style="background: #151922; border: 1px solid rgba(214,168,75,0.3); max-width: 480px;">
        <img src="<?= get_brand_logo() ?>" alt="Logo" style="height: 60px;" class="mb-3">
        <h3 class="text-gold">Receipt Not Found</h3>
        <p class="text-muted small">No valid booking reference was provided or the reservation does not exist.</p>
        <a href="<?= BASE_URL ?>check-booking.php" class="btn btn-warning btn-sm">Lookup Booking</a>
      </div>
    </body>
    </html>
    <?php
    exit;
}

// Fetch latest payment record
$stmt_pay = $db->prepare("SELECT * FROM payments WHERE booking_id = :bid ORDER BY id DESC LIMIT 1");
$stmt_pay->execute([':bid' => $booking['id']]);
$payment = $stmt_pay->fetch();

// Verification URL for dynamic QR code
$verify_url = BASE_URL . 'check-booking.php?ref=' . urlencode($booking['booking_ref']) . '&phone=' . urlencode($booking['customer_phone']);
$qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&margin=0&data=" . urlencode($verify_url);

// Receipt Number and Timestamp
$receipt_no = 'REC-' . date('Ymd', strtotime($booking['created_at'])) . '-' . str_pad((string)$booking['id'], 4, '0', STR_PAD_LEFT);
$issued_time = date('d M Y, h:i A', strtotime($booking['created_at']));

$brand_name    = get_brand_name();
$brand_short   = get_brand_short();
$brand_tagline = get_brand_tagline();
$brand_address = get_brand_address();
$brand_city    = get_brand_city();
$brand_phone   = get_brand_phone();
$brand_email   = get_brand_email();
$brand_logo    = get_brand_logo();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Receipt #<?= e($booking['booking_ref']) ?> | <?= e($brand_name) ?></title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=Segoe+UI:wght@400;600;700&display=swap" rel="stylesheet">
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="icon" type="image/png" href="<?= $brand_logo ?>">

  <!-- html2pdf.js for 1-Click Instant High-Res PDF Pass Generation -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

  <style>
    :root {
      --bg-dark: #090B10;
      --card-dark: #121620;
      --gold: #D6A84B;
      --gold-dark: #A87E2B;
      --cyan: #00E5FF;
      --green: #2ecc71;
      --red: #e74c3c;
      --border-dark: #222938;
      --text-muted: #8892b0;
    }

    body {
      background-color: var(--bg-dark);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: #F5F7FA;
      margin: 0;
      padding: 20px 10px;
      -webkit-font-smoothing: antialiased;
    }

    .brand-font {
      font-family: 'Rajdhani', sans-serif;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    /* Screen Action Header Bar */
    .action-bar {
      max-width: 800px;
      margin: 0 auto 16px auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 10px;
    }

    /* Receipt Box Styling */
    .receipt-wrapper {
      max-width: 800px;
      margin: 0 auto;
      background: #ffffff;
      color: #1a1e24;
      border-radius: 12px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.6);
      border: 2px solid var(--gold);
      position: relative;
      overflow: hidden;
      padding: 28px 32px;
    }

    /* Subtle watermark logo in background */
    .receipt-watermark {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 320px;
      opacity: 0.035;
      pointer-events: none;
      z-index: 0;
    }

    .receipt-content {
      position: relative;
      z-index: 1;
    }

    /* Header Section */
    .receipt-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 2px solid #e2e8f0;
      padding-bottom: 16px;
      margin-bottom: 16px;
    }

    .receipt-logo {
      height: 68px;
      width: auto;
      object-fit: contain;
    }

    .brand-title {
      font-size: 1.55rem;
      font-weight: 800;
      color: #0f172a;
      line-height: 1.1;
      letter-spacing: 0.08em;
    }

    .brand-tagline {
      font-size: 0.75rem;
      font-weight: 700;
      color: var(--gold-dark);
      text-transform: uppercase;
      letter-spacing: 0.15em;
    }

    .brand-address {
      font-size: 0.72rem;
      color: #64748b;
      line-height: 1.35;
      margin-top: 3px;
      max-width: 320px;
    }

    .receipt-meta {
      text-align: right;
    }

    .receipt-badge-title {
      display: inline-block;
      background: #0f172a;
      color: var(--gold);
      font-weight: 700;
      font-size: 0.75rem;
      padding: 4px 12px;
      border-radius: 4px;
      letter-spacing: 0.08em;
      margin-bottom: 6px;
    }

    .receipt-meta-item {
      font-size: 0.75rem;
      color: #64748b;
      line-height: 1.3;
    }

    .receipt-meta-item strong {
      color: #1e293b;
    }

    /* Reference & Status Bar */
    .status-ribbon {
      background: #f8fafc;
      border: 1px solid #cbd5e1;
      border-left: 5px solid var(--gold);
      border-radius: 6px;
      padding: 10px 16px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
    }

    .ref-title {
      font-size: 0.72rem;
      text-transform: uppercase;
      font-weight: 700;
      color: #64748b;
      letter-spacing: 0.05em;
    }

    .ref-number {
      font-size: 1.35rem;
      font-weight: 800;
      color: #0f172a;
      letter-spacing: 0.06em;
      font-family: 'Rajdhani', monospace;
    }

    .status-pill {
      font-weight: 700;
      font-size: 0.8rem;
      padding: 5px 14px;
      border-radius: 20px;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .status-confirmed {
      background: #dcfce7;
      color: #15803d;
      border: 1px solid #86efac;
    }

    .status-pending {
      background: #fef9c3;
      color: #a16207;
      border: 1px solid #fde047;
    }

    .status-in_session {
      background: #e0f2fe;
      color: #0369a1;
      border: 1px solid #7dd3fc;
    }

    .status-completed {
      background: #f1f5f9;
      color: #475569;
      border: 1px solid #cbd5e1;
    }

    /* 2-Column Info Grid */
    .info-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      margin-bottom: 16px;
    }

    .info-card {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      padding: 12px 14px;
    }

    .info-card-header {
      font-size: 0.72rem;
      font-weight: 800;
      color: var(--gold-dark);
      text-transform: uppercase;
      letter-spacing: 0.08em;
      margin-bottom: 8px;
      border-bottom: 1px solid #e2e8f0;
      padding-bottom: 4px;
    }

    .spec-row {
      display: flex;
      justify-content: space-between;
      font-size: 0.78rem;
      margin-bottom: 4px;
      line-height: 1.35;
    }

    .spec-label {
      color: #64748b;
    }

    .spec-val {
      font-weight: 700;
      color: #0f172a;
      text-align: right;
    }

    /* Financial Itemized Table */
    .receipt-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 14px;
      font-size: 0.8rem;
    }

    .receipt-table th {
      background: #0f172a;
      color: #ffffff;
      font-weight: 700;
      text-transform: uppercase;
      font-size: 0.72rem;
      letter-spacing: 0.05em;
      padding: 8px 12px;
    }

    .receipt-table td {
      padding: 8px 12px;
      border-bottom: 1px solid #e2e8f0;
      color: #1e293b;
    }

    .total-bar {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      padding: 10px 14px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
    }

    .pay-details {
      font-size: 0.75rem;
      color: #64748b;
      line-height: 1.35;
    }

    .pay-details strong {
      color: #0f172a;
    }

    .total-amount-box {
      text-align: right;
    }

    .total-label {
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      color: #64748b;
    }

    .total-value {
      font-size: 1.45rem;
      font-weight: 900;
      color: #0f172a;
      font-family: 'Rajdhani', sans-serif;
      line-height: 1;
    }

    /* Guidelines / Terms */
    .guidelines-box {
      background: #fffbeb;
      border: 1px solid #fef3c7;
      border-radius: 6px;
      padding: 8px 12px;
      margin-bottom: 16px;
      font-size: 0.72rem;
      color: #92400e;
      line-height: 1.35;
    }

    .guidelines-title {
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 3px;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    /* Footer Stamp & QR */
    .receipt-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-top: 1px dashed #cbd5e1;
      padding-top: 12px;
      font-size: 0.72rem;
    }

    .qr-block {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .qr-img {
      width: 58px;
      height: 58px;
      border: 1px solid #cbd5e1;
      border-radius: 4px;
      padding: 2px;
      background: #fff;
    }

    .qr-text {
      color: #64748b;
      font-size: 0.68rem;
      line-height: 1.25;
      max-width: 170px;
    }

    .stamp-block {
      text-align: right;
    }

    .stamp-badge {
      display: inline-block;
      border: 2px solid #16a34a;
      color: #16a34a;
      font-weight: 800;
      text-transform: uppercase;
      font-size: 0.68rem;
      padding: 2px 8px;
      border-radius: 4px;
      letter-spacing: 0.08em;
      margin-bottom: 3px;
    }

    .stamp-text {
      color: #64748b;
      font-size: 0.68rem;
    }

    /* Table responsive wrapper */
    .table-wrapper {
      width: 100%;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      margin-bottom: 14px;
    }

    /* ===================================================
       MOBILE RESPONSIVE STYLES (Screen widths <= 680px)
       Flawless presentation on all mobile devices & tablets
       =================================================== */
    @media screen and (max-width: 680px) {
      body {
        padding: 10px 8px;
      }

      .action-bar {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
        margin-bottom: 12px;
      }

      .action-bar-nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
      }

      .action-bar-buttons {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 6px;
        width: 100%;
      }

      .action-bar-buttons .btn {
        width: 100%;
        padding: 8px 10px;
        font-size: 0.78rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        white-space: nowrap;
      }

      .receipt-wrapper {
        padding: 18px 14px;
        border-radius: 8px;
        border-width: 1.5px;
      }

      .receipt-watermark {
        width: 200px;
        opacity: 0.03;
      }

      /* Stack Header vertically */
      .receipt-header {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
        padding-bottom: 12px;
        margin-bottom: 12px;
      }

      .receipt-brand-box {
        display: flex;
        align-items: center;
        gap: 12px;
      }

      .receipt-logo {
        height: 52px;
      }

      .brand-title {
        font-size: 1.25rem;
      }

      .brand-tagline {
        font-size: 0.7rem;
      }

      .brand-address {
        font-size: 0.7rem;
        max-width: 100%;
      }

      .receipt-meta {
        text-align: left;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 8px 12px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4px 8px;
      }

      .receipt-badge-title {
        grid-column: 1 / -1;
        text-align: center;
        margin-bottom: 4px;
        font-size: 0.7rem;
      }

      .receipt-meta-item {
        font-size: 0.72rem;
      }

      /* Stack Reference & Status Ribbon */
      .status-ribbon {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
        padding: 10px 12px;
        margin-bottom: 12px;
      }

      .ref-number {
        font-size: 1.25rem;
      }

      .status-pill {
        font-size: 0.74rem;
        padding: 4px 10px;
        align-self: flex-start;
      }

      /* Convert 2-column info grid to 1 vertical stack */
      .info-grid {
        grid-template-columns: 1fr;
        gap: 10px;
        margin-bottom: 12px;
      }

      .info-card {
        padding: 10px 12px;
      }

      .spec-row {
        font-size: 0.76rem;
      }

      /* Table responsive wrapper & legible typography */
      .table-wrapper {
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        margin-bottom: 12px;
      }

      .receipt-table {
        margin-bottom: 0;
        min-width: 480px; /* guarantees columns don't crunch together */
      }

      .receipt-table th {
        font-size: 0.7rem;
        padding: 7px 10px;
      }

      .receipt-table td {
        font-size: 0.75rem;
        padding: 7px 10px;
      }

      /* Total Amount & Payment Bar */
      .total-bar {
        flex-direction: column-reverse;
        align-items: stretch;
        gap: 10px;
        padding: 10px 12px;
        margin-bottom: 12px;
      }

      .total-amount-box {
        text-align: left;
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        border-bottom: 1px dashed #cbd5e1;
        padding-bottom: 8px;
      }

      .total-value {
        font-size: 1.4rem;
      }

      .pay-details {
        font-size: 0.72rem;
      }

      /* Guidelines */
      .guidelines-box {
        padding: 8px 10px;
        font-size: 0.7rem;
        margin-bottom: 12px;
      }

      /* Receipt Footer */
      .receipt-footer {
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 12px;
        padding-top: 10px;
      }

      .qr-block {
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 6px;
      }

      .qr-img {
        width: 72px;
        height: 72px;
      }

      .qr-text {
        max-width: 100%;
        font-size: 0.7rem;
      }

      .stamp-block {
        text-align: center;
      }
    }

    @media screen and (max-width: 420px) {
      .receipt-meta {
        grid-template-columns: 1fr;
      }
      .action-bar-buttons {
        grid-template-columns: 1fr;
      }
      .receipt-brand-box {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
      }
    }

    /* ===================================================
       STRICT 1-PAGE PRINT STYLES
       Guaranteed to fit on exactly 1 single page!
       =================================================== */
    @page {
      size: A4 portrait;
      margin: 8mm 10mm;
    }

    @media print {
      body {
        background: #ffffff !important;
        color: #000000 !important;
        margin: 0 !important;
        padding: 0 !important;
      }

      .no-print {
        display: none !important;
      }

      .receipt-wrapper {
        border: 1.5px solid #0f172a !important;
        box-shadow: none !important;
        padding: 16px 20px !important;
        margin: 0 !important;
        max-width: 100% !important;
        border-radius: 0 !important;
        page-break-inside: avoid !important;
        page-break-after: avoid !important;
      }

      .receipt-watermark {
        opacity: 0.04 !important;
      }

      .receipt-table th {
        background: #0f172a !important;
        color: #ffffff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      .status-confirmed, .status-pending, .status-in_session {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      .brand-title {
        color: #000 !important;
      }
    }
  </style>
</head>
<body>

  <!-- Screen Action Bar (Hidden when printed) -->
  <div class="action-bar no-print">
    <div class="action-bar-nav d-flex align-items-center gap-2">
      <a href="<?= BASE_URL ?>check-booking.php?ref=<?= urlencode($booking['booking_ref']) ?>&phone=<?= urlencode($booking['customer_phone']) ?>" class="btn btn-outline-secondary btn-sm text-light">
        <i class="bi bi-arrow-left me-1"></i> Back to Portal
      </a>
      <span class="text-muted-iron small d-none d-sm-inline">Official 1-Page Arena Pass</span>
    </div>

    <div class="action-bar-buttons d-flex align-items-center gap-2">
      <?php if (!$payment || $payment['status'] !== 'verified'): ?>
        <a href="<?= BASE_URL ?>check-booking.php?ref=<?= urlencode($booking['booking_ref']) ?>&pay=razorpay" class="btn btn-warning btn-sm fw-bold text-dark px-3 shadow-sm">
          <i class="bi bi-lightning-charge-fill me-1"></i> Pay Now with Razorpay
        </a>
      <?php endif; ?>
      <?php $wa_pass = generate_whatsapp_url('confirmed', $booking); ?>
      <button onclick="downloadPDFPass()" id="btnDownloadPDF" class="btn btn-danger btn-sm fw-bold px-3 shadow-sm">
        <i class="bi bi-file-earmark-pdf-fill me-1"></i> Download PDF Pass
      </button>
      <a href="<?= $wa_pass ?>" target="_blank" rel="noopener noreferrer" class="btn btn-success btn-sm fw-bold shadow-sm">
        <i class="bi bi-whatsapp me-1"></i> Share on WhatsApp
      </a>
      <button onclick="window.print()" class="btn btn-warning btn-sm fw-bold text-dark px-3 shadow-sm">
        <i class="bi bi-printer-fill me-1"></i> Print
      </button>
    </div>
  </div>

  <!-- Main Single-Page Receipt Document -->
  <div class="receipt-wrapper">
    <!-- Watermark Logo -->
    <img src="<?= $brand_logo ?>" alt="Watermark" class="receipt-watermark">

    <div class="receipt-content">
      
      <!-- 1. Header with Authentic Logo -->
      <div class="receipt-header">
        <div class="receipt-brand-box d-flex align-items-center gap-3">
          <img src="<?= $brand_logo ?>" alt="<?= e($brand_name) ?> Logo" class="receipt-logo">
          <div>
            <div class="brand-title brand-font"><?= e($brand_name) ?></div>
            <div class="brand-tagline"><?= e($brand_tagline) ?></div>
            <div class="brand-address">
              <?= e($brand_address) ?><br>
              <?php if (!empty($brand_phone)): ?><strong>Helpline:</strong> <?= e($brand_phone) ?> &bull; <?php endif; ?><strong>Email:</strong> <?= e($brand_email) ?>
            </div>
          </div>
        </div>

        <div class="receipt-meta">
          <div class="receipt-badge-title brand-font">E-RECEIPT & ENTRY PASS</div>
          <div class="receipt-meta-item">Receipt No: <strong><?= e($receipt_no) ?></strong></div>
          <div class="receipt-meta-item">Issued: <strong><?= e($issued_time) ?></strong></div>
          <div class="receipt-meta-item">Channel: <strong class="text-uppercase"><?= e($booking['booking_type']) ?></strong></div>
        </div>
      </div>

      <!-- 2. Booking Reference & Live Status Ribbon -->
      <div class="status-ribbon">
        <div>
          <div class="ref-title">Booking Access Reference</div>
          <div class="ref-number">#<?= e($booking['booking_ref']) ?></div>
        </div>

        <div>
          <?php
            $st = $booking['status'];
            $pill_class = match($st) {
              'confirmed' => 'status-confirmed',
              'in_session' => 'status-in_session',
              'completed' => 'status-completed',
              default => 'status-pending'
            };
            $pill_label = match($st) {
              'confirmed' => '<i class="bi bi-patch-check-fill"></i> CONFIRMED & PAID',
              'in_session' => '<i class="bi bi-controller"></i> IN SESSION ACTIVE',
              'completed' => '<i class="bi bi-check-all"></i> COMPLETED',
              default => '<i class="bi bi-hourglass-split"></i> PENDING VERIFICATION'
            };
          ?>
          <span class="status-pill <?= $pill_class ?>"><?= $pill_label ?></span>
        </div>
      </div>

      <!-- 3. Two-Column Specification: Gamer Details & Rig Allocation -->
      <div class="info-grid">
        <!-- Gamer Details -->
        <div class="info-card">
          <div class="info-card-header brand-font">Gamer Information</div>
          <div class="spec-row">
            <span class="spec-label">Player Name:</span>
            <span class="spec-val"><?= e($booking['customer_name']) ?></span>
          </div>
          <div class="spec-row">
            <span class="spec-label">Contact Phone:</span>
            <span class="spec-val">+91 <?= e($booking['customer_phone']) ?></span>
          </div>
          <div class="spec-row">
            <span class="spec-label">Email ID:</span>
            <span class="spec-val"><?= e($booking['customer_email'] ?: 'Counter Walk-In') ?></span>
          </div>
          <div class="spec-row">
            <span class="spec-label">Booking Channel:</span>
            <span class="spec-val text-uppercase"><?= e($booking['booking_type']) ?> Counter</span>
          </div>
        </div>

        <!-- Station & Timings -->
        <div class="info-card">
          <div class="info-card-header brand-font">Battle Station Allocation</div>
          <div class="spec-row">
            <span class="spec-label">Assigned Rig:</span>
            <span class="spec-val"><?= e($booking['station_name']) ?> (<?= e($booking['station_code']) ?>)</span>
          </div>
          <div class="spec-row">
            <span class="spec-label">Category:</span>
            <span class="spec-val"><?= e($booking['category_name']) ?></span>
          </div>
          <div class="spec-row">
            <span class="spec-label">Game Date:</span>
            <span class="spec-val"><?= date('l, d M Y', strtotime($booking['booking_date'])) ?></span>
          </div>
          <div class="spec-row">
            <span class="spec-label">Reserved Slot:</span>
            <span class="spec-val text-primary"><?= date('g:i A', strtotime($booking['start_time'])) ?> – <?= date('g:i A', strtotime($booking['end_time'])) ?> (<?= $booking['duration_hours'] ?>h)</span>
          </div>
        </div>
      </div>

      <!-- 4. Itemized Financial Table -->
      <div class="table-wrapper">
        <table class="receipt-table">
          <thead>
            <tr>
              <th style="width: 50%;">Description / Gaming Service</th>
              <th class="text-center" style="width: 15%;">Rate / Hr</th>
              <th class="text-center" style="width: 15%;">Duration</th>
              <th class="text-end" style="width: 20%;">Amount</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                <strong>Station Rental: <?= e($booking['station_name']) ?></strong>
                <div style="font-size: 0.7rem; color: #64748b;">High-refresh esport monitor, RTX gaming rig, mechanical peripherals</div>
              </td>
              <td class="text-center">₹<?= number_format((float)$booking['hourly_rate'], 0) ?></td>
              <td class="text-center"><?= $booking['duration_hours'] ?> Hour(s)</td>
              <td class="text-end font-monospace">₹<?= number_format((float)$booking['total_amount'], 2) ?></td>
            </tr>
            <?php if ((float)$booking['discount_amount'] > 0): ?>
              <tr>
                <td><span class="text-success fw-bold">Promotional Discount Applied</span></td>
                <td class="text-center">-</td>
                <td class="text-center">-</td>
                <td class="text-end font-monospace text-success">-₹<?= number_format((float)$booking['discount_amount'], 2) ?></td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- 5. Payment Details & Total Amount Bar -->
      <div class="total-bar">
        <div class="pay-details">
          <div>Payment Method: 
            <strong class="text-uppercase">
              <?php if (($payment['payment_method'] ?? '') === 'razorpay'): ?>
                Razorpay Online Gateway
              <?php else: ?>
                <?= e($payment['payment_method'] ?? 'Counter Cash / UPI') ?>
              <?php endif; ?>
            </strong>
          </div>
          <div>Transaction / Ref ID: 
            <strong class="font-monospace"><?= e($payment['razorpay_payment_id'] ?? $payment['upi_transaction_id'] ?? 'VERIFIED AT COUNTER') ?></strong>
          </div>
          <div>Verification: 
            <strong class="text-success">
              <i class="bi bi-shield-check"></i> 
              <?php if ($payment && $payment['status'] === 'verified'): ?>
                <?= (($payment['payment_method'] ?? '') === 'razorpay') ? 'VERIFIED VIA RAZORPAY GATEWAY' : 'VERIFIED BY ADMIN' ?>
              <?php else: ?>
                PENDING / AUTHORIZED ENTRY
              <?php endif; ?>
            </strong>
          </div>
        </div>

        <div class="total-amount-box">
          <div class="total-label">Net Payable Amount</div>
          <div class="total-value">₹<?= number_format((float)$booking['final_amount'], 2) ?></div>
        </div>
      </div>

      <!-- 6. Compact Arena Entry Guidelines -->
      <div class="guidelines-box">
        <div class="guidelines-title">
          <i class="bi bi-info-circle-fill"></i> Sanctuary Arena Access Guidelines
        </div>
        <div>
          &bull; Present this pass or booking ref <strong>#<?= e($booking['booking_ref']) ?></strong> at reception for seat check-in.<br>
          &bull; Arrive 5–10 minutes early; session timer starts promptly at <?= date('g:i A', strtotime($booking['start_time'])) ?>.<br>
          &bull; Outside food is allowed! Chilled beverage cans and packed snacks are available on-site at reception. Please handle peripherals with care.
        </div>
      </div>

      <!-- 7. Footer: Scannable Verification QR Code & Digital Stamp -->
      <div class="receipt-footer">
        <div class="qr-block">
          <img src="<?= $qr_api_url ?>" alt="Verification QR" class="qr-img">
          <div class="qr-text">
            <strong>Scan to Verify Pass</strong><br>
            Point phone camera to verify reservation status live on the sanctuary server.
          </div>
        </div>

        <div class="stamp-block">
          <div class="stamp-badge">
            <i class="bi bi-shield-fill-check"></i> OFFICIAL ENTRY PASS
          </div>
          <div class="stamp-text">
            <?= e($brand_short) ?> SANCTUARY COUNTER<br>
            <?= e($brand_city) ?> &bull; GG!
          </div>
        </div>
      </div>

    </div>
  </div>

  <script>
    function downloadPDFPass() {
      const btn = document.getElementById('btnDownloadPDF');
      const originalText = btn ? btn.innerHTML : '';
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Generating PDF...';
      }

      const element = document.querySelector('.receipt-wrapper');
      const opt = {
        margin:       [8, 10, 8, 10],
        filename:     '<?= e($brand_short) ?>_PASS_<?= e($booking['booking_ref']) ?>.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true, logging: false },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
      };

      if (typeof html2pdf !== 'undefined') {
        html2pdf().set(opt).from(element).save().then(function() {
          if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> PDF Downloaded!';
            setTimeout(function() { btn.innerHTML = originalText; }, 3000);
          }
        }).catch(function(err) {
          console.error('html2pdf error:', err);
          if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalText;
          }
          window.print();
        });
      } else {
        window.print();
      }
    }

    <?php if (isset($_GET['download']) && in_array(strtolower($_GET['download']), ['pdf', '1', 'true'])): ?>
      window.addEventListener('load', function() {
        setTimeout(downloadPDFPass, 600);
      });
    <?php endif; ?>

    <?php if ($auto_print): ?>
      window.addEventListener('load', function() {
        setTimeout(function() { window.print(); }, 400);
      });
    <?php endif; ?>
  </script>

</body>
</html>

