<?php
/**
 * REST API: Monthly Revenue & Sales Analytics (Admin Only)
 * Method: GET
 */

require_once __DIR__ . '/../../includes/auth-api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError("Method Not Allowed. Use GET.", 405);
}

// Require Admin
$admin = requireApiAdmin();

$currentMonth = (int)date('n');
$currentYear = (int)date('Y');
$selectedYear = isset($_GET['year']) && is_numeric($_GET['year']) ? (int)$_GET['year'] : $currentYear;

try {
    // 1. Overall Summary Stats
    $totalRealizedRevenue = 0.0;
    $totalSoldCount = 0;
    $availableInventoryValue = 0.0;
    $availableCount = 0;

    $statQuery = $conn->query("
        SELECT 
            SUM(CASE WHEN availability_status = 'sold' THEN price ELSE 0 END) AS total_sold_revenue,
            SUM(CASE WHEN availability_status = 'sold' THEN 1 ELSE 0 END) AS total_sold_count,
            SUM(CASE WHEN availability_status = 'available' THEN price ELSE 0 END) AS available_inventory_value,
            SUM(CASE WHEN availability_status = 'available' THEN 1 ELSE 0 END) AS available_count
        FROM artworks
    ");

    if ($statQuery) {
        $row = $statQuery->fetch_assoc();
        $totalRealizedRevenue = (float)($row['total_sold_revenue'] ?? 0);
        $totalSoldCount = (int)($row['total_sold_count'] ?? 0);
        $availableInventoryValue = (float)($row['available_inventory_value'] ?? 0);
        $availableCount = (int)($row['available_count'] ?? 0);
    }

    $avgSalePrice = $totalSoldCount > 0 ? round($totalRealizedRevenue / $totalSoldCount, 2) : 0.0;

    // 2. Year Specific Stats
    $yearRevenue = 0.0;
    $yearSoldCount = 0;

    $yearStmt = $conn->prepare("
        SELECT 
            SUM(price) AS year_revenue,
            COUNT(*) AS year_sold_count
        FROM artworks 
        WHERE availability_status = 'sold' AND (YEAR(created_at) = ? OR year_created = ?)
    ");
    $yearStmt->bind_param("ii", $selectedYear, $selectedYear);
    $yearStmt->execute();
    $yearRes = $yearStmt->get_result()->fetch_assoc();
    $yearStmt->close();

    if ($yearRes) {
        $yearRevenue = (float)($yearRes['year_revenue'] ?? 0);
        $yearSoldCount = (int)($yearRes['year_sold_count'] ?? 0);
    }

    // 3. Current Month Revenue
    $currentMonthRevenue = 0.0;
    $currentMonthSoldCount = 0;

    $currMonthStmt = $conn->prepare("
        SELECT 
            SUM(price) AS month_revenue,
            COUNT(*) AS month_sold_count
        FROM artworks 
        WHERE availability_status = 'sold' AND (YEAR(created_at) = ? OR year_created = ?) AND MONTH(created_at) = ?
    ");
    $currMonthStmt->bind_param("iii", $currentYear, $currentYear, $currentMonth);
    $currMonthStmt->execute();
    $currMonthRes = $currMonthStmt->get_result()->fetch_assoc();
    $currMonthStmt->close();

    if ($currMonthRes) {
        $currentMonthRevenue = (float)($currMonthRes['month_revenue'] ?? 0);
        $currentMonthSoldCount = (int)($currMonthRes['month_sold_count'] ?? 0);
    }

    // 4. 12-Month Breakdown for Selected Year
    $monthlyData = [];
    $monthNames = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
    $monthShorts = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
    ];

    $monthQuery = $conn->prepare("
        SELECT 
            MONTH(created_at) AS month_num,
            COUNT(*) AS sold_count,
            SUM(price) AS total_revenue
        FROM artworks 
        WHERE availability_status = 'sold' AND (YEAR(created_at) = ? OR year_created = ?)
        GROUP BY MONTH(created_at)
        ORDER BY MONTH(created_at) ASC
    ");
    $monthQuery->bind_param("ii", $selectedYear, $selectedYear);
    $monthQuery->execute();
    $monthRes = $monthQuery->get_result();

    $monthlyMap = [];
    while ($mRow = $monthRes->fetch_assoc()) {
        $monthlyMap[(int)$mRow['month_num']] = [
            'sold_count' => (int)$mRow['sold_count'],
            'revenue' => (float)$mRow['total_revenue']
        ];
    }
    $monthQuery->close();

    $maxMonthlyRevenue = 0.0;
    for ($m = 1; $m <= 12; $m++) {
        $rev = isset($monthlyMap[$m]) ? $monthlyMap[$m]['revenue'] : 0.0;
        $cnt = isset($monthlyMap[$m]) ? $monthlyMap[$m]['sold_count'] : 0;
        if ($rev > $maxMonthlyRevenue) {
            $maxMonthlyRevenue = $rev;
        }

        $pct = $yearRevenue > 0 ? round(($rev / $yearRevenue) * 100, 1) : 0;

        $monthlyData[] = [
            'month' => $m,
            'month_name' => $monthNames[$m],
            'month_short' => $monthShorts[$m],
            'sold_count' => $cnt,
            'revenue' => $rev,
            'percentage_of_year' => $pct
        ];
    }

    // 5. Category-wise Revenue Breakdown
    $catStmt = $conn->prepare("
        SELECT 
            COALESCE(c.name, 'Uncategorized') AS category_name,
            COUNT(a.id) AS sold_count,
            SUM(a.price) AS total_revenue
        FROM artworks a
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE a.availability_status = 'sold'
        GROUP BY a.category_id, c.name
        ORDER BY total_revenue DESC
    ");
    $catStmt->execute();
    $catRes = $catStmt->get_result();
    $categoryBreakdown = [];
    while ($catRow = $catRes->fetch_assoc()) {
        $catRev = (float)$catRow['total_revenue'];
        $catPct = $totalRealizedRevenue > 0 ? round(($catRev / $totalRealizedRevenue) * 100, 1) : 0;
        $categoryBreakdown[] = [
            'category_name' => $catRow['category_name'],
            'sold_count' => (int)$catRow['sold_count'],
            'revenue' => $catRev,
            'percentage' => $catPct
        ];
    }
    $catStmt->close();

    // 6. Recent Sold Artworks List
    $soldArtworks = [];
    $soldQuery = $conn->prepare("
        SELECT 
            a.id, 
            a.title, 
            a.price, 
            a.medium, 
            a.image_type, 
            a.image_path, 
            a.image_url, 
            a.created_at,
            c.name AS category_name,
            (SELECT COUNT(*) FROM inquiries i WHERE i.artwork_id = a.id) AS inquiry_count
        FROM artworks a
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE a.availability_status = 'sold'
        ORDER BY a.created_at DESC
        LIMIT 20
    ");
    $soldQuery->execute();
    $soldRes = $soldQuery->get_result();
    while ($sRow = $soldRes->fetch_assoc()) {
        $sRow['display_image'] = ($sRow['image_type'] === 'upload' && !empty($sRow['image_path']))
            ? $sRow['image_path']
            : (!empty($sRow['image_url']) ? $sRow['image_url'] : 'assets/img/artwork-placeholder.jpg');
        $soldArtworks[] = $sRow;
    }
    $soldQuery->close();

    // 7. Available Years (Union of created_at and year_created + recent range)
    $years = [];
    $yearListQuery = $conn->query("
        SELECT DISTINCT y FROM (
            SELECT YEAR(created_at) AS y FROM artworks WHERE created_at IS NOT NULL
            UNION
            SELECT year_created AS y FROM artworks WHERE year_created IS NOT NULL AND year_created >= 2000
        ) AS years_table
        ORDER BY y DESC
    ");
    if ($yearListQuery) {
        while ($yRow = $yearListQuery->fetch_assoc()) {
            if (!empty($yRow['y'])) {
                $years[] = (int)$yRow['y'];
            }
        }
    }
    for ($y = $currentYear; $y >= $currentYear - 4; $y--) {
        if (!in_array($y, $years)) {
            $years[] = $y;
        }
    }
    rsort($years);

    jsonResponse([
        'success' => true,
        'selected_year' => $selectedYear,
        'available_years' => $years,
        'summary' => [
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'total_realized_revenue' => $totalRealizedRevenue,
            'total_sold_count' => $totalSoldCount,
            'year_revenue' => $yearRevenue,
            'year_sold_count' => $yearSoldCount,
            'current_month_revenue' => $currentMonthRevenue,
            'current_month_sold_count' => $currentMonthSoldCount,
            'available_inventory_value' => $availableInventoryValue,
            'available_count' => $availableCount,
            'avg_sale_price' => $avgSalePrice
        ],
        'monthly_data' => $monthlyData,
        'max_monthly_revenue' => $maxMonthlyRevenue,
        'category_breakdown' => $categoryBreakdown,
        'sold_artworks' => $soldArtworks
    ]);

} catch (Exception $e) {
    jsonError("Database error calculating revenue: " . $e->getMessage(), 500);
}
