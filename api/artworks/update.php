<?php
/**
 * REST API: Update Artwork (Admin Only)
 * Method: POST (supports JSON or multipart/form-data)
 */

require_once __DIR__ . '/../../includes/auth-api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError("Method Not Allowed. Use POST.", 405);
}

// Require Admin
$admin = requireApiAdmin();

$input = getJsonInput();

$id = isset($input['id']) && is_numeric($input['id']) ? (int)$input['id'] : (isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0);

if ($id <= 0) {
    jsonError("Invalid or missing artwork ID.", 400);
}

try {
    // Fetch existing artwork
    $checkStmt = $conn->prepare("SELECT * FROM artworks WHERE id = ? LIMIT 1");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $existing = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    if (!$existing) {
        jsonError("Artwork not found.", 404);
    }

    $title = isset($input['title']) ? trim($input['title']) : $existing['title'];
    $categoryId = array_key_exists('category_id', $input) ? (!empty($input['category_id']) ? (int)$input['category_id'] : null) : $existing['category_id'];
    $description = isset($input['description']) ? trim($input['description']) : $existing['description'];
    $price = isset($input['price']) && is_numeric($input['price']) ? (float)$input['price'] : (float)$existing['price'];
    $medium = isset($input['medium']) ? trim($input['medium']) : $existing['medium'];
    $dimensions = isset($input['dimensions']) ? trim($input['dimensions']) : $existing['dimensions'];
    $yearCreated = array_key_exists('year_created', $input) ? (!empty($input['year_created']) ? (int)$input['year_created'] : null) : $existing['year_created'];
    $availabilityStatus = isset($input['availability_status']) && in_array(strtolower($input['availability_status']), ['available', 'sold'])
        ? strtolower($input['availability_status'])
        : $existing['availability_status'];
    $featured = array_key_exists('featured', $input) ? (!empty($input['featured']) ? 1 : 0) : (int)$existing['featured'];
    $imageType = isset($input['image_type']) && in_array(strtolower($input['image_type']), ['upload', 'link'])
        ? strtolower($input['image_type'])
        : $existing['image_type'];
    $imageUrl = isset($input['image_url']) ? trim($input['image_url']) : $existing['image_url'];
    $imagePath = isset($input['image_path']) ? trim($input['image_path']) : $existing['image_path'];

    $errors = [];
    if (empty($title)) {
        $errors['title'] = "Title cannot be empty.";
    }
    if ($price < 0) {
        $errors['price'] = "Price must be a positive number.";
    }

    // Handle new uploaded image if present
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($file['size'] > 5 * 1024 * 1024) {
            $errors['image'] = "Image size cannot exceed 5MB.";
        } elseif (!in_array($ext, $allowedExtensions)) {
            $errors['image'] = "Invalid file type. Allowed: " . implode(', ', $allowedExtensions);
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (!in_array($mime, $allowedMimes)) {
                $errors['image'] = "Invalid image MIME type.";
            } else {
                $uploadDir = __DIR__ . '/../../uploads/artworks/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $filename = 'art_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $targetFile = $uploadDir . $filename;
                if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                    $imagePath = 'uploads/artworks/' . $filename;
                    $imageType = 'upload';
                } else {
                    $errors['image'] = "Failed to upload image file.";
                }
            }
        }
    }

    if (!empty($errors)) {
        jsonError("Validation failed.", 422, $errors);
    }

    $stmt = $conn->prepare("
        UPDATE artworks SET 
            title = ?, 
            category_id = ?, 
            description = ?, 
            image_type = ?, 
            image_path = ?, 
            image_url = ?, 
            price = ?, 
            medium = ?, 
            dimensions = ?, 
            year_created = ?, 
            availability_status = ?, 
            featured = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "sissssdssisii",
        $title,
        $categoryId,
        $description,
        $imageType,
        $imagePath,
        $imageUrl,
        $price,
        $medium,
        $dimensions,
        $yearCreated,
        $availabilityStatus,
        $featured,
        $id
    );

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        jsonError("Failed to update artwork: $err", 500);
    }
    $stmt->close();

    // Fetch updated artwork
    $fetchStmt = $conn->prepare("
        SELECT a.*, c.name AS category_name 
        FROM artworks a 
        LEFT JOIN categories c ON a.category_id = c.id 
        WHERE a.id = ?
    ");
    $fetchStmt->bind_param("i", $id);
    $fetchStmt->execute();
    $updatedArtwork = $fetchStmt->get_result()->fetch_assoc();
    $fetchStmt->close();

    $updatedArtwork['display_image'] = ($updatedArtwork['image_type'] === 'upload' && !empty($updatedArtwork['image_path']))
        ? $updatedArtwork['image_path']
        : (!empty($updatedArtwork['image_url']) ? $updatedArtwork['image_url'] : 'assets/img/artwork-placeholder.jpg');

    jsonResponse([
        'success' => true,
        'message' => "Artwork updated successfully.",
        'data' => $updatedArtwork
    ]);

} catch (Exception $e) {
    jsonError("Database error: " . $e->getMessage(), 500);
}
