<?php
/**
 * REST API: List Artworks
 * Method: GET
 * Query params: search, category, price_min, price_max, featured, availability, sort, page, limit
 */

require_once __DIR__ . '/../../includes/auth-api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError("Method Not Allowed. Use GET.", 405);
}

try {
    // Parameters
    $search = trim($_GET['search'] ?? '');
    $category = trim($_GET['category'] ?? '');
    $priceMin = isset($_GET['price_min']) && is_numeric($_GET['price_min']) ? (float)$_GET['price_min'] : null;
    $priceMax = isset($_GET['price_max']) && is_numeric($_GET['price_max']) ? (float)$_GET['price_max'] : null;
    $featured = isset($_GET['featured']) && $_GET['featured'] !== '' ? (int)$_GET['featured'] : null;
    $availability = trim($_GET['availability'] ?? '');
    $sort = trim($_GET['sort'] ?? 'newest');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, min(100, (int)($_GET['limit'] ?? 12)));
    $offset = ($page - 1) * $limit;

    // Build WHERE clauses
    $where = ["1=1"];
    $params = [];
    $types = "";

    if ($search !== '') {
        $where[] = "(a.title LIKE ? OR a.description LIKE ? OR a.medium LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "sss";
    }

    if ($category !== '') {
        if (is_numeric($category)) {
            $where[] = "a.category_id = ?";
            $params[] = (int)$category;
            $types .= "i";
        } else {
            $where[] = "c.name LIKE ?";
            $params[] = "%{$category}%";
            $types .= "s";
        }
    }

    if ($priceMin !== null) {
        $where[] = "a.price >= ?";
        $params[] = $priceMin;
        $types .= "d";
    }

    if ($priceMax !== null) {
        $where[] = "a.price <= ?";
        $params[] = $priceMax;
        $types .= "d";
    }

    if ($featured !== null) {
        $where[] = "a.featured = ?";
        $params[] = $featured;
        $types .= "i";
    }

    if ($availability !== '' && in_array(strtolower($availability), ['available', 'sold'])) {
        $where[] = "a.availability_status = ?";
        $params[] = strtolower($availability);
        $types .= "s";
    }

    $whereClause = implode(" AND ", $where);

    // Sorting
    $orderBy = "a.created_at DESC";
    switch ($sort) {
        case 'price_asc':
            $orderBy = "a.price ASC";
            break;
        case 'price_desc':
            $orderBy = "a.price DESC";
            break;
        case 'title_asc':
            $orderBy = "a.title ASC";
            break;
        case 'title_desc':
            $orderBy = "a.title DESC";
            break;
        case 'oldest':
            $orderBy = "a.created_at ASC";
            break;
        case 'featured':
            $orderBy = "a.featured DESC, a.created_at DESC";
            break;
        default:
            $orderBy = "a.created_at DESC";
    }

    // Count Total
    $countSql = "SELECT COUNT(*) as total FROM artworks a LEFT JOIN categories c ON a.category_id = c.id WHERE $whereClause";
    $countStmt = $conn->prepare($countSql);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $total = (int)$countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();

    $totalPages = ceil($total / $limit);

    // Fetch Artworks
    $sql = "
        SELECT 
            a.id, 
            a.title, 
            a.category_id, 
            c.name AS category_name,
            a.description, 
            a.image_type, 
            a.image_path, 
            a.image_url, 
            a.price, 
            a.medium, 
            a.dimensions, 
            a.year_created, 
            a.availability_status, 
            a.featured, 
            a.created_at,
            CASE 
                WHEN a.image_type = 'upload' AND a.image_path IS NOT NULL AND a.image_path != '' THEN a.image_path
                WHEN a.image_url IS NOT NULL AND a.image_url != '' THEN a.image_url
                ELSE 'assets/img/artwork-placeholder.jpg'
            END AS display_image
        FROM artworks a
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE $whereClause
        ORDER BY $orderBy
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

    $artworks = [];
    while ($row = $result->fetch_assoc()) {
        $artworks[] = [
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'category_id' => $row['category_id'] ? (int)$row['category_id'] : null,
            'category_name' => $row['category_name'] ?: 'Uncategorized',
            'description' => $row['description'],
            'image_type' => $row['image_type'],
            'image_path' => $row['image_path'],
            'image_url' => $row['image_url'],
            'display_image' => $row['display_image'],
            'price' => (float)$row['price'],
            'medium' => $row['medium'],
            'dimensions' => $row['dimensions'],
            'year_created' => $row['year_created'] ? (int)$row['year_created'] : null,
            'availability_status' => $row['availability_status'],
            'featured' => (bool)$row['featured'],
            'created_at' => $row['created_at']
        ];
    }
    $stmt->close();

    jsonResponse([
        'success' => true,
        'data' => $artworks,
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
