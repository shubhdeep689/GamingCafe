<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Slot Availability API Endpoint
 */

header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__) . '/config/config.php';

$station_id = isset($_GET['station_id']) ? (int)$_GET['station_id'] : 0;
$date = isset($_GET['date']) ? trim($_GET['date']) : '';
$duration = isset($_GET['duration']) ? (float)$_GET['duration'] : 1.0;

if ($duration < 0.5) $duration = 0.5;
if ($duration > 12) $duration = 12.0;

// Validate date format YYYY-MM-DD
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format.']);
    exit;
}

if ($station_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please select a valid gaming station.']);
    exit;
}

try {
    $db = get_db();

    // Check station existence and status
    $stmt_st = $db->prepare("SELECT id, name, status FROM stations WHERE id = :id");
    $stmt_st->execute([':id' => $station_id]);
    $station = $stmt_st->fetch();

    if (!$station) {
        echo json_encode(['success' => false, 'message' => 'Station not found.']);
        exit;
    }

    if ($station['status'] !== 'active') {
        echo json_encode([
            'success' => false,
            'message' => 'Station is currently under maintenance or inactive. Please choose another station.'
        ]);
        exit;
    }

    // Check blocked dates
    $stmt_blocked = $db->prepare("SELECT reason FROM blocked_dates WHERE block_date = :bdate");
    $stmt_blocked->execute([':bdate' => $date]);
    $blocked = $stmt_blocked->fetch();
    if ($blocked) {
        echo json_encode([
            'success' => true,
            'is_closed' => true,
            'message' => 'Arena closed on ' . date('d M Y', strtotime($date)) . ' (' . $blocked['reason'] . ').'
        ]);
        exit;
    }

    // Check business operating hours
    $day_of_week = (int)date('w', strtotime($date));
    $stmt_hours = $db->prepare("SELECT is_open, open_time, close_time FROM business_hours WHERE day_of_week = :dow");
    $stmt_hours->execute([':dow' => $day_of_week]);
    $hours = $stmt_hours->fetch();

    if (!$hours || !$hours['is_open']) {
        echo json_encode([
            'success' => true,
            'is_closed' => true,
            'message' => 'Ironclad Gaming Sanctuary is closed on ' . date('l', strtotime($date)) . 's.'
        ]);
        exit;
    }

    // Generate slots
    $open_ts = strtotime($hours['open_time']);
    $close_ts = strtotime($hours['close_time']);
    $now_ts = time();
    $is_today = ($date === date('Y-m-d'));
    $is_past_date = ($date < date('Y-m-d'));
    $is_admin = (isset($_GET['is_admin']) && $_GET['is_admin'] === '1');

    $slots = [];
    $curr_ts = $open_ts;

    while (($curr_ts + ($duration * 3600)) <= $close_ts) {
        $slot_time = date('H:i:s', $curr_ts);
        $display_time = date('g:i A', $curr_ts);

        // If slot has already passed
        $slot_full_ts = strtotime($date . ' ' . $slot_time);
        if ($is_past_date) {
            $is_past = true;
        } elseif ($is_today) {
            if ($is_admin) {
                // Admin can book ongoing session if end time is still in future
                $is_past = (($slot_full_ts + ($duration * 3600)) <= $now_ts);
            } else {
                // Online gamer booking: 5 min advance buffer
                $is_past = ($slot_full_ts < ($now_ts + 300));
            }
        } else {
            $is_past = false;
        }

        $available = false;
        if (!$is_past) {
            $available = is_slot_available($station_id, $date, $slot_time, $duration);
        }

        $slots[] = [
            'time' => $slot_time,
            'display' => $display_time,
            'available' => $available,
            'is_past' => $is_past
        ];

        $curr_ts += 1800; // 30-minute step (supports arrival at 10:30, 11:30, etc.)
    }

    $available_count = count(array_filter($slots, fn($s) => $s['available']));

    echo json_encode([
        'success' => true,
        'is_closed' => false,
        'station_name' => $station['name'],
        'slots' => $slots,
        'total_available' => $available_count
    ]);

} catch (Exception $e) {
    error_log('Slot API Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error checking availability.']);
}

