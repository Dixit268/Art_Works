<?php
/**
 * REST API: List Inquiries (Admin Only)
 * Method: GET
 * Query params: status, artwork_id, search, page, limit
 */

require_once __DIR__ . '/../../includes/auth-api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError("Method Not Allowed. Use GET.", 405);
}

// Require Admin
$admin = requireApiAdmin();

try {
    $status = trim($_GET['status'] ?? 'all');
    $artworkId = isset($_GET['artwork_id']) && is_numeric($_GET['artwork_id']) ? (int)$_GET['artwork_id'] : null;
    $search = trim($_GET['search'] ?? '');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    $where = ["1=1"];
    $params = [];
    $types = "";

    if (in_array(strtolower($status), ['pending', 'contacted', 'resolved'])) {
        $where[] = "i.status = ?";
        $params[] = strtolower($status);
        $types .= "s";
    }

    if ($artworkId) {
        $where[] = "i.artwork_id = ?";
        $params[] = $artworkId;
        $types .= "i";
    }

    if ($search !== '') {
        $where[] = "(i.name LIKE ? OR i.email LIKE ? OR i.phone LIKE ? OR i.message LIKE ? OR a.title LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "sssss";
    }

    $whereClause = implode(" AND ", $where);

    // Count
    $countSql = "SELECT COUNT(*) as total FROM inquiries i LEFT JOIN artworks a ON i.artwork_id = a.id WHERE $whereClause";
    $countStmt = $conn->prepare($countSql);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $total = (int)$countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();

    $totalPages = ceil($total / $limit);

    // Fetch Inquiries
    $sql = "
        SELECT 
            i.id,
            i.name,
            i.email,
            i.phone,
            i.artwork_id,
            a.title AS artwork_title,
            a.price AS artwork_price,
            a.image_type,
            a.image_path,
            a.image_url,
            i.message,
            i.status,
            i.created_at
        FROM inquiries i
        LEFT JOIN artworks a ON i.artwork_id = a.id
        WHERE $whereClause
        ORDER BY i.created_at DESC
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

    $inquiries = [];
    while ($row = $result->fetch_assoc()) {
        $artworkImage = null;
        if ($row['artwork_id']) {
            $artworkImage = ($row['image_type'] === 'upload' && !empty($row['image_path']))
                ? $row['image_path']
                : (!empty($row['image_url']) ? $row['image_url'] : 'assets/img/artwork-placeholder.jpg');
        }

        $inquiries[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'artwork_id' => $row['artwork_id'] ? (int)$row['artwork_id'] : null,
            'artwork_title' => $row['artwork_title'],
            'artwork_price' => $row['artwork_price'] ? (float)$row['artwork_price'] : null,
            'artwork_image' => $artworkImage,
            'message' => $row['message'],
            'status' => $row['status'],
            'created_at' => $row['created_at']
        ];
    }
    $stmt->close();

    jsonResponse([
        'success' => true,
        'data' => $inquiries,
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
