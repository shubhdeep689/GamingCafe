<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Core Helper & Business Logic Functions
 */

// 1. Output Escaping (XSS Prevention)
function e($value): string {
    if ($value === null) {
        return '';
    }
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// 2. CSRF Protection
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf(?string $token = null): bool {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// 3. Flash Messages
function set_flash(string $type, string $message): void {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flash(): array {
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function display_flash(): string {
    $flashes = get_flash();
    if (empty($flashes)) {
        return '';
    }
    $html = '<div class="flash-messages-container mb-4">';
    foreach ($flashes as $flash) {
        $alert_type = match ($flash['type']) {
            'success' => 'alert-success',
            'danger', 'error' => 'alert-danger',
            'warning' => 'alert-warning',
            default => 'alert-info'
        };
        $icon = match ($flash['type']) {
            'success' => 'bi-check-circle-fill',
            'danger', 'error' => 'bi-exclamation-triangle-fill',
            'warning' => 'bi-exclamation-circle-fill',
            default => 'bi-info-circle-fill'
        };
        $html .= sprintf(
            '<div class="alert %s alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
                <i class="bi %s me-2 fs-5"></i>
                <div class="flex-grow-1">%s</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>',
            $alert_type,
            $icon,
            e($flash['message'])
        );
    }
    $html .= '</div>';
    return $html;
}

// 4. Site Settings Loader & Cache
function get_setting(string $key, string $default = ''): string {
    static $settings_cache = null;
    $db = get_db();

    if ($settings_cache === null) {
        try {
            $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings");
            $settings_cache = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            error_log('Settings Load Failed: ' . $e->getMessage());
            return $default;
        }
    }

    return $settings_cache[$key] ?? $default;
}

function update_setting(string $key, string $value, string $group = 'general'): bool {
    $db = get_db();
    try {
        $stmt = $db->prepare("
            INSERT INTO site_settings (setting_key, setting_value, setting_group) 
            VALUES (:key, :val, :grp)
            ON DUPLICATE KEY UPDATE setting_value = :val2, setting_group = :grp2
        ");
        return $stmt->execute([
            ':key' => $key,
            ':val' => $value,
            ':grp' => $group,
            ':val2' => $value,
            ':grp2' => $group
        ]);
    } catch (PDOException $e) {
        error_log('Update Setting Failed: ' . $e->getMessage());
        return false;
    }
}

// 5. Currency & Number Formatting
function format_currency($amount): string {
    return '₹' . number_format((float)$amount, 2);
}

// 5b. Proper Title Case Formatter for Customer / Player Names
function to_title_case(string $name): string {
    $trimmed = trim($name);
    if ($trimmed === '') return '';
    // Normalize consecutive spaces and convert to Title Case (e.g. "shubhdeep singh" -> "Shubhdeep Singh")
    $normalized = preg_replace('/\s+/', ' ', $trimmed);
    return mb_convert_case(mb_strtolower($normalized, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
}

// 5c. Dynamic Brand Identity & White-Label Helpers
function get_brand_name(): string {
    return get_setting('business_name', 'IRONCLAD GAMING SANCTUARY');
}

function get_brand_short(): string {
    return get_setting('brand_short_name', 'IRONCLAD');
}

function get_brand_tagline(): string {
    return get_setting('tagline', 'Gaming. Competition. Community.');
}

function get_brand_logo(): string {
    $logo = get_setting('logo_image', 'images/NOBG_IRONCLADLOGO.png');
    if (empty($logo)) {
        $logo = 'images/NOBG_IRONCLADLOGO.png';
    }
    if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) {
        return $logo;
    }
    return BASE_URL . ltrim($logo, '/');
}

function get_brand_video(): string {
    $video = get_setting('arena_tour_video', 'images/intro_clad.mp4');
    if (empty($video)) {
        $video = 'images/intro_clad.mp4';
    }
    if (str_starts_with($video, 'http://') || str_starts_with($video, 'https://')) {
        return $video;
    }
    return BASE_URL . ltrim($video, '/');
}

function get_brand_address(): string {
    return get_setting('address', 'Behind Redtape Showroom, Near PSPCL Office, Guru Angad Dev Nagar, Sri Muktsar Sahib, Punjab – 152026');
}

function get_brand_city(): string {
    return get_setting('city', 'Sri Muktsar Sahib');
}

function get_brand_state(): string {
    return get_setting('state', 'Punjab');
}

function get_brand_pincode(): string {
    return get_setting('pincode', '152026');
}

function get_brand_phone(): string {
    return get_setting('contact_phone', '');
}

function get_brand_email(): string {
    return get_setting('contact_email', 'contact@ironcladgaming.in');
}

function get_brand_whatsapp(): string {
    return get_setting('whatsapp_number', '');
}

// 6. Unique Booking Reference Generator
function generate_booking_ref(): string {
    $db = get_db();
    $year = date('Y');
    
    // Characters excluding ambiguous 0, O, 1, I
    $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    $chars_len = strlen($chars);

    for ($attempt = 0; $attempt < 10; $attempt++) {
        $random = '';
        for ($i = 0; $i < 5; $i++) {
            $random .= $chars[random_int(0, $chars_len - 1)];
        }
        $ref = sprintf('IC-%s-%s', $year, $random);

        $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE booking_ref = :ref");
        $stmt->execute([':ref' => $ref]);
        if ($stmt->fetchColumn() == 0) {
            return $ref;
        }
    }

    // Fallback timestamp based
    return 'IC-' . $year . '-' . strtoupper(substr(md5(uniqid()), 0, 5));
}

// 7. Time Calculation (Supports flexible decimal durations e.g. 0.5hr = 30m, 1.5hr = 90m)
function calculate_end_time(string $start_time, float|int $duration_hours): string {
    $timestamp = strtotime($start_time);
    $end_timestamp = $timestamp + (int)round($duration_hours * 3600);
    return date('H:i:s', $end_timestamp);
}

// 8. Server-Side Atomic Slot Availability Check
function is_slot_available(int $station_id, string $date, string $start_time, float|int $duration_hours, ?int $exclude_booking_id = null): bool {
    $db = get_db();

    // Date format sanitization
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }

    // 1. Check if date is blocked (holiday/maintenance)
    $stmt_blocked = $db->prepare("SELECT COUNT(*) FROM blocked_dates WHERE block_date = :bdate");
    $stmt_blocked->execute([':bdate' => $date]);
    if ($stmt_blocked->fetchColumn() > 0) {
        return false;
    }

    // 2. Check business hours for that day
    $day_of_week = (int)date('w', strtotime($date));
    $stmt_hours = $db->prepare("SELECT is_open, open_time, close_time FROM business_hours WHERE day_of_week = :dow");
    $stmt_hours->execute([':dow' => $day_of_week]);
    $hours = $stmt_hours->fetch();

    if (!$hours || !$hours['is_open']) {
        return false;
    }

    // Full start and end timestamps (prevents 24-hour midnight wrap-around bugs)
    $start_ts = strtotime($date . ' ' . $start_time);
    $open_ts  = strtotime($date . ' ' . $hours['open_time']);
    $close_ts = strtotime($date . ' ' . $hours['close_time']);

    if ($start_ts === false || $open_ts === false || $close_ts === false) {
        return false;
    }

    $end_ts = $start_ts + (int)round($duration_hours * 3600);

    // If close_time <= open_time, sanctuary operates past midnight into next day
    if ($close_ts <= $open_ts) {
        $close_ts += 86400;
    }

    // Strictly enforce operating hours (cannot start before open, cannot end after close)
    if ($start_ts < $open_ts || $end_ts > $close_ts) {
        return false;
    }

    // 3. Verify station is active
    $stmt_station = $db->prepare("SELECT status FROM stations WHERE id = :id");
    $stmt_station->execute([':id' => $station_id]);
    $station = $stmt_station->fetch();
    if (!$station || $station['status'] !== 'active') {
        return false;
    }

    // 4. Check for overlapping bookings using full DATETIME comparisons
    // An overlap occurs if (existing_start < requested_end) AND (existing_end > requested_start)
    $req_start_dt = date('Y-m-d H:i:s', $start_ts);
    $req_end_dt   = date('Y-m-d H:i:s', $end_ts);

    $sql = "
        SELECT COUNT(*) 
        FROM bookings 
        WHERE station_id = :station_id 
          AND status IN ('pending_payment', 'confirmed', 'in_session')
          AND CONCAT(booking_date, ' ', start_time) < :req_end_dt
          AND IF(end_time < start_time, 
                 CONCAT(DATE_ADD(booking_date, INTERVAL 1 DAY), ' ', end_time), 
                 CONCAT(booking_date, ' ', end_time)
              ) > :req_start_dt
    ";
    $params = [
        ':station_id'   => $station_id,
        ':req_start_dt' => $req_start_dt,
        ':req_end_dt'   => $req_end_dt
    ];

    if ($exclude_booking_id !== null) {
        $sql .= " AND id != :exclude_id";
        $params[':exclude_id'] = $exclude_booking_id;
    }

    $stmt_check = $db->prepare($sql);
    $stmt_check->execute($params);
    $conflicts = (int)$stmt_check->fetchColumn();

    return $conflicts === 0;
}

// 8b. Early Session Checkout & Immediate Rig Release Engine
// Solves business loss when a player leaves before scheduled slot ends
function release_station_early(int $booking_id, ?string $checkout_note = null, ?int $admin_id = null): array {
    $db = get_db();
    
    // Fetch booking
    $stmt = $db->prepare("
        SELECT b.*, s.name AS station_name, cust.full_name AS customer_name 
        FROM bookings b
        JOIN stations s ON b.station_id = s.id
        JOIN customers cust ON b.customer_id = cust.id
        WHERE b.id = :id
    ");
    $stmt->execute([':id' => $booking_id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        return ['success' => false, 'message' => 'Booking record not found.'];
    }

    if ($booking['status'] === 'completed') {
        return ['success' => false, 'message' => 'Session is already marked completed.'];
    }

    $now_time = date('H:i:s');
    $now_datetime = date('Y-m-d H:i:s');
    $start_ts = strtotime($booking['booking_date'] . ' ' . $booking['start_time']);
    $now_ts = time();
    
    // Calculate actual elapsed hours (minimum 0.5 hour)
    $actual_dur_hours = max(0.5, round(($now_ts - $start_ts) / 3600, 2));

    $note_text = "\n[Early Checkout / Rig Release: Gamer checked out at " . date('g:i A') . ". Rig freed immediately for new walk-ins.]";
    if (!empty($checkout_note)) {
        $note_text .= " (" . trim($checkout_note) . ")";
    }

    $upd = $db->prepare("
        UPDATE bookings 
        SET end_time = :etime,
            status = 'completed',
            notes = CONCAT(COALESCE(notes, ''), :note),
            updated_at = NOW()
        WHERE id = :id
    ");
    $upd->execute([
        ':etime' => $now_time,
        ':note'  => $note_text,
        ':id'    => $booking_id
    ]);

    if ($admin_id) {
        log_admin_activity($admin_id, 'release_station_early', "Ended session early for #{$booking['booking_ref']} at {$now_time}. Released {$booking['station_name']} immediately.");
    }

    return [
        'success' => true,
        'message' => "Station {$booking['station_name']} successfully released! Rig is now 100% FREE for new walk-ins.",
        'booking_ref' => $booking['booking_ref'],
        'station_name' => $booking['station_name'],
        'released_at' => date('g:i A')
    ];
}

// 9. Get Operating Slots for a Station and Date
function get_day_time_slots(string $date, int $slot_interval = 60): array {
    $db = get_db();
    $day_of_week = (int)date('w', strtotime($date));
    $stmt = $db->prepare("SELECT is_open, open_time, close_time FROM business_hours WHERE day_of_week = :dow");
    $stmt->execute([':dow' => $day_of_week]);
    $hours = $stmt->fetch();

    if (!$hours || !$hours['is_open']) {
        return [];
    }

    $slots = [];
    $start = strtotime($hours['open_time']);
    $close = strtotime($hours['close_time']);

    while ($start < $close) {
        $time_str = date('H:i:s', $start);
        $display_str = date('g:i A', $start);
        $slots[] = [
            'time' => $time_str,
            'display' => $display_str
        ];
        $start += ($slot_interval * 60);
    }

    return $slots;
}

// 10. Status Badges
function booking_status_badge(string $status): string {
    return match ($status) {
        'pending_payment' => '<span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Pending Payment</span>',
        'confirmed'       => '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Confirmed</span>',
        'in_session'      => '<span class="badge bg-info text-dark"><i class="bi bi-controller me-1"></i>In Session</span>',
        'completed'       => '<span class="badge bg-primary"><i class="bi bi-flag-fill me-1"></i>Completed</span>',
        'cancelled'       => '<span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>Cancelled</span>',
        'rejected'        => '<span class="badge bg-danger"><i class="bi bi-slash-circle me-1"></i>Rejected</span>',
        default           => '<span class="badge bg-dark">' . e(ucfirst($status)) . '</span>'
    };
}

function payment_status_badge(string $status): string {
    return match ($status) {
        'pending'  => '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Pending Verification</span>',
        'verified' => '<span class="badge bg-success"><i class="bi bi-shield-check me-1"></i>Verified</span>',
        'rejected' => '<span class="badge bg-danger"><i class="bi bi-x-octagon me-1"></i>Rejected</span>',
        default    => '<span class="badge bg-secondary">' . e(ucfirst($status)) . '</span>'
    };
}

// 11. Secure File Upload Handler
function handle_file_upload(array $file, string $target_subfolder, int $max_bytes = 104857600, array $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 'video/mp4', 'video/webm', 'video/ogg', 'video/quicktime']): array {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Invalid file parameter.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $messages = [
            UPLOAD_ERR_INI_SIZE   => 'Uploaded file exceeds PHP upload limit.',
            UPLOAD_ERR_FORM_SIZE  => 'Uploaded file exceeds form limit.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.'
        ];
        return ['success' => false, 'error' => $messages[$file['error']] ?? 'File upload error.'];
    }

    if ($file['size'] > $max_bytes) {
        return ['success' => false, 'error' => 'File size exceeds maximum limit of ' . ($max_bytes / (1024 * 1024)) . ' MB.'];
    }

    $mime = null;
    $orig_ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));

    // 1. Check with finfo if available
    if (class_exists('finfo')) {
        try {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
        } catch (Throwable $t) {
            $mime = null;
        }
    }

    // 2. Fallback to getimagesize (built-in in core PHP for images)
    if (!$mime && function_exists('getimagesize')) {
        $img_info = @getimagesize($file['tmp_name']);
        if ($img_info && !empty($img_info['mime'])) {
            $mime = $img_info['mime'];
        }
    }

    // 3. Fallback for SVG inspection
    if (!$mime && in_array($orig_ext, ['svg', 'svgz'])) {
        $sample = @file_get_contents($file['tmp_name'], false, null, 0, 512);
        if ($sample && (stripos($sample, '<svg') !== false || stripos($sample, '<?xml') !== false)) {
            $mime = 'image/svg+xml';
        }
    }

    // 4. Fallback for videos based on extension and client mime
    if (!$mime && in_array($orig_ext, ['mp4', 'm4v'])) {
        $mime = 'video/mp4';
    } elseif (!$mime && $orig_ext === 'webm') {
        $mime = 'video/webm';
    } elseif (!$mime && in_array($orig_ext, ['mov', 'qt'])) {
        $mime = 'video/quicktime';
    }

    // 5. Fallback to verified client MIME
    if (!$mime && !empty($file['type'])) {
        $client_mime = strtolower(trim($file['type']));
        if (in_array($client_mime, $allowed_mimes)) {
            $mime = $client_mime;
        }
    }

    if (!$mime || !in_array($mime, $allowed_mimes)) {
        return ['success' => false, 'error' => 'Invalid file format. Allowed formats: JPG, PNG, WEBP, SVG, MP4, WEBM, MOV.'];
    }

    $ext = match ($mime) {
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp',
        'image/svg+xml'   => 'svg',
        'video/mp4'       => 'mp4',
        'video/webm'      => 'webm',
        'video/ogg'       => 'ogv',
        'video/quicktime' => 'mov',
        default           => in_array($orig_ext, ['jpg', 'jpeg', 'png', 'webp', 'svg', 'mp4', 'webm', 'mov']) ? $orig_ext : false
    };

    if (!$ext) {
        return ['success' => false, 'error' => 'Unrecognized file extension.'];
    }

    $subfolder = trim($target_subfolder, '/\\') . DIRECTORY_SEPARATOR;
    $target_dir = UPLOAD_DIR . $subfolder;
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    $filename = sprintf('%s_%s.%s', bin2hex(random_bytes(10)), time(), $ext);
    $dest_path = $target_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest_path)) {
        return ['success' => false, 'error' => 'Failed to save uploaded file.'];
    }

    $relative_url = 'uploads/' . str_replace('\\', '/', $subfolder) . $filename;
    return ['success' => true, 'path' => $relative_url, 'filename' => $filename];
}

// 12. Admin Activity Logger
function log_admin_activity(?int $admin_id, string $action, ?string $details = null): void {
    try {
        $db = get_db();
        $stmt = $db->prepare("
            INSERT INTO admin_activity_logs (admin_id, action, details, ip_address) 
            VALUES (:aid, :act, :det, :ip)
        ");
        $stmt->execute([
            ':aid' => $admin_id,
            ':act' => $action,
            ':det' => $details,
            ':ip'  => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
    } catch (Exception $e) {
        error_log('Failed to write admin log: ' . $e->getMessage());
    }
}

// 13. Safe Redirect
function redirect(string $url): void {
    if (!headers_sent()) {
        header('Location: ' . $url);
        exit;
    }
    echo '<script type="text/javascript">window.location.href=' . json_encode($url) . ';</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"></noscript>';
    exit;
}

// 14. Friendly Time Ago
function time_ago(string $datetime): string {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' mins ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('d M Y', $time);
}

// 15. Indian Mobile Phone Validation & Normalization
function clean_indian_phone(string $phone): string {
    $clean = preg_replace('/[^\d]/', '', $phone);
    if (strlen($clean) === 12 && str_starts_with($clean, '91')) {
        return substr($clean, 2);
    }
    if (strlen($clean) > 10) {
        return substr($clean, -10);
    }
    return $clean;
}

function validate_indian_phone(string $phone): bool {
    $clean = clean_indian_phone($phone);
    return (bool)preg_match('/^[6-9]\d{9}$/', $clean);
}

// 16. Live Arena Radar Status (Real-time Right Now)
function get_live_arena_status(): array {
    $db = get_db();
    $today = date('Y-m-d');
    $now_time = date('H:i:s');
    $now_ts = time();

    // Check if arena is open today
    $day_of_week = (int)date('w');
    $stmt_h = $db->prepare("SELECT is_open, open_time, close_time FROM business_hours WHERE day_of_week = :dow");
    $stmt_h->execute([':dow' => $day_of_week]);
    $hours = $stmt_h->fetch();

    $is_open_now = false;
    if ($hours && $hours['is_open']) {
        if ($now_time >= $hours['open_time'] && $now_time <= $hours['close_time']) {
            $is_open_now = true;
        }
    }

    // Fetch all active stations
    $stations = $db->query("
        SELECT s.*, c.name AS category_name 
        FROM stations s
        JOIN station_categories c ON s.category_id = c.id
        WHERE s.status = 'active'
        ORDER BY s.display_order ASC
    ")->fetchAll();

    // Fetch active bookings occurring right now (using full DATETIME to accurately handle midnight-crossing sessions)
    $now_datetime = date('Y-m-d H:i:s');
    $stmt_active = $db->prepare("
        SELECT b.*, cust.full_name AS customer_name, cust.phone AS customer_phone
        FROM bookings b
        JOIN customers cust ON b.customer_id = cust.id
        WHERE b.status IN ('confirmed', 'in_session', 'pending_payment')
          AND CONCAT(b.booking_date, ' ', b.start_time) <= :now_dt1
          AND IF(b.end_time < b.start_time, 
                 CONCAT(DATE_ADD(b.booking_date, INTERVAL 1 DAY), ' ', b.end_time), 
                 CONCAT(b.booking_date, ' ', b.end_time)
              ) > :now_dt2
    ");
    $stmt_active->execute([':now_dt1' => $now_datetime, ':now_dt2' => $now_datetime]);
    $active_map = [];
    foreach ($stmt_active->fetchAll() as $b) {
        $active_map[$b['station_id']] = $b;
    }

    // Fetch upcoming bookings today for each station
    $stmt_upcoming = $db->prepare("
        SELECT b.*, cust.full_name AS customer_name
        FROM bookings b
        JOIN customers cust ON b.customer_id = cust.id
        WHERE b.booking_date = :td
          AND b.status IN ('confirmed', 'pending_payment')
          AND CONCAT(b.booking_date, ' ', b.start_time) > :now_dt
        ORDER BY b.start_time ASC
    ");
    $stmt_upcoming->execute([':td' => $today, ':now_dt' => $now_datetime]);
    $upcoming_map = [];
    foreach ($stmt_upcoming->fetchAll() as $ub) {
        if (!isset($upcoming_map[$ub['station_id']])) {
            $upcoming_map[$ub['station_id']] = $ub;
        }
    }

    $results = [];
    $total_free = 0;
    $total_in_session = 0;

    foreach ($stations as $st) {
        $sid = (int)$st['id'];
        $is_occupied = isset($active_map[$sid]);
        $station_data = [
            'id' => $sid,
            'name' => $st['name'],
            'code' => $st['code'],
            'category_name' => $st['category_name'],
            'hourly_rate' => (float)$st['hourly_rate'],
            'is_open_now' => $is_open_now,
            'is_occupied' => $is_occupied,
            'current_booking' => null,
            'upcoming_booking' => null,
            'minutes_left' => 0,
            'percent_elapsed' => 0,
            'status_label' => 'FREE NOW',
            'status_badge_class' => 'bg-success',
        ];

        if ($is_occupied) {
            $total_in_session++;
            $cb = $active_map[$sid];
            $start_ts = strtotime($cb['booking_date'] . ' ' . $cb['start_time']);
            $end_ts = strtotime($cb['booking_date'] . ' ' . $cb['end_time']);
            if ($end_ts <= $start_ts) {
                $end_ts += 86400; // midnight-crossing session
            }
            $total_dur_sec = max(1, $end_ts - $start_ts);
            $elapsed_sec = max(0, $now_ts - $start_ts);
            $remaining_sec = max(0, $end_ts - $now_ts);
            $mins_left = (int)ceil($remaining_sec / 60);
            $percent = min(100, (int)round(($elapsed_sec / $total_dur_sec) * 100));

            $station_data['current_booking'] = $cb;
            $station_data['minutes_left'] = $mins_left;
            $station_data['percent_elapsed'] = $percent;
            $station_data['status_label'] = 'IN SESSION';
            $station_data['status_badge_class'] = ($cb['status'] === 'in_session' || $cb['status'] === 'confirmed') ? 'bg-danger' : 'bg-warning text-dark';
        } else {
            $total_free++;
            if (isset($upcoming_map[$sid])) {
                $station_data['upcoming_booking'] = $upcoming_map[$sid];
            }
        }

        $results[] = $station_data;
    }

    return [
        'is_open_now' => $is_open_now,
        'open_time' => $hours['open_time'] ?? '10:00:00',
        'close_time' => $hours['close_time'] ?? '23:00:00',
        'total_stations' => count($stations),
        'total_free' => $total_free,
        'total_in_session' => $total_in_session,
        'stations' => $results,
    ];
}

// 17. Visual Schedule Matrix for Any Selected Date
function get_station_schedule_matrix(string $date): array {
    $db = get_db();
    $day_of_week = (int)date('w', strtotime($date));
    $is_today = ($date === date('Y-m-d'));
    $now_time = date('H:i:s');

    // 1. Check blocked date
    $stmt_bl = $db->prepare("SELECT reason FROM blocked_dates WHERE block_date = :bd");
    $stmt_bl->execute([':bd' => $date]);
    $blocked = $stmt_bl->fetch();

    if ($blocked) {
        return [
            'date' => $date,
            'is_closed' => true,
            'reason' => 'Arena Closed: ' . $blocked['reason'],
            'hours' => [],
            'stations' => [],
        ];
    }

    // 2. Business hours
    $stmt_h = $db->prepare("SELECT is_open, open_time, close_time FROM business_hours WHERE day_of_week = :dow");
    $stmt_h->execute([':dow' => $day_of_week]);
    $hours = $stmt_h->fetch();

    if (!$hours || !$hours['is_open']) {
        return [
            'date' => $date,
            'is_closed' => true,
            'reason' => 'Sanctuary is scheduled closed on ' . date('l', strtotime($date)) . 's.',
            'hours' => [],
            'stations' => [],
        ];
    }

    // 3. Generate hourly slots (e.g. 10:00 to 23:00)
    $open_ts = strtotime($hours['open_time']);
    $close_ts = strtotime($hours['close_time']);
    $hourly_slots = [];
    $curr_ts = $open_ts;
    while ($curr_ts < $close_ts) {
        $hourly_slots[] = [
            'time' => date('H:i:s', $curr_ts),
            'display' => date('g A', $curr_ts),
            'display_full' => date('g:i A', $curr_ts),
            'timestamp' => $curr_ts
        ];
        $curr_ts += 3600;
    }

    // 4. Fetch all active stations
    $stations = $db->query("
        SELECT s.*, c.name AS category_name 
        FROM stations s
        JOIN station_categories c ON s.category_id = c.id
        WHERE s.status = 'active'
        ORDER BY s.display_order ASC
    ")->fetchAll();

    // 5. Fetch all bookings for this date
    $stmt_b = $db->prepare("
        SELECT b.*, cust.full_name AS customer_name, cust.phone AS customer_phone
        FROM bookings b
        JOIN customers cust ON b.customer_id = cust.id
        WHERE b.booking_date = :bd
          AND b.status IN ('confirmed', 'in_session', 'pending_payment')
        ORDER BY b.start_time ASC
    ");
    $stmt_b->execute([':bd' => $date]);
    $bookings = $stmt_b->fetchAll();

    // Build matrix
    $station_matrices = [];
    $is_past_date = ($date < date('Y-m-d'));

    foreach ($stations as $st) {
        $sid = (int)$st['id'];
        $st_bookings = array_filter($bookings, fn($b) => (int)$b['station_id'] === $sid);

        $slots_data = [];
        foreach ($hourly_slots as $slot) {
            $slot_start = $slot['time'];
            $slot_end = date('H:i:s', strtotime($slot_start) + 3600);
            $slot_full_ts = strtotime($date . ' ' . $slot_start);

            // Is past / expired? (Date in past OR today and slot end time has passed)
            $is_slot_past = $is_past_date || ($is_today && (strtotime($date . ' ' . $slot_end) <= time()));

            // Find overlapping booking for this hour using full DATETIME comparisons
            $slot_start_dt = $date . ' ' . $slot_start;
            $slot_end_dt   = date('Y-m-d H:i:s', strtotime($slot_start_dt) + 3600);

            $matched_booking = null;
            foreach ($st_bookings as $b) {
                $b_start_dt = $b['booking_date'] . ' ' . $b['start_time'];
                $b_end_dt = ($b['end_time'] < $b['start_time'])
                    ? date('Y-m-d H:i:s', strtotime($b['booking_date'] . ' ' . $b['end_time']) + 86400)
                    : ($b['booking_date'] . ' ' . $b['end_time']);

                if ($b_start_dt < $slot_end_dt && $b_end_dt > $slot_start_dt) {
                    $matched_booking = $b;
                    break;
                }
            }

            $state = 'free';
            if ($matched_booking) {
                if ($is_today && $slot_start <= $now_time && $slot_end > $now_time) {
                    $state = 'in_session';
                } elseif ($is_slot_past) {
                    $state = 'past_booked'; // Past booking already completed
                } else {
                    $state = ($matched_booking['status'] === 'pending_payment') ? 'pending' : 'booked';
                }
            } elseif ($is_slot_past) {
                $state = 'expired'; // Past time slot with no booking
            }

            $slots_data[$slot['time']] = [
                'time' => $slot['time'],
                'display' => $slot['display'],
                'display_full' => $slot['display_full'],
                'state' => $state,
                'booking' => $matched_booking,
                'is_past' => $is_slot_past,
            ];
        }

        $station_matrices[] = [
            'id' => $sid,
            'name' => $st['name'],
            'code' => $st['code'],
            'category_name' => $st['category_name'],
            'hourly_rate' => (float)$st['hourly_rate'],
            'slots' => $slots_data,
        ];
    }

    return [
        'date' => $date,
        'is_closed' => false,
        'reason' => '',
        'open_time' => $hours['open_time'],
        'close_time' => $hours['close_time'],
        'hours' => $hourly_slots,
        'stations' => $station_matrices,
    ];
}

// 18. Generate WhatsApp Notification URL & Formatted Message
function generate_whatsapp_url(string $type, array $booking, array $extra = []): string {
    $phone = clean_indian_phone($booking['customer_phone'] ?? $booking['phone'] ?? '');
    if (empty($phone)) {
        return '#';
    }

    $ref = $booking['booking_ref'] ?? '';
    $name = $booking['customer_name'] ?? $booking['full_name'] ?? 'Player';
    $station = $booking['station_name'] ?? 'Gaming Station';
    $cat = $booking['category_name'] ?? '';
    if (!empty($cat)) {
        $station .= " ({$cat})";
    }
    $date_formatted = !empty($booking['booking_date']) ? date('D, d M Y', strtotime($booking['booking_date'])) : '';
    $time_formatted = !empty($booking['start_time']) ? date('g:i A', strtotime($booking['start_time'])) : '';
    $end_formatted = !empty($booking['end_time']) ? date('g:i A', strtotime($booking['end_time'])) : '';
    $dur = $booking['duration_hours'] ?? 1;
    $amount = number_format((float)($booking['final_amount'] ?? 0), 2);
    $receipt_url = BASE_URL . 'receipt.php?ref=' . urlencode($ref) . '&phone=' . urlencode($phone);
    $pdf_pass_url = BASE_URL . 'receipt.php?ref=' . urlencode($ref) . '&download=pdf';
    $brand_name = get_brand_name();
    $brand_address = get_brand_address();
    $brand_short = get_brand_short();
    $maps_url = 'https://maps.google.com/?q=' . urlencode($brand_address);

    $msg = '';

    switch ($type) {
        case 'confirmed':
        case 'slot_booked':
            $msg = "🎮 *{$brand_name}* 🎮\n";
            $msg .= "⚡ *BOOKING CONFIRMED & LOCKED!*\n\n";
            $msg .= "Hey *{$name}*, your battle station is locked and loaded! Here are your session credentials:\n\n";
            $msg .= "🏷️ *Booking Ref:* #{$ref}\n";
            $msg .= "🖥️ *Station:* {$station}\n";
            $msg .= "📅 *Date:* {$date_formatted}\n";
            $msg .= "⏰ *Slot:* {$time_formatted} – {$end_formatted} ({$dur} hr)\n";
            $msg .= "💰 *Amount:* ₹{$amount} (Verified)\n\n";
            $msg .= "📄 *DOWNLOAD OFFICIAL 1-PAGE PDF PASS:*\n";
            $msg .= "{$pdf_pass_url}\n\n";
            $msg .= "🎟️ *Live Entry Pass & QR Verification Link:*\n";
            $msg .= "{$receipt_url}\n\n";
            $msg .= "📍 *Sanctuary Arena Location:*\n";
            $msg .= "{$brand_address}\n";
            $msg .= "🗺️ Directions: {$maps_url}\n\n";
            $msg .= "⚠️ Please present your booking reference or digital pass upon counter arrival. See you in the arena! 🔥";
            break;

        case 'rescheduled':
        case 'timing_changed':
            $new_date = !empty($extra['new_date']) ? date('D, d M Y', strtotime($extra['new_date'])) : $date_formatted;
            $new_start = !empty($extra['new_start']) ? date('g:i A', strtotime($extra['new_start'])) : $time_formatted;
            $new_end = !empty($extra['new_end']) ? date('g:i A', strtotime($extra['new_end'])) : $end_formatted;
            $msg = "🎮 *{$brand_name}* 🎮\n";
            $msg .= "🔄 *SESSION TIMING UPDATED*\n\n";
            $msg .= "Hey *{$name}*, your gaming schedule for booking *#{$ref}* has been updated:\n\n";
            $msg .= "🖥️ *Station:* {$station}\n";
            $msg .= "📅 *New Date:* {$new_date}\n";
            $msg .= "⏰ *New Slot:* {$new_start} – {$new_end}\n\n";
            $msg .= "📄 *DOWNLOAD UPDATED PDF PASS:*\n";
            $msg .= "{$pdf_pass_url}\n\n";
            $msg .= "🎟️ *View Updated Pass & Status:*\n";
            $msg .= "{$receipt_url}\n\n";
            $msg .= "Need any adjustments? Contact the sanctuary counter. GG! 🔥";
            break;

        case 'station_changed':
            $new_st = $extra['new_station'] ?? $station;
            $msg = "🎮 *{$brand_name}* 🎮\n";
            $msg .= "🔄 *STATION REASSIGNMENT NOTICE*\n\n";
            $msg .= "Hey *{$name}*, your gaming station for booking *#{$ref}* has been updated:\n\n";
            $msg .= "🖥️ *New Station:* {$new_st}\n";
            $msg .= "📅 *Date:* {$date_formatted}\n";
            $msg .= "⏰ *Slot:* {$time_formatted} – {$end_formatted}\n\n";
            $msg .= "📄 *DOWNLOAD UPDATED PDF PASS:*\n";
            $msg .= "{$pdf_pass_url}\n\n";
            $msg .= "🎟️ *View Updated Pass:*\n";
            $msg .= "{$receipt_url}\n\n";
            $msg .= "See you at your new rig! 🔥";
            break;

        case 'payment_rejected':
            $reason = $extra['reason'] ?? 'Payment transaction could not be verified with the bank / UTR mismatch.';
            $msg = "⚠️ *{$brand_name}*\n";
            $msg .= "❌ *PAYMENT VERIFICATION ALERT*\n\n";
            $msg .= "Hey *{$name}*, we could not verify the payment for booking *#{$ref}*.\n\n";
            $msg .= "📝 *Reason:* {$reason}\n";
            $msg .= "💰 *Payable Amount:* ₹{$amount}\n\n";
            $msg .= "To keep your station reserved, please resubmit your valid UPI UTR or screenshot here:\n";
            $msg .= "{$receipt_url}\n\n";
            $msg .= "You can also contact the counter immediately to confirm with cash. 📞";
            break;

        case 'reminder':
            $msg = "🎮 *{$brand_name}* 🎮\n";
            $msg .= "⏰ *GAMING SESSION REMINDER*\n\n";
            $msg .= "Hey *{$name}*, your battle station is waiting for you today!\n\n";
            $msg .= "🏷️ *Ref:* #{$ref}\n";
            $msg .= "🖥️ *Station:* {$station}\n";
            $msg .= "⏰ *Start Time:* {$time_formatted} ({$date_formatted})\n\n";
            $msg .= "📄 *DOWNLOAD 1-PAGE PDF PASS:*\n";
            $msg .= "{$pdf_pass_url}\n\n";
            $msg .= "🎟️ *Your Pass:*\n";
            $msg .= "{$receipt_url}\n";
            $msg .= "📍 {$brand_address}. Ready for battle! 🔥";
            break;

        default:
            $msg = "🎮 *{$brand_name}* 🎮\n";
            $msg .= "Booking #{$ref} for {$name}.\nPDF Pass: {$pdf_pass_url}\nLive View: {$receipt_url}";
            break;
    }

    return 'https://wa.me/91' . $phone . '?text=' . rawurlencode($msg);
}

// =========================================================================
// 21. Razorpay Payment Gateway Integration
// =========================================================================

function get_razorpay_key_id(): string {
    $key = get_setting('razorpay_key_id', '');
    if (!empty($key)) {
        return $key;
    }
    return (string)env('RAZORPAY_KEY_ID', defined('RAZORPAY_KEY_ID') ? RAZORPAY_KEY_ID : '');
}

function get_razorpay_key_secret(): string {
    $secret = get_setting('razorpay_key_secret', '');
    if (!empty($secret)) {
        return $secret;
    }
    return (string)env('RAZORPAY_KEY_SECRET', defined('RAZORPAY_KEY_SECRET') ? RAZORPAY_KEY_SECRET : '');
}

function get_razorpay_mode(): string {
    $mode = get_setting('razorpay_mode', '');
    if (!empty($mode)) {
        return $mode;
    }
    return (string)env('RAZORPAY_MODE', defined('RAZORPAY_MODE') ? RAZORPAY_MODE : 'test');
}

function is_razorpay_enabled(): bool {
    $enabled = get_setting('razorpay_enabled', '1');
    return ($enabled === '1' || $enabled === 'true' || $enabled === 'yes') 
        && !empty(get_razorpay_key_id()) 
        && !empty(get_razorpay_key_secret());
}

/**
 * Create a server-side order on Razorpay
 *
 * @param float $amount Amount in INR
 * @param string $receipt_ref Booking Reference
 * @param array $notes Metadata notes
 * @return array ['success' => bool, 'order' => array|null, 'error' => string|null]
 */
function razorpay_create_order(float $amount, string $receipt_ref, array $notes = []): array {
    $key_id = get_razorpay_key_id();
    $key_secret = get_razorpay_key_secret();

    if (empty($key_id) || empty($key_secret)) {
        return ['success' => false, 'error' => 'Razorpay API credentials not configured.'];
    }

    $amount_in_paise = (int)round($amount * 100);
    if ($amount_in_paise <= 0) {
        return ['success' => false, 'error' => 'Invalid payable amount.'];
    }

    $payload = [
        'amount'   => $amount_in_paise,
        'currency' => 'INR',
        'receipt'  => substr($receipt_ref, 0, 40),
        'notes'    => $notes
    ];
    $json_payload = json_encode($payload);

    $response = false;
    $http_code = 0;
    $err_details = '';

    // Method 1: Use cURL if enabled in PHP environment
    if (function_exists('curl_init')) {
        try {
            $ch = curl_init('https://api.razorpay.com/v1/orders');
            $curl_options = [
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERPWD        => $key_id . ':' . $key_secret,
                CURLOPT_POSTFIELDS     => $json_payload,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0
            ];

            $ca_cert = 'C:/xampp/perl/vendor/lib/Mozilla/CA/cacert.pem';
            if (file_exists($ca_cert)) {
                $curl_options[CURLOPT_CAINFO] = $ca_cert;
            }

            curl_setopt_array($ch, $curl_options);
            $response = curl_exec($ch);
            $http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            if ($response === false) {
                $err_details = 'cURL error: ' . $curl_error;
            }
        } catch (\Throwable $t) {
            $err_details = 'cURL exception: ' . $t->getMessage();
            $response = false;
        }
    }

    // Method 2: High-Performance Native Stream Context Fallback (does not depend on cURL extension)
    if ($response === false || empty($response)) {
        try {
            $auth = base64_encode($key_id . ':' . $key_secret);
            $stream_opts = [
                'http' => [
                    'method'        => 'POST',
                    'header'        => [
                        "Authorization: Basic {$auth}",
                        "Content-Type: application/json",
                        "Accept: application/json"
                    ],
                    'content'       => $json_payload,
                    'ignore_errors' => true,
                    'timeout'       => 15
                ],
                'ssl' => [
                    'verify_peer'      => false,
                    'verify_peer_name' => false
                ]
            ];

            $context = stream_context_create($stream_opts);
            $stream_res = @file_get_contents('https://api.razorpay.com/v1/orders', false, $context);

            if ($stream_res !== false) {
                $response = $stream_res;
                if (isset($http_response_header) && is_array($http_response_header)) {
                    foreach ($http_response_header as $hdr) {
                        if (preg_match('#^HTTP/\S+\s+(\d{3})#i', $hdr, $m)) {
                            $http_code = (int)$m[1];
                            break;
                        }
                    }
                }
            }
        } catch (\Throwable $t) {
            $err_details .= ' | Stream exception: ' . $t->getMessage();
        }
    }

    if ($response === false || empty($response)) {
        error_log('Razorpay Order Connection Error: ' . $err_details);
        return ['success' => false, 'error' => 'Connection to payment gateway failed. Please ensure internet access or use the manual UPI QR scanner.'];
    }

    $data = json_decode($response, true);
    if ($http_code >= 200 && $http_code < 300 && !empty($data['id'])) {
        return ['success' => true, 'order' => $data];
    }

    $err_msg = $data['error']['description'] ?? 'Failed to initialize payment gateway order.';
    error_log('Razorpay Order Creation Failed (HTTP ' . $http_code . '): ' . $response);
    return ['success' => false, 'error' => $err_msg];
}

/**
 * Cryptographically verify Razorpay payment signature
 *
 * @param string $order_id Razorpay Order ID (order_xxx)
 * @param string $payment_id Razorpay Payment ID (pay_xxx)
 * @param string $signature HMAC SHA256 Signature returned by Checkout JS
 * @return bool
 */
function razorpay_verify_signature(string $order_id, string $payment_id, string $signature): bool {
    $secret = get_razorpay_key_secret();
    if (empty($secret) || empty($order_id) || empty($payment_id) || empty($signature)) {
        return false;
    }

    $expected_signature = hash_hmac('sha256', $order_id . '|' . $payment_id, $secret);
    return hash_equals($expected_signature, $signature);
}


