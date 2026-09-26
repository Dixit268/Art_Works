<?php
/**
 * REST API: Update Inquiry Status (Admin Only)
 * Method: POST
 */

require_once __DIR__ . '/../../includes/auth-api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError("Method Not Allowed. Use POST.", 405);
}

// Require Admin
$admin = requireApiAdmin();

$input = getJsonInput();
$id = isset($input['id']) && is_numeric($input['id']) ? (int)$input['id'] : (isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0);
$status = strtolower(trim($input['status'] ?? ''));

$allowedStatuses = ['pending', 'contacted', 'resolved'];

if ($id <= 0) {
    jsonError("Invalid or missing inquiry ID.", 400);
}

if (!in_array($status, $allowedStatuses)) {
    jsonError("Invalid status. Allowed values: " . implode(', ', $allowedStatuses), 422);
}

try {
    $checkStmt = $conn->prepare("SELECT id FROM inquiries WHERE id = ? LIMIT 1");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    if (!$checkStmt->get_result()->fetch_assoc()) {
        $checkStmt->close();
        jsonError("Inquiry not found.", 404);
    }
    $checkStmt->close();

    $stmt = $conn->prepare("UPDATE inquiries SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        jsonError("Failed to update inquiry status: $err", 500);
    }
    $stmt->close();

    jsonResponse([
        'success' => true,
        'message' => "Inquiry status updated to '$status'.",
        'data' => [
            'id' => $id,
            'status' => $status
        ]
    ]);

} catch (Exception $e) {
    jsonError("Database error: " . $e->getMessage(), 500);
}
