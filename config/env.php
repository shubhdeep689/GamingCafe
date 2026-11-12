<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Lightweight Environment (.env) Loader
 * Loads sensitive credentials and environment variables securely into PHP runtime.
 */

if (!function_exists('load_env')) {
    /**
     * Parse a .env file and populate getenv(), $_ENV, and $_SERVER
     *
     * @param string $file_path Path to the .env file
     * @return bool
     */
    function load_env(string $file_path): bool {
        if (!file_exists($file_path) || !is_readable($file_path)) {
            return false;
        }

        $lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return false;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines and comment lines
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Split into key and value by first '='
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $val = trim($parts[1]);

            // Remove inline comments (e.g. KEY=val # comment) if outside quotes
            if (!empty($val) && ($val[0] !== '"' && $val[0] !== "'")) {
                $comment_pos = strpos($val, '#');
                if ($comment_pos !== false) {
                    $val = trim(substr($val, 0, $comment_pos));
                }
            }

            // Strip surrounding single or double quotes
            $len = strlen($val);
            if ($len >= 2) {
                $first = $val[0];
                $last  = $val[$len - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $val = substr($val, 1, -1);
                }
            }

            // Populate PHP environment superglobals
            putenv("{$key}={$val}");
            $_ENV[$key] = $val;
            $_SERVER[$key] = $val;
        }

        return true;
    }
}

if (!function_exists('env')) {
    /**
     * Get an environment variable value with type casting and default fallback
     *
     * @param string $key Environment key name
     * @param mixed $default Fallback value if not found
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed {
        $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($val === false || $val === null || $val === '') {
            return $default;
        }

        return match (strtolower((string)$val)) {
            'true', '(true)'   => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)'   => null,
            default            => $val
        };
    }
}

