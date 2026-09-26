<?php
/**
 * REST API: Delete Category (Admin Only)
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
    jsonError("Invalid or missing category ID.", 400);
}

try {
    // Check if category exists
    $stmt = $conn->prepare("SELECT id, name FROM categories WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $category = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$category) {
        jsonError("Category not found.", 404);
    }

    // Delete category (foreign key ON DELETE SET NULL on artworks)
    $delStmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $delStmt->bind_param("i", $id);
    $delStmt->execute();
    $affected = $delStmt->affected_rows;
    $delStmt->close();

    if ($affected > 0) {
        jsonResponse([
            'success' => true,
            'message' => "Category \"{$category['name']}\" deleted successfully."
        ]);
    } else {
        jsonError("Failed to delete category.", 500);
    }

} catch (Exception $e) {
    jsonError("Database error: " . $e->getMessage(), 500);
}
