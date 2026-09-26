<?php
/**
 * REST API: Delete User (Admin Only)
 * Method: POST or DELETE
 */

require_once __DIR__ . '/../../includes/auth-api.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) {
    jsonError("Method Not Allowed. Use POST or DELETE.", 405);
}

// Require Admin
$admin = requireApiAdmin();

$input = getJsonInput();
$id = isset($input['id']) && is_numeric($input['id']) ? (int)$input['id'] : (isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0);

if ($id <= 0) {
    jsonError("Invalid or missing user ID.", 400);
}

// Prevent self-deletion
if ($id === (int)$admin['id']) {
    jsonError("You cannot delete your own administrator account.", 400);
}

try {
    $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        jsonError("User not found.", 404);
    }

    $delStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $delStmt->bind_param("i", $id);
    $delStmt->execute();
    $affected = $delStmt->affected_rows;
    $delStmt->close();

    if ($affected > 0) {
        jsonResponse([
            'success' => true,
            'message' => "User \"{$user['name']}\" deleted successfully."
        ]);
    } else {
        jsonError("Failed to delete user.", 500);
    }

} catch (Exception $e) {
    jsonError("Database error: " . $e->getMessage(), 500);
}
