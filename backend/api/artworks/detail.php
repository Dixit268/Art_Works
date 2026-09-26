<?php
/**
 * REST API: Artwork Details
 * Method: GET
 * Query params: id
 */

require_once __DIR__ . '/../../includes/auth-api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError("Method Not Allowed. Use GET.", 405);
}

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    jsonError("Invalid or missing artwork ID.", 400);
}

try {
    $sql = "
        SELECT 
            a.id, 
            a.title, 
            a.category_id, 
            c.name AS category_name,
            c.description AS category_description,
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
            a.created_at,
            CASE 
                WHEN a.image_type = 'upload' AND a.image_path IS NOT NULL AND a.image_path != '' THEN a.image_path
                WHEN a.image_url IS NOT NULL AND a.image_url != '' THEN a.image_url
                ELSE 'assets/img/artwork-placeholder.jpg'
            END AS display_image
        FROM artworks a
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE a.id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        jsonError("Artwork not found.", 404);
    }

    // Check if user is logged in and if this artwork is in their wishlist
    $inWishlist = false;
    $user = authenticateApiUser();
    if ($user) {
        $wStmt = $conn->prepare("SELECT id FROM wishlists WHERE user_id = ? AND artwork_id = ? LIMIT 1");
        $wStmt->bind_param("ii", $user['id'], $id);
        $wStmt->execute();
        $inWishlist = (bool)$wStmt->get_result()->fetch_assoc();
        $wStmt->close();
    }

    $artwork = [
        'id' => (int)$row['id'],
        'title' => $row['title'],
        'category_id' => $row['category_id'] ? (int)$row['category_id'] : null,
        'category_name' => $row['category_name'] ?: 'Uncategorized',
        'category_description' => $row['category_description'],
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
        'featured' => (bool)$row['featured'],
        'created_at' => $row['created_at'],
        'in_wishlist' => $inWishlist
    ];

    jsonResponse([
        'success' => true,
        'data' => $artwork
    ]);

} catch (Exception $e) {
    jsonError("Database error: " . $e->getMessage(), 500);
}
