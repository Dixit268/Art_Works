<?php
/**
 * REST API: Delete Artwork (Admin Only)
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
    jsonError("Invalid or missing artwork ID.", 400);
}

try {
    // Check if artwork exists
    $stmt = $conn->prepare("SELECT id, image_type, image_path FROM artworks WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $artwork = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$artwork) {
        jsonError("Artwork not found.", 404);
    }

    // Delete uploaded image file if local
    if ($artwork['image_type'] === 'upload' && !empty($artwork['image_path'])) {
        $localPath = __DIR__ . '/../../' . ltrim($artwork['image_path'], '/\\');
        if (file_exists($localPath)) {
            @unlink($localPath);
        }
    }

    // Delete record (cascades wishlists and nullifies inquiries)
    $delStmt = $conn->prepare("DELETE FROM artworks WHERE id = ?");
    $delStmt->bind_param("i", $id);
    $delStmt->execute();
    $affected = $delStmt->affected_rows;
    $delStmt->close();

    if ($affected > 0) {
        jsonResponse([
            'success' => true,
            'message' => "Artwork #$id deleted successfully."
        ]);
    } else {
        jsonError("Failed to delete artwork.", 500);
    }

} catch (Exception $e) {
    jsonError("Database error: " . $e->getMessage(), 500);
}
