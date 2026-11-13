<?php
/**
 * IRONCLAD GAMING SANCTUARY — Hosting Health & Environment Diagnostic Tool
 * Upload this file to your hosting root (htdocs/) to verify your setup.
 */

// Enable full error reporting for diagnostics
error_reporting(E_ALL);
ini_set('display_errors', '1');

$configFile = __DIR__ . '/config/config.php';
$envFile = __DIR__ . '/.env';

$checks = [];

// 1. PHP Version
$checks['PHP Version'] = [
    'status' => version_compare(PHP_VERSION, '8.0.0', '>='),
    'detail' => 'Running PHP ' . PHP_VERSION . ' (Requirement: >= 8.0)'
];

// 2. PDO MySQL Extension
$checks['PDO MySQL Extension'] = [
    'status' => extension_loaded('pdo_mysql'),
    'detail' => extension_loaded('pdo_mysql') ? 'Installed & Active' : 'Missing pdo_mysql extension'
];

// 3. .env File Check
$checks['.env File'] = [
    'status' => file_exists($envFile) && is_readable($envFile),
    'detail' => file_exists($envFile) ? 'Found and readable' : 'NOT FOUND! InfinityFree often hides dot-files. Put credentials in config/config.php fallback.'
];

// 4. Configuration Load
$configLoaded = false;
if (file_exists($configFile)) {
    require_once $configFile;
    $configLoaded = true;
}
$checks['Config File'] = [
    'status' => $configLoaded,
    'detail' => $configLoaded ? 'Loaded config/config.php successfully' : 'config/config.php not found'
];

// 5. Database Connection Test
$dbConnected = false;
$dbMessage = '';
$tablesFound = 0;
if ($configLoaded) {
    try {
        $db = get_db();
        $dbConnected = true;
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $tablesFound = count($tables);
        $dbMessage = "Connected to host '" . DB_HOST . "' | Database '" . DB_NAME . "' has {$tablesFound} tables.";
    } catch (Throwable $t) {
        $dbMessage = $t->getMessage();
    }
}
$checks['Database Connection'] = [
    'status' => $dbConnected && $tablesFound > 0,
    'detail' => $dbConnected ? $dbMessage : ("FAILED: " . ($dbMessage ?: 'Could not connect'))
];

// 6. Base URL Detection
$detectedBaseUrl = defined('BASE_URL') ? BASE_URL : 'Unknown';
$checks['Detected BASE_URL'] = [
    'status' => true,
    'detail' => $detectedBaseUrl
];

// 7. Uploads Directory Writable
$uploadDir = __DIR__ . '/uploads/';
$isWritable = is_dir($uploadDir) && is_writable($uploadDir);
$checks['Uploads Directory'] = [
    'status' => $isWritable,
    'detail' => $isWritable ? 'Writable (755)' : (is_dir($uploadDir) ? 'Not writable (Set to 755 in FTP)' : 'Directory missing!')
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Hosting Health Diagnostic — Ironclad</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body { font-family: Segoe UI, sans-serif; background: #090B10; color: #fff; margin: 0; padding: 40px 20px; }
    .card { max-width: 700px; margin: 0 auto; background: #121620; border: 1px solid #232B3B; border-radius: 12px; padding: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.8); }
    h1 { color: #D6A84B; margin-top: 0; font-size: 24px; border-bottom: 1px solid #232B3B; padding-bottom: 15px; }
    .item { padding: 12px 16px; border-radius: 8px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; background: #181E2B; }
    .label { font-weight: 600; font-size: 15px; }
    .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
    .badge-ok { background: rgba(46,204,113,0.2); color: #2ecc71; border: 1px solid #2ecc71; }
    .badge-err { background: rgba(230,57,70,0.2); color: #e63946; border: 1px solid #e63946; }
    .desc { font-size: 13px; color: #94A3B8; margin-top: 4px; font-family: monospace; }
    .btn { display: inline-block; background: #D6A84B; color: #000; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-top: 20px; }
    .tip { background: rgba(0,229,255,0.08); border-left: 4px solid #00E5FF; padding: 12px; font-size: 13px; color: #cbd5e1; border-radius: 0 6px 6px 0; margin-top: 20px; }
  </style>
</head>
<body>
  <div class="card">
    <h1>⚔️ Ironclad Hosting Health Diagnostic</h1>
    <p style="color: #cbd5e1; font-size: 14px;">This diagnostic runs live on your server to verify environment variables, database connectivity, and permissions.</p>

    <?php foreach ($checks as $title => $c): ?>
      <div class="item">
        <div>
          <div class="label"><?= htmlspecialchars($title) ?></div>
          <div class="desc"><?= htmlspecialchars($c['detail']) ?></div>
        </div>
        <span class="badge <?= $c['status'] ? 'badge-ok' : 'badge-err' ?>">
          <?= $c['status'] ? 'PASS' : 'FAIL' ?>
        </span>
      </div>
    <?php endforeach; ?>

    <div class="tip">
      <strong>Next Steps:</strong>
      If all checks show <span style="color:#2ecc71; font-weight:bold;">PASS</span>, click below to open your homepage! Remember to delete <code>check.php</code> after your site is verified.
    </div>

    <a href="./index.php" class="btn">Go to Arena Homepage &rarr;</a>
  </div>
</body>
</html>

