<?php
/**
 * REST API: Create Artwork (Admin Only)
 * Method: POST (supports JSON or multipart/form-data)
 */

require_once __DIR__ . '/../../includes/auth-api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError("Method Not Allowed. Use POST.", 405);
}

// Require Admin
$admin = requireApiAdmin();

$input = getJsonInput();

$title = trim($input['title'] ?? '');
$categoryId = !empty($input['category_id']) ? (int)$input['category_id'] : null;
$description = trim($input['description'] ?? '');
$price = isset($input['price']) && is_numeric($input['price']) ? (float)$input['price'] : null;
$medium = trim($input['medium'] ?? '');
$dimensions = trim($input['dimensions'] ?? '');
$yearCreated = !empty($input['year_created']) && is_numeric($input['year_created']) ? (int)$input['year_created'] : null;
$availabilityStatus = in_array(strtolower($input['availability_status'] ?? ''), ['available', 'sold']) ? strtolower($input['availability_status']) : 'available';
$featured = !empty($input['featured']) ? 1 : 0;
$imageType = in_array(strtolower($input['image_type'] ?? ''), ['upload', 'link']) ? strtolower($input['image_type']) : 'link';
$imageUrl = trim($input['image_url'] ?? '');
$imagePath = null;

// Validation
$errors = [];
if (empty($title)) {
    $errors['title'] = "Title is required.";
}
if ($price === null || $price < 0) {
    $errors['price'] = "A valid positive price is required.";
}

// Handle Image Upload if imageType is 'upload'
if ($imageType === 'upload') {
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Max 5MB
        if ($file['size'] > 5 * 1024 * 1024) {
            $errors['image'] = "Image size cannot exceed 5MB.";
        } elseif (!in_array($ext, $allowedExtensions)) {
            $errors['image'] = "Invalid file type. Allowed: " . implode(', ', $allowedExtensions);
        } else {
            // Verify MIME
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
                } else {
                    $errors['image'] = "Failed to upload image file.";
                }
            }
        }
    } else {
        if (!empty($input['image_path'])) {
            $imagePath = trim($input['image_path']);
        } else {
            $errors['image'] = "Image file is required when image type is 'upload'.";
        }
    }
} else {
    // Link mode
    if (empty($imageUrl)) {
        $errors['image_url'] = "Image URL is required when image type is 'link'.";
    } elseif (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
        $errors['image_url'] = "Invalid URL format.";
    }
}

if (!empty($errors)) {
    jsonError("Validation failed.", 422, $errors);
}

try {
    $stmt = $conn->prepare("
        INSERT INTO artworks (
            title, category_id, description, image_type, image_path, image_url,
            price, medium, dimensions, year_created, availability_status, featured
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sissssdssisi",
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
        $featured
    );

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        jsonError("Failed to create artwork: $err", 500);
    }

    $newId = (int)$stmt->insert_id;
    $stmt->close();

    // Fetch newly created artwork
    $fetchStmt = $conn->prepare("
        SELECT a.*, c.name AS category_name 
        FROM artworks a 
        LEFT JOIN categories c ON a.category_id = c.id 
        WHERE a.id = ?
    ");
    $fetchStmt->bind_param("i", $newId);
    $fetchStmt->execute();
    $newArtwork = $fetchStmt->get_result()->fetch_assoc();
    $fetchStmt->close();

    $newArtwork['display_image'] = ($newArtwork['image_type'] === 'upload' && !empty($newArtwork['image_path']))
        ? $newArtwork['image_path']
        : (!empty($newArtwork['image_url']) ? $newArtwork['image_url'] : 'assets/img/artwork-placeholder.jpg');

    jsonResponse([
        'success' => true,
        'message' => "Artwork created successfully.",
        'data' => $newArtwork
    ], 201);

} catch (Exception $e) {
    jsonError("Database error: " . $e->getMessage(), 500);
}
