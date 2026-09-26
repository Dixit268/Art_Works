<?php
/**
 * REST API: Update Category (Admin Only)
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

if ($id <= 0) {
    jsonError("Invalid or missing category ID.", 400);
}

try {
    // Check if category exists
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$existing) {
        jsonError("Category not found.", 404);
    }

    $name = isset($input['name']) ? trim($input['name']) : $existing['name'];
    $description = isset($input['description']) ? trim($input['description']) : $existing['description'];
    $status = isset($input['status']) && in_array(strtolower($input['status']), ['active', 'inactive']) 
        ? strtolower($input['status']) 
        : $existing['status'];

    $errors = [];
    if (empty($name)) {
        $errors['name'] = "Category name cannot be empty.";
    }

    // Check name uniqueness if changed
    if (strtolower($name) !== strtolower($existing['name'])) {
        $checkStmt = $conn->prepare("SELECT id FROM categories WHERE LOWER(name) = LOWER(?) AND id != ? LIMIT 1");
        $checkStmt->bind_param("si", $name, $id);
        $checkStmt->execute();
        if ($checkStmt->get_result()->fetch_assoc()) {
            $errors['name'] = "Another category with this name already exists.";
        }
        $checkStmt->close();
    }

    if (!empty($errors)) {
        jsonError("Validation failed.", 422, $errors);
    }

    $updStmt = $conn->prepare("UPDATE categories SET name = ?, description = ?, status = ? WHERE id = ?");
    $updStmt->bind_param("sssi", $name, $description, $status, $id);
    
    if (!$updStmt->execute()) {
        $err = $updStmt->error;
        $updStmt->close();
        jsonError("Failed to update category: $err", 500);
    }
    $updStmt->close();

    // Get artworks count
    $cntStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM artworks WHERE category_id = ?");
    $cntStmt->bind_param("i", $id);
    $cntStmt->execute();
    $artworksCount = (int)$cntStmt->get_result()->fetch_assoc()['cnt'];
    $cntStmt->close();

    jsonResponse([
        'success' => true,
        'message' => "Category updated successfully.",
        'data' => [
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'status' => $status,
            'artworks_count' => $artworksCount
        ]
    ]);

} catch (Exception $e) {
    jsonError("Database error: " . $e->getMessage(), 500);
}
