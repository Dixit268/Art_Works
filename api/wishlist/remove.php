<?php
/**
 * REST API: Remove from Wishlist
 * Method: POST or DELETE
 */

require_once __DIR__ . '/../../includes/auth-api.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) {
    jsonError("Method Not Allowed. Use POST or DELETE.", 405);
}

// Require Authenticated User
$user = requireApiAuth();
$userId = (int)$user['id'];

$input = getJsonInput();

$artworkId = isset($input['artwork_id']) && is_numeric($input['artwork_id']) 
    ? (int)$input['artwork_id'] 
    : (isset($_GET['artwork_id']) && is_numeric($_GET['artwork_id']) ? (int)$_GET['artwork_id'] : 0);

$wishlistId = isset($input['id']) && is_numeric($input['id']) 
    ? (int)$input['id'] 
    : (isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0);

if ($artworkId <= 0 && $wishlistId <= 0) {
    jsonError("Specify artwork_id or wishlist id to remove.", 400);
}

try {
    if ($artworkId > 0) {
        $stmt = $conn->prepare("DELETE FROM wishlists WHERE user_id = ? AND artwork_id = ?");
        $stmt->bind_param("ii", $userId, $artworkId);
    } else {
        $stmt = $conn->prepare("DELETE FROM wishlists WHERE user_id = ? AND id = ?");
        $stmt->bind_param("ii", $userId, $wishlistId);
    }

    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected > 0) {
        jsonResponse([
            'success' => true,
            'message' => "Artwork removed from wishlist successfully."
        ]);
    } else {
        jsonResponse([
            'success' => true,
            'message' => "Artwork was not in your wishlist."
        ]);
    }

} catch (Exception $e) {
    jsonError("Database error: " . $e->getMessage(), 500);
}
