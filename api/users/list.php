<?php
/**
 * REST API: List Users (Admin Only)
 * Method: GET
 * Query params: role (user/admin/all), search, page, limit
 */

require_once __DIR__ . '/../../includes/auth-api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError("Method Not Allowed. Use GET.", 405);
}

// Require Admin
$admin = requireApiAdmin();

try {
    $role = trim($_GET['role'] ?? 'all');
    $search = trim($_GET['search'] ?? '');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    $where = ["1=1"];
    $params = [];
    $types = "";

    if (in_array(strtolower($role), ['user', 'admin'])) {
        $where[] = "u.role = ?";
        $params[] = strtolower($role);
        $types .= "s";
    }

    if ($search !== '') {
        $where[] = "(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "sss";
    }

    $whereClause = implode(" AND ", $where);

    // Total Count
    $countSql = "SELECT COUNT(*) as total FROM users u WHERE $whereClause";
    $countStmt = $conn->prepare($countSql);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $total = (int)$countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();

    $totalPages = ceil($total / $limit);

    // Fetch users with wishlist & inquiries counts
    $sql = "
        SELECT 
            u.id, 
            u.name, 
            u.email, 
            u.phone, 
            u.role, 
            u.created_at,
            (SELECT COUNT(*) FROM wishlists w WHERE w.user_id = u.id) AS wishlist_count
        FROM users u
        WHERE $whereClause
        ORDER BY u.id DESC
        LIMIT ? OFFSET ?
    ";

    $fetchParams = $params;
    $fetchParams[] = $limit;
    $fetchParams[] = $offset;
    $fetchTypes = $types . "ii";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($fetchTypes, ...$fetchParams);
    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'role' => $row['role'],
            'wishlist_count' => (int)$row['wishlist_count'],
            'created_at' => $row['created_at']
        ];
    }
    $stmt->close();

    jsonResponse([
        'success' => true,
        'data' => $users,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $totalPages,
            'has_more' => $page < $totalPages
        ]
    ]);

} catch (Exception $e) {
    jsonError("Database error: " . $e->getMessage(), 500);
}
