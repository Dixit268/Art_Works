<?php
/**
 * REST API: User Detail (Admin Only)
 * Method: GET
 * Query params: id
 */

require_once __DIR__ . '/../../includes/auth-api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError("Method Not Allowed. Use GET.", 405);
}

// Require Admin
$admin = requireApiAdmin();

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    jsonError("Invalid or missing user ID.", 400);
}

try {
    $stmt = $conn->prepare("SELECT id, name, email, phone, role, created_at FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        jsonError("User not found.", 404);
    }

    jsonResponse([
        'success' => true,
        'data' => [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'role' => $row['role'],
            'created_at' => $row['created_at']
        ]
    ]);

} catch (Exception $e) {
    jsonError("Database error: " . $e->getMessage(), 500);
}
