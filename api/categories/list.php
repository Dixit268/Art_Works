<?php
/**
 * REST API: List Categories
 * Method: GET
 * Query params: status (active/inactive/all)
 */

require_once __DIR__ . '/../../includes/auth-api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError("Method Not Allowed. Use GET.", 405);
}

try {
    $status = trim($_GET['status'] ?? 'all');

    $sql = "
        SELECT 
            c.id, 
            c.name, 
            c.description, 
            c.status,
            COUNT(a.id) AS artworks_count
        FROM categories c
        LEFT JOIN artworks a ON c.id = a.category_id
    ";

    $params = [];
    $types = "";

    if (in_array(strtolower($status), ['active', 'inactive'])) {
        $sql .= " WHERE c.status = ?";
        $params[] = strtolower($status);
        $types .= "s";
    }

    $sql .= " GROUP BY c.id ORDER BY c.name ASC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'status' => $row['status'],
            'artworks_count' => (int)$row['artworks_count']
        ];
    }
    $stmt->close();

    jsonResponse([
        'success' => true,
        'count' => count($categories),
        'data' => $categories
    ]);

} catch (Exception $e) {
    jsonError("Database error: " . $e->getMessage(), 500);
}
