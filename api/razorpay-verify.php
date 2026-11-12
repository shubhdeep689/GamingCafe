<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Razorpay Payment Verification API Endpoint
 * Cryptographically validates the Razorpay HMAC signature, confirms the booking,
 * marks payment verified, updates customer stats, and returns redirect URL.
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
$order_id    = trim($input_data['razorpay_order_id'] ?? $_POST['razorpay_order_id'] ?? '');
$payment_id  = trim($input_data['razorpay_payment_id'] ?? $_POST['razorpay_payment_id'] ?? '');
$signature   = trim($input_data['razorpay_signature'] ?? $_POST['razorpay_signature'] ?? '');
$csrf_token  = trim($input_data['csrf_token'] ?? $_POST['csrf_token'] ?? '');

// Verify CSRF
if (!verify_csrf($csrf_token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Security token invalid or expired. Please refresh the page.']);
    exit;
}

if (empty($booking_ref) || empty($order_id) || empty($payment_id) || empty($signature)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required payment verification parameters.']);
    exit;
}

// 1. Cryptographic Signature Verification
$is_valid = razorpay_verify_signature($order_id, $payment_id, $signature);

if (!$is_valid) {
    error_log("Razorpay Signature Verification FAILED for Order: {$order_id}, Payment: {$payment_id}, Booking: {$booking_ref}");
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Payment signature verification failed. The payment response could not be verified with Razorpay.'
    ]);
    exit;
}

$db = get_db();

try {
    $stmt = $db->prepare("
        SELECT b.*, cust.id AS cust_id, cust.full_name AS customer_name, cust.phone AS customer_phone
        FROM bookings b
        JOIN customers cust ON b.customer_id = cust.id
        WHERE b.booking_ref = :ref
        LIMIT 1
    ");
    $stmt->execute([':ref' => $booking_ref]);
    $booking = $stmt->fetch();

    if (!$booking) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Booking not found.']);
        exit;
    }

    $db->beginTransaction();

    // 2. Update Payment Record
    $stmt_pay = $db->prepare("SELECT id FROM payments WHERE booking_id = :bid ORDER BY id DESC LIMIT 1");
    $stmt_pay->execute([':bid' => $booking['id']]);
    $pay_row = $stmt_pay->fetch();

    $verified_notes = "Auto-verified via Razorpay Gateway (Order: {$order_id}, Payment: {$payment_id})";

    if ($pay_row) {
        $upd_pay = $db->prepare("
            UPDATE payments 
            SET payment_method = 'razorpay',
                razorpay_order_id = :oid,
                razorpay_payment_id = :pid,
                razorpay_signature = :sig,
                upi_transaction_id = :pid2,
                status = 'verified',
                admin_notes = :notes,
                verified_at = NOW()
            WHERE id = :id
        ");
        $upd_pay->execute([
            ':oid'   => $order_id,
            ':pid'   => $payment_id,
            ':sig'   => $signature,
            ':pid2'  => $payment_id,
            ':notes' => $verified_notes,
            ':id'    => $pay_row['id']
        ]);
    } else {
        $ins_pay = $db->prepare("
            INSERT INTO payments (booking_id, amount, payment_method, razorpay_order_id, razorpay_payment_id, razorpay_signature, upi_transaction_id, status, admin_notes, verified_at)
            VALUES (:bid, :amt, 'razorpay', :oid, :pid, :sig, :pid2, 'verified', :notes, NOW())
        ");
        $ins_pay->execute([
            ':bid'   => $booking['id'],
            ':amt'   => $booking['final_amount'],
            ':oid'   => $order_id,
            ':pid'   => $payment_id,
            ':sig'   => $signature,
            ':pid2'  => $payment_id,
            ':notes' => $verified_notes
        ]);
    }

    // 3. Confirm Booking & Mark WhatsApp Pending for Pass Dispatch
    $upd_booking = $db->prepare("
        UPDATE bookings 
        SET status = 'confirmed',
            whatsapp_status = 'pending',
            whatsapp_pending_reason = 'Payment Auto-Verified via Razorpay - Ready for Entry Pass',
            updated_at = NOW()
        WHERE id = :id
    ");
    $upd_booking->execute([':id' => $booking['id']]);

    // 4. Update Customer Total Spent
    if (!empty($booking['cust_id'])) {
        $upd_cust = $db->prepare("
            UPDATE customers 
            SET total_spent = total_spent + :amt,
                updated_at = NOW()
            WHERE id = :cid
        ");
        $upd_cust->execute([
            ':amt' => $booking['final_amount'],
            ':cid' => $booking['cust_id']
        ]);
    }

    $db->commit();

    // 5. Log Admin / System Audit Trail
    log_admin_activity(
        null, 
        'razorpay_payment_verified', 
        "Razorpay online payment of ₹" . number_format((float)$booking['final_amount'], 2) . " auto-verified for Booking #{$booking_ref}. Payment ID: {$payment_id}"
    );

    // Set flash for receipt or portal
    set_flash('success', "Payment of ₹" . number_format((float)$booking['final_amount'], 2) . " confirmed via Razorpay! Your session pass has been issued.");

    echo json_encode([
        'success'      => true,
        'message'      => 'Payment verified successfully and reservation confirmed!',
        'booking_ref'  => $booking_ref,
        'payment_id'   => $payment_id,
        'redirect_url' => BASE_URL . 'receipt.php?ref=' . urlencode($booking_ref) . '&payment=verified'
    ]);
    exit;

} catch (\Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Razorpay Verification DB Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Server error updating reservation status: ' . $e->getMessage()
    ]);
    exit;
}

