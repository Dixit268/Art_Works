<?php
/**
 * REST API: User's Own Inquiries
 * Method: GET (Authenticated User)
 */

require_once __DIR__ . '/../../includes/auth-api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError("Method Not Allowed. Use GET.", 405);
}

// Require User
$user = requireApiAuth();
$userEmail = $user['email'];

try {
    $sql = "
        SELECT 
            i.id,
            i.name,
            i.email,
            i.phone,
            i.artwork_id,
            a.title AS artwork_title,
            a.price AS artwork_price,
            a.image_type,
            a.image_path,
            a.image_url,
            i.message,
            i.status,
            i.created_at
        FROM inquiries i
        LEFT JOIN artworks a ON i.artwork_id = a.id
        WHERE LOWER(i.email) = LOWER(?)
        ORDER BY i.created_at DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $userEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    $inquiries = [];
    while ($row = $result->fetch_assoc()) {
        $artworkImage = null;
        if ($row['artwork_id']) {
            $artworkImage = ($row['image_type'] === 'upload' && !empty($row['image_path']))
                ? $row['image_path']
                : (!empty($row['image_url']) ? $row['image_url'] : 'assets/img/artwork-placeholder.jpg');
        }

        $inquiries[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'artwork_id' => $row['artwork_id'] ? (int)$row['artwork_id'] : null,
            'artwork_title' => $row['artwork_title'],
            'artwork_price' => $row['artwork_price'] ? (float)$row['artwork_price'] : null,
            'artwork_image' => $artworkImage,
            'message' => $row['message'],
            'status' => $row['status'],
            'created_at' => $row['created_at']
        ];
    }
    $stmt->close();

    jsonResponse([
        'success' => true,
        'count' => count($inquiries),
        'data' => $inquiries
    ]);

} catch (Exception $e) {
    jsonError("Database error: " . $e->getMessage(), 500);
}
