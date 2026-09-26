<?php
/**
 * REST API: User & Admin Logout
 * Method: POST
 */

require_once __DIR__ . '/../../includes/auth-api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError("Method Not Allowed. Use POST.", 405);
}

// Require valid Bearer token
$user = requireApiAuth();
$token = getBearerToken();

if ($token) {
    revokeApiToken($token);
}

jsonResponse([
    'success' => true,
    'message' => "Successfully logged out. Token revoked."
], 200);
