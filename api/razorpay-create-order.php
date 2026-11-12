<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Razorpay Order Creation API Endpoint
 * Accepts booking reference, generates Razorpay order, and returns configuration for Checkout modal.
 */

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

// Read JSON input or POST form fields
$raw_input = file_get_contents('php://input');
$input_data = [];
if (!empty($raw_input)) {
    $decoded = json_decode($raw_input, true);
    if (is_array($decoded)) {
        $input_data = $decoded;
    }
}
$booking_ref = trim($input_data['booking_ref'] ?? $_POST['booking_ref'] ?? '');
$csrf_token  = trim($input_data['csrf_token'] ?? $_POST['csrf_token'] ?? '');

// Verify CSRF
if (!verify_csrf($csrf_token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid or expired security token. Please refresh the page.']);
    exit;
}

if (!is_razorpay_enabled()) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Razorpay online payment is currently disabled. Please use the UPI QR scanner.']);
    exit;
}

if (empty($booking_ref)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing booking reference.']);
    exit;
}

$db = get_db();

try {
    $stmt = $db->prepare("
        SELECT b.*, s.name AS station_name, s.code AS station_code,
               c.name AS category_name, cust.full_name AS customer_name,
               cust.phone AS customer_phone, cust.email AS customer_email
        FROM bookings b
        JOIN stations s ON b.station_id = s.id
        JOIN station_categories c ON s.category_id = c.id
        JOIN customers cust ON b.customer_id = cust.id
        WHERE b.booking_ref = :ref
        LIMIT 1
    ");
    $stmt->execute([':ref' => $booking_ref]);
    $booking = $stmt->fetch();

    if (!$booking) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Booking reference not found.']);
        exit;
    }

    if (in_array($booking['status'], ['confirmed', 'in_session', 'completed'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'error'   => 'This reservation is already confirmed and verified.',
            'redirect' => BASE_URL . 'receipt.php?ref=' . urlencode($booking['booking_ref'])
        ]);
        exit;
    }

    if (in_array($booking['status'], ['cancelled', 'rejected'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'This reservation was ' . $booking['status'] . ' and cannot receive payment.']);
        exit;
    }

    $final_amount = (float)$booking['final_amount'];
    if ($final_amount <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Payable amount cannot be zero.']);
        exit;
    }

    $brand_name  = get_brand_name();
    $brand_short = get_brand_short();
    $logo_url    = get_brand_logo();

    $notes = [
        'booking_ref'   => $booking['booking_ref'],
        'customer_name' => $booking['customer_name'],
        'customer_phone'=> $booking['customer_phone'],
        'station'       => $booking['station_name'],
        'date'          => $booking['booking_date'],
        'time'          => $booking['start_time']
    ];

    $res = razorpay_create_order($final_amount, $booking['booking_ref'], $notes);

    if (!$res['success'] || empty($res['order']['id'])) {
        http_response_code(502);
        echo json_encode([
            'success' => false,
            'error'   => $res['error'] ?? 'Unable to generate Razorpay order. Please try again or use the manual UPI QR code.'
        ]);
        exit;
    }

    $order = $res['order'];

    // Upsert payment row with the generated order ID
    $stmt_pay = $db->prepare("SELECT id FROM payments WHERE booking_id = :bid ORDER BY id DESC LIMIT 1");
    $stmt_pay->execute([':bid' => $booking['id']]);
    $existing_pay = $stmt_pay->fetch();

    if ($existing_pay) {
        $upd_p = $db->prepare("
            UPDATE payments 
            SET razorpay_order_id = :oid,
                payment_method = 'razorpay'
            WHERE id = :pid
        ");
        $upd_p->execute([
            ':oid' => $order['id'],
            ':pid' => $existing_pay['id']
        ]);
    } else {
        $ins_p = $db->prepare("
            INSERT INTO payments (booking_id, amount, payment_method, razorpay_order_id, status)
            VALUES (:bid, :amt, 'razorpay', :oid, 'pending')
        ");
        $ins_p->execute([
            ':bid' => $booking['id'],
            ':amt' => $final_amount,
            ':oid' => $order['id']
        ]);
    }

    // Return Checkout Modal Options
    echo json_encode([
        'success'     => true,
        'key_id'      => get_razorpay_key_id(),
        'order_id'    => $order['id'],
        'amount'      => $order['amount'],
        'currency'    => $order['currency'] ?? 'INR',
        'name'        => $brand_name,
        'description' => $booking['station_name'] . ' (' . (float)$booking['duration_hours'] . ' hr) - #' . $booking['booking_ref'],
        'image'       => $logo_url,
        'prefill'     => [
            'name'    => $booking['customer_name'],
            'contact' => $booking['customer_phone'],
            'email'   => $booking['customer_email'] ?: ''
        ],
        'notes'       => [
            'booking_ref' => $booking['booking_ref'],
            'station'     => $booking['station_name']
        ],
        'theme'       => [
            'color' => '#D6A84B'
        ]
    ]);
    exit;

} catch (\Throwable $e) {
    error_log('Razorpay API Exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error creating payment order: ' . $e->getMessage()]);
    exit;
}

