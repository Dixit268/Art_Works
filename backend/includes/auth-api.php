<?php
/**
 * REST API Core Helper & Token Authentication Middleware (Backend)
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
 * Output standard JSON Error
 */
function jsonError($message, $statusCode = 400, $errors = null) {
    $res = [
        'success' => false,
        'message' => $message
    ];
    if ($errors !== null) {
        $res['errors'] = $errors;
    }
    jsonResponse($res, $statusCode);
}

/**
 * Parse JSON Request Body or POST form data
 */
function getJsonInput() {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
    return $_POST;
}

/**
 * Extract Bearer Token from Authorization header
 */
function getBearerToken() {
    $header = null;
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $header = trim($_SERVER['HTTP_AUTHORIZATION']);
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $header = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        if (isset($requestHeaders['Authorization'])) {
            $header = trim($requestHeaders['Authorization']);
        }
    }

    if (!empty($header)) {
        if (preg_match('/Bearer\s(\S+)/i', $header, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

/**
 * Validate Token and return authenticated user array or null
 */
function getApiUser() {
    global $conn;
    $token = getBearerToken();
    if (!$token) {
        return null;
    }

    $stmt = $conn->prepare("
        SELECT u.id, u.name, u.email, u.phone, u.role, u.created_at, t.expires_at 
        FROM tokens t
        INNER JOIN users u ON t.user_id = u.id
        WHERE t.token = ? AND t.expires_at > NOW()
        LIMIT 1
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    return $user ?: null;
}

/**
 * Require valid authenticated user or terminate with 401
 */
function requireApiAuth() {
    $user = getApiUser();
    if (!$user) {
        jsonError("Unauthorized. Valid token required.", 401);
    }
    return $user;
}

/**
 * Require admin user or terminate with 403
 */
function requireApiAdmin() {
    $user = requireApiAuth();
    if (($user['role'] ?? '') !== 'admin') {
        jsonError("Forbidden. Administrator access required.", 403);
    }
    return $user;
}

/**
 * Generate unique token and save into `tokens` table
 */
function createAuthToken($userId, $durationDays = 30) {
    global $conn;
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime("+{$durationDays} days"));

    $stmt = $conn->prepare("INSERT INTO tokens (token, user_id, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $token, $userId, $expiresAt);
    $stmt->execute();
    $stmt->close();

    return [
        'token' => $token,
        'expires_at' => $expiresAt
    ];
}

/**
 * Revoke/Delete token on logout
 */
function revokeAuthToken($token) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM tokens WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->close();
}
