<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Database Connection via PDO
 */

function get_db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Attempt to create database if it doesn't exist yet
            if ($e->getCode() == 1049) {
                try {
                    $rootDsn = sprintf('mysql:host=%s;port=%d;charset=%s', DB_HOST, DB_PORT, DB_CHARSET);
                    $tempPdo = new PDO($rootDsn, DB_USER, DB_PASS, $options);
                    $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                } catch (PDOException $ex) {
                    error_log('Database Connection & Creation Failed: ' . $ex->getMessage());
                    die('Database connection error. Please ensure MySQL is running in XAMPP.');
                }
            } else {
                error_log('Database Connection Failed: ' . $e->getMessage());
                die('Database connection error. Please check your database settings or XAMPP MySQL service.');
            }
        }
    }

    return $pdo;
}

