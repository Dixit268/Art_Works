<?php
/**
 * REST API: User Wishlist List
 * Method: GET
 */

require_once __DIR__ . '/../../includes/auth-api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError("Method Not Allowed. Use GET.", 405);
}

// Require Authenticated User
$user = requireApiAuth();
$userId = (int)$user['id'];

try {
    $sql = "
        SELECT 
            w.id AS wishlist_id,
            w.created_at AS added_at,
            a.id AS artwork_id,
            a.title,
            a.category_id,
            c.name AS category_name,
            a.description,
            a.image_type,
            a.image_path,
            a.image_url,
            a.price,
            a.medium,
            a.dimensions,
            a.year_created,
            a.availability_status,
            a.featured,
            CASE 
                WHEN a.image_type = 'upload' AND a.image_path IS NOT NULL AND a.image_path != '' THEN a.image_path
                WHEN a.image_url IS NOT NULL AND a.image_url != '' THEN a.image_url
                ELSE 'assets/img/artwork-placeholder.jpg'
            END AS display_image
        FROM wishlists w
        JOIN artworks a ON w.artwork_id = a.id
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE w.user_id = ?
        ORDER BY w.created_at DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'wishlist_id' => (int)$row['wishlist_id'],
            'added_at' => $row['added_at'],
            'artwork' => [
                'id' => (int)$row['artwork_id'],
                'title' => $row['title'],
                'category_id' => $row['category_id'] ? (int)$row['category_id'] : null,
                'category_name' => $row['category_name'] ?: 'Uncategorized',
                'description' => $row['description'],
                'image_type' => $row['image_type'],
                'image_path' => $row['image_path'],
                'image_url' => $row['image_url'],
                'display_image' => $row['display_image'],
                'price' => (float)$row['price'],
                'medium' => $row['medium'],
                'dimensions' => $row['dimensions'],
                'year_created' => $row['year_created'] ? (int)$row['year_created'] : null,
                'availability_status' => $row['availability_status'],
                'featured' => (bool)$row['featured']
            ]
        ];
    }
    $stmt->close();

    jsonResponse([
        'success' => true,
        'count' => count($items),
        'data' => $items
    ]);

} catch (Exception $e) {
    jsonError("Database error: " . $e->getMessage(), 500);
}
