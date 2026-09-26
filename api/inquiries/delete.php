<?php
/**
 * REST API: Delete Inquiry (Admin Only)
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
    jsonError("Invalid or missing inquiry ID.", 400);
}

try {
    $stmt = $conn->prepare("SELECT id FROM inquiries WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $inquiry = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$inquiry) {
        jsonError("Inquiry not found.", 404);
    }

    $delStmt = $conn->prepare("DELETE FROM inquiries WHERE id = ?");
    $delStmt->bind_param("i", $id);
    $delStmt->execute();
    $affected = $delStmt->affected_rows;
    $delStmt->close();

    if ($affected > 0) {
        jsonResponse([
            'success' => true,
            'message' => "Inquiry #$id deleted successfully."
        ]);
    } else {
        jsonError("Failed to delete inquiry.", 500);
    }

} catch (Exception $e) {
    jsonError("Database error: " . $e->getMessage(), 500);
}
