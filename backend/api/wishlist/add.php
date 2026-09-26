<?php
/**
 * REST API: Add to Wishlist
 * Method: POST
 */

require_once __DIR__ . '/../../includes/auth-api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError("Method Not Allowed. Use POST.", 405);
}

// Require Authenticated User
$user = requireApiAuth();
$userId = (int)$user['id'];

$input = getJsonInput();
$artworkId = isset($input['artwork_id']) && is_numeric($input['artwork_id']) 
    ? (int)$input['artwork_id'] 
    : (isset($_GET['artwork_id']) && is_numeric($_GET['artwork_id']) ? (int)$_GET['artwork_id'] : 0);

if ($artworkId <= 0) {
    jsonError("Invalid or missing artwork_id.", 400);
}

try {
    // Check if artwork exists
    $artStmt = $conn->prepare("SELECT id, title FROM artworks WHERE id = ? LIMIT 1");
    $artStmt->bind_param("i", $artworkId);
    $artStmt->execute();
    $artwork = $artStmt->get_result()->fetch_assoc();
    $artStmt->close();

    if (!$artwork) {
        jsonError("Artwork not found.", 404);
    }

    // Check if already in wishlist
    $checkStmt = $conn->prepare("SELECT id FROM wishlists WHERE user_id = ? AND artwork_id = ? LIMIT 1");
    $checkStmt->bind_param("ii", $userId, $artworkId);
    $checkStmt->execute();
    $existing = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    if ($existing) {
        jsonResponse([
            'success' => true,
            'message' => "Artwork is already in your wishlist.",
            'wishlist_id' => (int)$existing['id'],
            'artwork_id' => $artworkId
        ], 200);
    }

    $stmt = $conn->prepare("INSERT INTO wishlists (user_id, artwork_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $userId, $artworkId);
    
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        jsonError("Failed to add artwork to wishlist: $err", 500);
    }

    $newId = (int)$stmt->insert_id;
    $stmt->close();

    jsonResponse([
        'success' => true,
        'message' => "Artwork \"{$artwork['title']}\" added to your wishlist.",
        'wishlist_id' => $newId,
        'artwork_id' => $artworkId
    ], 201);

} catch (Exception $e) {
    jsonError("Database error: " . $e->getMessage(), 500);
}
