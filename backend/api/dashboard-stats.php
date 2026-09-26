<?php
/**
 * REST API: Admin Dashboard Statistics
 * Method: GET (Admin Only)
 */

require_once __DIR__ . '/../includes/auth-api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError("Method Not Allowed. Use GET.", 405);
}

// Require Admin
$admin = requireApiAdmin();

try {
    // 1. Artwork stats
    $artworksRes = $conn->query("
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN availability_status = 'available' THEN 1 ELSE 0 END) AS available,
            SUM(CASE WHEN availability_status = 'sold' THEN 1 ELSE 0 END) AS sold,
            SUM(CASE WHEN featured = 1 THEN 1 ELSE 0 END) AS featured
        FROM artworks
    ")->fetch_assoc();

    // 2. Category stats
    $categoriesRes = $conn->query("
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) AS inactive
        FROM categories
    ")->fetch_assoc();

    // 3. User stats
    $usersRes = $conn->query("
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) AS regular_users,
            SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) AS admin_users
        FROM users
    ")->fetch_assoc();

    // 4. Inquiry stats
    $inquiriesRes = $conn->query("
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status = 'contacted' THEN 1 ELSE 0 END) AS contacted,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS resolved
        FROM inquiries
    ")->fetch_assoc();

    // 5. Wishlists count
    $wishlistRes = $conn->query("SELECT COUNT(*) AS total FROM wishlists")->fetch_assoc();

    // 6. Recent 5 Artworks
    $recentArtworksRes = $conn->query("
        SELECT 
            a.id, 
            a.title, 
            a.price, 
            a.availability_status, 
            a.image_type, 
            a.image_path, 
            a.image_url, 
            a.created_at,
            c.name AS category_name
        FROM artworks a
        LEFT JOIN categories c ON a.category_id = c.id
        ORDER BY a.created_at DESC 
        LIMIT 5
    ");
    $recentArtworks = [];
    while ($row = $recentArtworksRes->fetch_assoc()) {
        $recentArtworks[] = [
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'category_name' => $row['category_name'] ?: 'Uncategorized',
            'price' => (float)$row['price'],
            'availability_status' => $row['availability_status'],
            'display_image' => ($row['image_type'] === 'upload' && !empty($row['image_path']))
                ? $row['image_path']
                : (!empty($row['image_url']) ? $row['image_url'] : 'assets/img/artwork-placeholder.jpg'),
            'created_at' => $row['created_at']
        ];
    }

    // 7. Recent 5 Inquiries
    $recentInquiriesRes = $conn->query("
        SELECT 
            i.id, 
            i.name, 
            i.email, 
            i.status, 
            i.created_at,
            a.title AS artwork_title
        FROM inquiries i
        LEFT JOIN artworks a ON i.artwork_id = a.id
        ORDER BY i.created_at DESC 
        LIMIT 5
    ");
    $recentInquiries = [];
    while ($row = $recentInquiriesRes->fetch_assoc()) {
        $recentInquiries[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'artwork_title' => $row['artwork_title'],
            'status' => $row['status'],
            'created_at' => $row['created_at']
        ];
    }

    jsonResponse([
        'success' => true,
        'stats' => [
            'artworks' => [
                'total' => (int)($artworksRes['total'] ?? 0),
                'available' => (int)($artworksRes['available'] ?? 0),
                'sold' => (int)($artworksRes['sold'] ?? 0),
                'featured' => (int)($artworksRes['featured'] ?? 0),
            ],
            'categories' => [
                'total' => (int)($categoriesRes['total'] ?? 0),
                'active' => (int)($categoriesRes['active'] ?? 0),
                'inactive' => (int)($categoriesRes['inactive'] ?? 0),
            ],
            'users' => [
                'total' => (int)($usersRes['total'] ?? 0),
                'regular_users' => (int)($usersRes['regular_users'] ?? 0),
                'admin_users' => (int)($usersRes['admin_users'] ?? 0),
            ],
            'inquiries' => [
                'total' => (int)($inquiriesRes['total'] ?? 0),
                'pending' => (int)($inquiriesRes['pending'] ?? 0),
                'contacted' => (int)($inquiriesRes['contacted'] ?? 0),
                'resolved' => (int)($inquiriesRes['resolved'] ?? 0),
            ],
            'wishlists' => [
                'total' => (int)($wishlistRes['total'] ?? 0),
            ]
        ],
        'recent_artworks' => $recentArtworks,
        'recent_inquiries' => $recentInquiries
    ]);

} catch (Exception $e) {
    jsonError("Database error: " . $e->getMessage(), 500);
}
