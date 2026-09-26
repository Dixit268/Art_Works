<?php
/**
 * REST API: User Profile & Change Password (Authenticated User)
 * Method: GET (retrieve), POST (update profile or change password)
 */

require_once __DIR__ . '/../../includes/auth-api.php';

// Require logged-in user
$user = requireApiAuth();
$userId = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Return latest profile
    $stmt = $conn->prepare("SELECT id, name, email, phone, role, created_at FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $userData = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    jsonResponse([
        'success' => true,
        'user' => [
            'id' => (int)$userData['id'],
            'name' => $userData['name'],
            'email' => $userData['email'],
            'phone' => $userData['phone'],
            'role' => $userData['role'],
            'created_at' => $userData['created_at']
        ]
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getJsonInput();
    $action = $input['action'] ?? 'update_profile'; // 'update_profile' or 'change_password'

    if ($action === 'change_password') {
        $currentPassword = $input['current_password'] ?? '';
        $newPassword = $input['new_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword)) {
            jsonError("Current password and new password are required.", 422);
        }

        if (strlen($newPassword) < 6) {
            jsonError("New password must be at least 6 characters.", 422);
        }

        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $dbPass = $stmt->get_result()->fetch_assoc()['password'];
        $stmt->close();

        if (!password_verify($currentPassword, $dbPass)) {
            jsonError("Incorrect current password.", 400);
        }

        // Update password
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
        $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $upd->bind_param("si", $hashed, $userId);
        $upd->execute();
        $upd->close();

        jsonResponse([
            'success' => true,
            'message' => "Password changed successfully."
        ]);
    } else {
        // Update profile
        $name = trim($input['name'] ?? '');
        $phone = trim($input['phone'] ?? '');

        if (empty($name)) {
            jsonError("Name cannot be empty.", 422);
        }

        $upd = $conn->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
        $upd->bind_param("ssi", $name, $phone, $userId);
        $upd->execute();
        $upd->close();

        // Fetch updated user
        $stmt = $conn->prepare("SELECT id, name, email, phone, role, created_at FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $updatedUser = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        jsonResponse([
            'success' => true,
            'message' => "Profile updated successfully.",
            'user' => [
                'id' => (int)$updatedUser['id'],
                'name' => $updatedUser['name'],
                'email' => $updatedUser['email'],
                'phone' => $updatedUser['phone'],
                'role' => $updatedUser['role'],
                'created_at' => $updatedUser['created_at']
            ]
        ]);
    }
}

jsonError("Method Not Allowed.", 405);
