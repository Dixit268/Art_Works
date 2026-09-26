<?php
/**
 * REST API: Create Category (Admin Only)
 * Method: POST
 */

require_once __DIR__ . '/../../includes/auth-api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError("Method Not Allowed. Use POST.", 405);
}

// Require Admin
$admin = requireApiAdmin();

$input = getJsonInput();
$name = trim($input['name'] ?? '');
$description = trim($input['description'] ?? '');
$status = in_array(strtolower($input['status'] ?? ''), ['active', 'inactive']) ? strtolower($input['status']) : 'active';

$errors = [];
if (empty($name)) {
    $errors['name'] = "Category name is required.";
}

if (!empty($errors)) {
    jsonError("Validation failed.", 422, $errors);
}

try {
    // Check if category name already exists
    $checkStmt = $conn->prepare("SELECT id FROM categories WHERE LOWER(name) = LOWER(?) LIMIT 1");
    $checkStmt->bind_param("s", $name);
    $checkStmt->execute();
    if ($checkStmt->get_result()->fetch_assoc()) {
        $checkStmt->close();
        jsonError("A category with this name already exists.", 409);
    }
    $checkStmt->close();

    $stmt = $conn->prepare("INSERT INTO categories (name, description, status) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $description, $status);
    
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        jsonError("Failed to create category: $err", 500);
    }

    $newId = (int)$stmt->insert_id;
    $stmt->close();

    jsonResponse([
        'success' => true,
        'message' => "Category created successfully.",
        'data' => [
            'id' => $newId,
            'name' => $name,
            'description' => $description,
            'status' => $status,
            'artworks_count' => 0
        ]
    ], 201);

} catch (Exception $e) {
    jsonError("Database error: " . $e->getMessage(), 500);
}
