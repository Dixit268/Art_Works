<?php
/**
 * REST API: Category Detail
 * Method: GET
 * Query params: id
 */

require_once __DIR__ . '/../../includes/auth-api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError("Method Not Allowed. Use GET.", 405);
}

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    jsonError("Invalid or missing category ID.", 400);
}

try {
    $stmt = $conn->prepare("
        SELECT 
            c.id, 
            c.name, 
            c.description, 
            c.status,
            (SELECT COUNT(*) FROM artworks a WHERE a.category_id = c.id) AS artworks_count
        FROM categories c
        WHERE c.id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        jsonError("Category not found.", 404);
    }

    jsonResponse([
        'success' => true,
        'data' => [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'status' => $row['status'],
            'artworks_count' => (int)$row['artworks_count']
        ]
    ]);

} catch (Exception $e) {
    jsonError("Database error: " . $e->getMessage(), 500);
}
