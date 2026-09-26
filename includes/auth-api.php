<?php
/**
 * REST API Core Helper & Token Authentication Middleware
 */

// Disable direct HTML errors in API responses
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Set Global CORS and JSON Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Handle CORS preflight OPTIONS request
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['status' => 'ok', 'message' => 'CORS preflight OK']);
    exit;
}

// Require Database Configuration
require_once __DIR__ . '/../config/database.php';

/**
 * Ensure `tokens` table exists in database
 */
function ensureTokensTableExists($conn) {
    static $checked = false;
    if ($checked) return;
    $sql = "CREATE TABLE IF NOT EXISTS `tokens` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `token` VARCHAR(191) NOT NULL UNIQUE,
        `user_id` INT NOT NULL,
        `expires_at` DATETIME NOT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_token` (`token`),
        CONSTRAINT `fk_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    try {
        $conn->query($sql);
    } catch (Exception $e) {
        // Table may already exist
    }
    $checked = true;
}

ensureTokensTableExists($conn);

/**
 * Output standard JSON Response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Output standard JSON Error Response
 */
function jsonError($message, $statusCode = 400, $errors = []) {
    http_response_code($statusCode);
    $response = [
        'success' => false,
        'message' => $message
    ];
    if (!empty($errors)) {
        $response['errors'] = $errors;
    }
    echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Extract JSON Body or POST Form Data seamlessly
 */
function getJsonInput() {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return !empty($_POST) ? array_merge($_POST, $decoded) : $decoded;
        }
    }
    return !empty($_POST) ? $_POST : [];
}

/**
 * Extract Bearer Token from Authorization Header
 */
function getBearerToken() {
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER['Authorization']);
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }

    if (!empty($headers) && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
        return $matches[1];
    }
    return null;
}

/**
 * Generate a cryptographically secure token & save in `tokens` table
 */
function createApiToken($userId, $daysValid = 30) {
    global $conn;
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime("+$daysValid days"));

    $stmt = $conn->prepare("INSERT INTO `tokens` (`token`, `user_id`, `expires_at`) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $token, $userId, $expiresAt);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception("Failed to generate authentication token.");
    }
    $stmt->close();

    return [
        'token' => $token,
        'expires_at' => $expiresAt
    ];
}

/**
 * Invalidate a token on Logout
 */
function revokeApiToken($token) {
    global $conn;
    if (!$token) return false;
    $stmt = $conn->prepare("DELETE FROM `tokens` WHERE `token` = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $affected > 0;
}

/**
 * Authenticate current request via Bearer Token
 * Returns user array or null
 */
function authenticateApiUser() {
    global $conn;
    $token = getBearerToken();
    if (!$token) {
        return null;
    }

    try {
        $stmt = $conn->prepare("
            SELECT u.id, u.name, u.email, u.phone, u.role, u.created_at, t.expires_at 
            FROM `tokens` t 
            JOIN `users` u ON t.user_id = u.id 
            WHERE t.token = ? AND t.expires_at > NOW() 
            LIMIT 1
        ");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();

        return $user ?: null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Middleware: Require valid authenticated user
 */
function requireApiAuth() {
    $user = authenticateApiUser();
    if (!$user) {
        jsonError("Unauthorized. Valid Bearer Token required.", 401);
    }
    return $user;
}

/**
 * Middleware: Require Administrator privileges
 */
function requireApiAdmin() {
    $user = requireApiAuth();
    if (($user['role'] ?? '') !== 'admin') {
        jsonError("Forbidden. Administrator access required.", 403);
    }
    return $user;
}
