<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Promo Code Verification API Endpoint
 */

header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__) . '/config/config.php';

$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true) ?: [];
$code = strtoupper(trim($input['code'] ?? $_POST['code'] ?? ''));
$duration_hours = (float)($input['duration_hours'] ?? $_POST['duration_hours'] ?? 1);
if ($duration_hours < 0.5) $duration_hours = 0.5;

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a coupon code.']);
    exit;
}

try {
    $db = get_db();
    $today = date('Y-m-d');

    $stmt = $db->prepare("
        SELECT * FROM offers 
        WHERE code = :code 
          AND is_active = 1 
          AND valid_from <= :today 
          AND valid_until >= :today2
        LIMIT 1
    ");
    $stmt->execute([':code' => $code, ':today' => $today, ':today2' => $today]);
    $offer = $stmt->fetch();

    if (!$offer) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired promotional code.']);
        exit;
    }

    if ($duration_hours < $offer['min_booking_hours']) {
        echo json_encode([
            'success' => false,
            'message' => "This code requires a minimum session booking of {$offer['min_booking_hours']} hour(s)."
        ]);
        exit;
    }

    $discount_label = ($offer['discount_type'] === 'percentage') 
        ? ((float)$offer['discount_value'] . '% OFF') 
        : ('₹' . number_format((float)$offer['discount_value'], 2) . ' OFF');

    echo json_encode([
        'success'        => true,
        'code'           => $offer['code'],
        'title'          => $offer['title'],
        'discount_type'  => $offer['discount_type'],
        'discount_value' => (float)$offer['discount_value'],
        'message'        => "Code applied! Enjoy {$discount_label} on your session."
    ]);

} catch (Exception $e) {
    error_log('Promo API Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to process promo code.']);
}
