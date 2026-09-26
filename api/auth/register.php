<?php
/**
 * REST API: User Registration
 * Method: POST
 */

require_once __DIR__ . '/../../includes/auth-api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError("Method Not Allowed. Use POST.", 405);
}

$input = getJsonInput();

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$password = $input['password'] ?? '';
$confirm_password = $input['confirm_password'] ?? '';

$errors = [];

if (empty($name)) {
    $errors['name'] = "Full name is required.";
}

if (empty($email)) {
    $errors['email'] = "Email address is required.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = "A valid email address is required.";
}

if (empty($password)) {
    $errors['password'] = "Password is required.";
} elseif (strlen($password) < 6) {
    $errors['password'] = "Password must be at least 6 characters long.";
}

if (!empty($confirm_password) && $password !== $confirm_password) {
    $errors['confirm_password'] = "Password confirmation does not match.";
}

// Check for existing user email
if (empty($errors)) {
    try {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->fetch_assoc()) {
            $errors['email'] = "An account with this email address already exists.";
        }
        $stmt->close();
    } catch (Exception $e) {
        jsonError("Database error during verification.", 500);
    }
}

if (!empty($errors)) {
    jsonError("Validation failed.", 422, $errors);
}

try {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role, created_at) VALUES (?, ?, ?, ?, 'user', NOW())");
    $stmt->bind_param("ssss", $name, $email, $phone, $hashed_password);
    
    if (!$stmt->execute()) {
        $stmt->close();
        jsonError("Registration failed. Please try again.", 500);
    }
    
    $user_id = $stmt->insert_id;
    $stmt->close();

    // Generate API Token
    $tokenData = createApiToken($user_id);

    jsonResponse([
        'success' => true,
        'message' => "User registered successfully.",
        'token' => $tokenData['token'],
        'expires_at' => $tokenData['expires_at'],
        'user' => [
            'id' => (int)$user_id,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'role' => 'user'
        ]
    ], 201);
} catch (Exception $e) {
    jsonError("An unexpected error occurred: " . $e->getMessage(), 500);
}
