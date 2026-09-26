<?php
/**
 * REST API: User & Admin Login
 * Method: POST
 */

require_once __DIR__ . '/../../includes/auth-api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError("Method Not Allowed. Use POST.", 405);
}

$input = getJsonInput();

$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

$errors = [];
if (empty($email)) {
    $errors['email'] = "Email address is required.";
}
if (empty($password)) {
    $errors['password'] = "Password is required.";
}

if (!empty($errors)) {
    jsonError("Validation failed.", 422, $errors);
}

try {
    $stmt = $conn->prepare("SELECT id, name, email, phone, password, role FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user || !password_verify($password, $user['password'])) {
        jsonError("Invalid email address or password.", 401);
    }

    // Generate API Token
    $tokenData = createApiToken((int)$user['id']);

    jsonResponse([
        'success' => true,
        'message' => "Login successful.",
        'token' => $tokenData['token'],
        'expires_at' => $tokenData['expires_at'],
        'user' => [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'role' => $user['role']
        ]
    ], 200);

} catch (Exception $e) {
    jsonError("Authentication error: " . $e->getMessage(), 500);
}
