<?php
/**
 * Database Configuration & Connection (MySQLi)
 */

if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'art_gallery');
if (!defined('DB_PORT')) define('DB_PORT', 3306);

// Enable MySQLi error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    // Show friendly error message in production, detailed for development
    die("Database Connection Error: " . htmlspecialchars($e->getMessage()));
}

/**
 * Helper function for executing prepared statements safely
 * @param string $sql
 * @param string $types e.g. "ssi"
 * @param array $params
 * @return mysqli_stmt
 */
if (!function_exists('db_query')) {
    function db_query($sql, $types = "", $params = []) {
        global $conn;
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("MySQL prepare error: " . $conn->error);
        }
        if (!empty($params) && !empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt;
    }
}
