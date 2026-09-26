<?php
/**
 * REST API: Create User (Admin Only)
 * Method: POST
 */

require_once __DIR__ . '/../../includes/auth-api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError("Method Not Allowed. Use POST.", 405);
}

// Require Admin
$admin = requireApiAdmin();

$input = getJsonInput();

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$password = $input['password'] ?? '';
$role = in_array(strtolower($input['role'] ?? ''), ['user', 'admin']) ? strtolower($input['role']) : 'user';

$errors = [];
if (empty($name)) {
    $errors['name'] = "Name is required.";
}
if (empty($email)) {
    $errors['email'] = "Email is required.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = "Please provide a valid email address.";
}
if (empty($password)) {
    $errors['password'] = "Password is required.";
} elseif (strlen($password) < 6) {
    $errors['password'] = "Password must be at least 6 characters.";
}

if (!empty($errors)) {
    jsonError("Validation failed.", 422, $errors);
}

try {
    // Check email uniqueness
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    if ($checkStmt->get_result()->fetch_assoc()) {
        $checkStmt->close();
        jsonError("A user with this email address already exists.", 409, ['email' => 'Email is already registered.']);
    }
    $checkStmt->close();

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $phone, $hashedPassword, $role);
    
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        jsonError("Failed to create user: $err", 500);
    }

    $newId = (int)$stmt->insert_id;
    $stmt->close();

    jsonResponse([
        'success' => true,
        'message' => "User created successfully.",
        'data' => [
            'id' => $newId,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'role' => $role
        ]
    ], 201);

} catch (Exception $e) {
    jsonError("Database error: " . $e->getMessage(), 500);
}
