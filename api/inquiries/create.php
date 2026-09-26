<?php
/**
 * REST API: Submit Inquiry
 * Method: POST (Public)
 */

require_once __DIR__ . '/../../includes/auth-api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError("Method Not Allowed. Use POST.", 405);
}

$input = getJsonInput();

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$artworkId = !empty($input['artwork_id']) && is_numeric($input['artwork_id']) ? (int)$input['artwork_id'] : null;
$message = trim($input['message'] ?? '');

$errors = [];
if (empty($name)) {
    $errors['name'] = "Name is required.";
}
if (empty($email)) {
    $errors['email'] = "Email is required.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = "Please provide a valid email address.";
}
if (empty($message)) {
    $errors['message'] = "Inquiry message is required.";
}

if (!empty($errors)) {
    jsonError("Validation failed.", 422, $errors);
}

try {
    // If artwork_id provided, verify artwork exists
    $artworkTitle = null;
    if ($artworkId) {
        $artStmt = $conn->prepare("SELECT title FROM artworks WHERE id = ? LIMIT 1");
        $artStmt->bind_param("i", $artworkId);
        $artStmt->execute();
        $artRow = $artStmt->get_result()->fetch_assoc();
        $artStmt->close();
        if ($artRow) {
            $artworkTitle = $artRow['title'];
        } else {
            $artworkId = null; // Artwork deleted or not found
        }
    }

    $stmt = $conn->prepare("
        INSERT INTO inquiries (name, email, phone, artwork_id, message, status) 
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->bind_param("sssis", $name, $email, $phone, $artworkId, $message);
    
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        jsonError("Failed to submit inquiry: $err", 500);
    }

    $newId = (int)$stmt->insert_id;
    $stmt->close();

    jsonResponse([
        'success' => true,
        'message' => "Thank you for your inquiry. Our gallery curators will get in touch shortly.",
        'data' => [
            'id' => $newId,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'artwork_id' => $artworkId,
            'artwork_title' => $artworkTitle,
            'message' => $message,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]
    ], 201);

} catch (Exception $e) {
    jsonError("Database error: " . $e->getMessage(), 500);
}
