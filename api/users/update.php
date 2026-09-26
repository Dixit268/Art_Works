<?php
/**
 * REST API: Update User (Admin Only)
 * Method: POST
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
    jsonError("Invalid or missing user ID.", 400);
}

try {
    // Check if user exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$existing) {
        jsonError("User not found.", 404);
    }

    $name = isset($input['name']) ? trim($input['name']) : $existing['name'];
    $email = isset($input['email']) ? trim($input['email']) : $existing['email'];
    $phone = isset($input['phone']) ? trim($input['phone']) : $existing['phone'];
    $role = isset($input['role']) && in_array(strtolower($input['role']), ['user', 'admin']) ? strtolower($input['role']) : $existing['role'];
    $newPassword = $input['password'] ?? null;

    $errors = [];
    if (empty($name)) {
        $errors['name'] = "Name cannot be empty.";
    }
    if (empty($email)) {
        $errors['email'] = "Email cannot be empty.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email address format.";
    }

    // Check duplicate email
    if (strtolower($email) !== strtolower($existing['email'])) {
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        $checkStmt->bind_param("si", $email, $id);
        $checkStmt->execute();
        if ($checkStmt->get_result()->fetch_assoc()) {
            $errors['email'] = "This email is already in use by another account.";
        }
        $checkStmt->close();
    }

    if (!empty($newPassword) && strlen($newPassword) < 6) {
        $errors['password'] = "New password must be at least 6 characters.";
    }

    if (!empty($errors)) {
        jsonError("Validation failed.", 422, $errors);
    }

    if (!empty($newPassword)) {
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
        $updStmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role = ?, password = ? WHERE id = ?");
        $updStmt->bind_param("sssssi", $name, $email, $phone, $role, $hashed, $id);
    } else {
        $updStmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role = ? WHERE id = ?");
        $updStmt->bind_param("ssssi", $name, $email, $phone, $role, $id);
    }

    if (!$updStmt->execute()) {
        $err = $updStmt->error;
        $updStmt->close();
        jsonError("Failed to update user: $err", 500);
    }
    $updStmt->close();

    jsonResponse([
        'success' => true,
        'message' => "User updated successfully.",
        'data' => [
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'role' => $role
        ]
    ]);

} catch (Exception $e) {
    jsonError("Database error: " . $e->getMessage(), 500);
}
