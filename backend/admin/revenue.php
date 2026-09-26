<?php
$page_title = 'Revenue & Sales Analytics';
$active_menu = 'revenue';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/includes/auth.php';
require_once __DIR__ . '/../../backend/includes/image_helper.php';

requireAdmin('login.php');

$current_month = (int)date('n');
$current_year = (int)date('Y');
$selected_year = isset($_GET['year']) && is_numeric($_GET['year']) ? (int)$_GET['year'] : $current_year;

// 1. Available Years (Union of created_at and year_created + recent range)
$available_years = [];
try {
    $yQuery = $conn->query("
        SELECT DISTINCT y FROM (
            SELECT YEAR(created_at) AS y FROM artworks WHERE created_at IS NOT NULL
            UNION
            SELECT year_created AS y FROM artworks WHERE year_created IS NOT NULL AND year_created >= 2000
        ) AS years_table
        ORDER BY y DESC
    ");
    if ($yQuery) {
        while ($yRow = $yQuery->fetch_assoc()) {
            if (!empty($yRow['y'])) {
                $available_years[] = (int)$yRow['y'];
            }
        }
    }
} catch (Exception $e) {}

for ($y = $current_year; $y >= $current_year - 4; $y--) {
    if (!in_array($y, $available_years)) {
        $available_years[] = $y;
    }
}
rsort($available_years);

// 2. Overall Summary Stats
$total_sold_revenue = 0.0;
$total_sold_count = 0;
$available_inventory_value = 0.0;
$available_count = 0;

try {
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
        $total_sold_revenue = (float)($row['total_sold_revenue'] ?? 0);
        $total_sold_count = (int)($row['total_sold_count'] ?? 0);
        $available_inventory_value = (float)($row['available_inventory_value'] ?? 0);
        $available_count = (int)($row['available_count'] ?? 0);
    }
} catch (Exception $e) {}

$avg_sale_price = $total_sold_count > 0 ? round($total_sold_revenue / $total_sold_count, 2) : 0.0;

// 3. Year Specific Stats
$year_revenue = 0.0;
$year_sold_count = 0;

try {
    $yearStmt = $conn->prepare("
        SELECT 
            SUM(price) AS year_revenue,
            COUNT(*) AS year_sold_count
        FROM artworks 
        WHERE availability_status = 'sold' AND (YEAR(created_at) = ? OR year_created = ?)
    ");
    $yearStmt->bind_param("ii", $selected_year, $selected_year);
    $yearStmt->execute();
    $yearRes = $yearStmt->get_result()->fetch_assoc();
    $yearStmt->close();

    if ($yearRes) {
        $year_revenue = (float)($yearRes['year_revenue'] ?? 0);
        $year_sold_count = (int)($yearRes['year_sold_count'] ?? 0);
    }
} catch (Exception $e) {}

// 4. Current Month Revenue
$month_revenue = 0.0;
$month_sold_count = 0;

try {
    $currMonthStmt = $conn->prepare("
        SELECT 
            SUM(price) AS month_revenue,
            COUNT(*) AS month_sold_count
        FROM artworks 
        WHERE availability_status = 'sold' AND (YEAR(created_at) = ? OR year_created = ?) AND MONTH(created_at) = ?
    ");
    $currMonthStmt->bind_param("iii", $current_year, $current_year, $current_month);
    $currMonthStmt->execute();
    $currMonthRes = $currMonthStmt->get_result()->fetch_assoc();
    $currMonthStmt->close();

    if ($currMonthRes) {
        $month_revenue = (float)($currMonthRes['month_revenue'] ?? 0);
        $month_sold_count = (int)($currMonthRes['month_sold_count'] ?? 0);
    }
} catch (Exception $e) {}

// 5. Monthly Breakdown for Selected Year
$month_names = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];
$month_shorts = [
    1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
    5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
    9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
];

$monthly_data = [];
$monthly_map = [];

try {
    $mQuery = $conn->prepare("
        SELECT 
            MONTH(created_at) AS month_num,
            COUNT(*) AS sold_count,
            SUM(price) AS total_revenue
        FROM artworks 
        WHERE availability_status = 'sold' AND (YEAR(created_at) = ? OR year_created = ?)
        GROUP BY MONTH(created_at)
        ORDER BY MONTH(created_at) ASC
    ");
    $mQuery->bind_param("ii", $selected_year, $selected_year);
    $mQuery->execute();
    $mRes = $mQuery->get_result();

    while ($mRow = $mRes->fetch_assoc()) {
        $monthly_map[(int)$mRow['month_num']] = [
            'sold_count' => (int)$mRow['sold_count'],
            'revenue' => (float)$mRow['total_revenue']
        ];
    }
    $mQuery->close();
} catch (Exception $e) {}

$max_monthly_rev = 1.0;
for ($m = 1; $m <= 12; $m++) {
    $rev = isset($monthly_map[$m]) ? $monthly_map[$m]['revenue'] : 0.0;
    $cnt = isset($monthly_map[$m]) ? $monthly_map[$m]['sold_count'] : 0;
    if ($rev > $max_monthly_rev) {
        $max_monthly_rev = $rev;
    }

    $pct = $year_revenue > 0 ? round(($rev / $year_revenue) * 100, 1) : 0;

    $monthly_data[] = [
        'month' => $m,
        'name' => $month_names[$m],
        'short' => $month_shorts[$m],
        'sold_count' => $cnt,
        'revenue' => $rev,
        'percentage' => $pct
    ];
}

// 6. Category Breakdown
$category_breakdown = [];
try {
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
    while ($catRow = $catRes->fetch_assoc()) {
        $catRev = (float)$catRow['total_revenue'];
        $catPct = $total_sold_revenue > 0 ? round(($catRev / $total_sold_revenue) * 100, 1) : 0;
        $category_breakdown[] = [
            'name' => $catRow['category_name'],
            'sold_count' => (int)$catRow['sold_count'],
            'revenue' => $catRev,
            'percentage' => $catPct
        ];
    }
    $catStmt->close();
} catch (Exception $e) {}

// 7. Recent Sold Pieces
$recent_sold = [];
try {
    $soldQuery = $conn->prepare("
        SELECT 
            a.*, 
            c.name AS category_name,
            (SELECT COUNT(*) FROM inquiries i WHERE i.artwork_id = a.id) AS inquiry_count
        FROM artworks a
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE a.availability_status = 'sold'
        ORDER BY a.created_at DESC
        LIMIT 10
    ");
    $soldQuery->execute();
    $soldRes = $soldQuery->get_result();
    while ($sRow = $soldRes->fetch_assoc()) {
        $recent_sold[] = $sRow;
    }
    $soldQuery->close();
} catch (Exception $e) {}

require_once __DIR__ . '/includes/admin_header.php';
?>

<!-- SCREEN HEADER & CONTROLS (Hidden during print) -->
<div class="d-print-none">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 font-heading fw-bold mb-1" style="color: var(--primary-navy);">Revenue & Sales Analytics</h1>
            <p class="text-muted small mb-0">Track monthly acquisition revenue, gallery sales performance, and sales breakdown in Indian Rupees (₹ INR).</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <form method="GET" action="revenue.php" class="d-flex align-items-center gap-2">
                <label for="yearSelect" class="small fw-semibold text-muted text-nowrap">Exhibition Year:</label>
                <select name="year" id="yearSelect" class="form-select form-select-sm rounded-pill px-3 fw-bold border-primary text-primary" style="width: 140px;" onchange="this.form.submit()">
                    <?php foreach ($available_years as $y): ?>
                        <option value="<?php echo $y; ?>" <?php echo ($selected_year === $y) ? 'selected' : ''; ?>>
                            FY <?php echo $y; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <button onclick="window.print()" class="btn btn-sm btn-primary-blue rounded-pill px-3 shadow-sm">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i>Print / Save PDF
            </button>
        </div>
    </div>

    <!-- STAT METRIC CARDS -->
    <div class="row g-3 mb-4">
        <!-- Total All-Time Revenue -->
        <div class="col-sm-6 col-xl-3">
            <div class="admin-stat-card revenue-stat-card">
                <div class="d-flex align-items-center justify-content-between w-100 mb-2">
                    <span class="stat-label">Total Realized Revenue</span>
                    <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #22C55E, #10B981);">
                        <i class="bi bi-currency-rupee text-white fs-5"></i>
                    </div>
                </div>
                <div class="stat-value text-success">
                    ₹<?php echo number_format($total_sold_revenue, 2); ?>
                </div>
                <div class="stat-meta">
                    <i class="bi bi-check2-all text-success me-1"></i><?php echo $total_sold_count; ?> original pieces acquired
                </div>
            </div>
        </div>

        <!-- Selected Year Revenue -->
        <div class="col-sm-6 col-xl-3">
            <div class="admin-stat-card revenue-stat-card">
                <div class="d-flex align-items-center justify-content-between w-100 mb-2">
                    <span class="stat-label">Year <?php echo $selected_year; ?> Revenue</span>
                    <div class="stat-icon-wrapper" style="background: var(--accent-gradient);">
                        <i class="bi bi-graph-up-arrow text-dark fs-5"></i>
                    </div>
                </div>
                <div class="stat-value" style="color: var(--primary-blue);">
                    ₹<?php echo number_format($year_revenue, 2); ?>
                </div>
                <div class="stat-meta">
                    <i class="bi bi-calendar-check text-primary me-1"></i><?php echo $year_sold_count; ?> sold in <?php echo $selected_year; ?>
                </div>
            </div>
        </div>

        <!-- Current Month Revenue -->
        <div class="col-sm-6 col-xl-3">
            <div class="admin-stat-card revenue-stat-card">
                <div class="d-flex align-items-center justify-content-between w-100 mb-2">
                    <span class="stat-label">This Month (<?php echo date('M Y'); ?>)</span>
                    <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #1B4DFF, #38BDF8);">
                        <i class="bi bi-wallet2 text-white fs-5"></i>
                    </div>
                </div>
                <div class="stat-value" style="color: var(--primary-navy);">
                    ₹<?php echo number_format($month_revenue, 2); ?>
                </div>
                <div class="stat-meta">
                    <i class="bi bi-bag-check text-info me-1"></i><?php echo $month_sold_count; ?> acquisitions this month
                </div>
            </div>
        </div>

        <!-- Available Stock Value -->
        <div class="col-sm-6 col-xl-3">
            <div class="admin-stat-card revenue-stat-card">
                <div class="d-flex align-items-center justify-content-between w-100 mb-2">
                    <span class="stat-label">Available Inventory Value</span>
                    <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #F59E0B, #FBBF24);">
                        <i class="bi bi-box-seam text-white fs-5"></i>
                    </div>
                </div>
                <div class="stat-value text-dark">
                    ₹<?php echo number_format($available_inventory_value, 2); ?>
                </div>
                <div class="stat-meta text-muted">
                    <i class="bi bi-palette me-1"></i><?php echo $available_count; ?> gallery pieces for sale
                </div>
            </div>
        </div>
    </div>

    <!-- MONTHLY VISUAL CHART -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white" style="border: 1px solid var(--border-color) !important;">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4 pb-2 border-bottom">
            <div>
                <h2 class="h5 font-heading fw-bold mb-1" style="color: var(--primary-navy);">
                    <i class="bi bi-bar-chart-fill text-primary me-2"></i>Month-by-Month Revenue Trajectory (<?php echo $selected_year; ?>)
                </h2>
                <p class="text-muted small mb-0">Visual distribution of monthly art sales in Indian Rupees (₹).</p>
            </div>
            <span class="badge rounded-pill bg-light text-primary border px-3 py-2 fw-semibold">
                Annual Total: ₹<?php echo number_format($year_revenue, 2); ?>
            </span>
        </div>

        <!-- Responsive Bar Chart Visual -->
        <div class="d-flex align-items-end justify-content-between gap-2 pt-4 pb-2 px-2 overflow-auto" style="min-height: 240px;">
            <?php foreach ($monthly_data as $m): 
                $barHeightPercent = $max_monthly_rev > 0 ? max(8, round(($m['revenue'] / $max_monthly_rev) * 100)) : 8;
                $hasRevenue = $m['revenue'] > 0;
            ?>
                <div class="d-flex flex-column align-items-center flex-grow-1" style="min-width: 55px;">
                    <!-- Value Label -->
                    <div class="small fw-bold mb-2 text-center" style="font-size: 0.72rem; color: <?php echo $hasRevenue ? 'var(--primary-blue)' : '#9CA3AF'; ?>;">
                        <?php echo $hasRevenue ? '₹' . ($m['revenue'] >= 1000 ? round($m['revenue']/1000, 1) . 'k' : $m['revenue']) : '₹0'; ?>
                    </div>

                    <!-- Bar Column -->
                    <div class="w-100 rounded-top-3 position-relative" 
                         style="height: <?php echo $barHeightPercent * 1.6; ?>px; 
                                background: <?php echo $hasRevenue ? 'linear-gradient(180deg, #38BDF8 0%, #1B4DFF 100%)' : '#E2E8F0'; ?>; 
                                box-shadow: <?php echo $hasRevenue ? '0 4px 12px rgba(27,77,255,0.2)' : 'none'; ?>;
                                transition: all 0.3s ease;"
                         title="<?php echo $m['name']; ?>: ₹<?php echo number_format($m['revenue'], 2); ?> (<?php echo $m['sold_count']; ?> pieces)">
                    </div>

                    <!-- Month Name -->
                    <div class="small fw-semibold mt-2 text-muted" style="font-size: 0.75rem;">
                        <?php echo $m['short']; ?>
                    </div>
                    <div class="badge rounded-pill bg-light text-muted border mt-1" style="font-size: 0.65rem; padding: 0.2rem 0.4rem;">
                        <?php echo $m['sold_count']; ?> pcs
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- OFFICIAL PDF SUMMARY REPORT CONTAINER (Visible on Screen & Perfectly Printed) -->
<!-- ========================================================================= -->
<div class="pdf-report-document bg-white p-4 p-md-5 rounded-4 shadow-sm mb-4 border">
    <!-- Formal Report Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-start border-bottom pb-4 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <div style="width: 38px; height: 38px; border-radius: 10px; background: var(--accent-gradient); display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-palette-fill text-dark fs-5"></i>
                </div>
                <div>
                    <h2 class="h4 font-heading fw-bold mb-0" style="color: var(--primary-navy); letter-spacing: -0.02em;">ART GALLERY</h2>
                    <span class="small text-muted text-uppercase" style="letter-spacing: 0.12em; font-size: 0.7rem;">Executive Curator Portal</span>
                </div>
            </div>
            <h1 class="h5 font-heading fw-bold mt-2" style="color: var(--primary-navy);">
                EXECUTIVE REVENUE & SALES STATEMENT
            </h1>
            <p class="text-muted small mb-0">Official financial ledger and monthly acquisition breakdown.</p>
        </div>

        <div class="text-sm-end mt-3 mt-sm-0">
            <div class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 fw-bold rounded-pill mb-2">
                FISCAL YEAR <?php echo $selected_year; ?>
            </div>
            <div class="small text-muted"><strong>Generated:</strong> <?php echo date('d M Y, h:i A'); ?></div>
            <div class="small text-muted"><strong>Curator In-Charge:</strong> <?php echo htmlspecialchars($current_admin_user['name'] ?? 'Curator Administrator', ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="small text-muted"><strong>Currency:</strong> INR (₹ Indian Rupees)</div>
        </div>
    </div>

    <!-- KPI Summary Grid for PDF/Print -->
    <div class="row g-3 mb-4">
        <div class="col-3">
            <div class="p-3 border rounded-3 bg-light">
                <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Total Revenue</div>
                <div class="h5 fw-bold text-success mb-0 mt-1">₹<?php echo number_format($total_sold_revenue, 2); ?></div>
                <div class="small text-muted" style="font-size: 0.72rem;"><?php echo $total_sold_count; ?> total sales</div>
            </div>
        </div>
        <div class="col-3">
            <div class="p-3 border rounded-3 bg-light">
                <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">FY <?php echo $selected_year; ?> Revenue</div>
                <div class="h5 fw-bold text-primary mb-0 mt-1">₹<?php echo number_format($year_revenue, 2); ?></div>
                <div class="small text-muted" style="font-size: 0.72rem;"><?php echo $year_sold_count; ?> sold in FY <?php echo $selected_year; ?></div>
            </div>
        </div>
        <div class="col-3">
            <div class="p-3 border rounded-3 bg-light">
                <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Avg Sale / Piece</div>
                <div class="h5 fw-bold text-dark mb-0 mt-1">₹<?php echo number_format($avg_sale_price, 2); ?></div>
                <div class="small text-muted" style="font-size: 0.72rem;">Per acquisition</div>
            </div>
        </div>
        <div class="col-3">
            <div class="p-3 border rounded-3 bg-light">
                <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Active Inventory</div>
                <div class="h5 fw-bold text-secondary mb-0 mt-1">₹<?php echo number_format($available_inventory_value, 2); ?></div>
                <div class="small text-muted" style="font-size: 0.72rem;"><?php echo $available_count; ?> pieces available</div>
            </div>
        </div>
    </div>

    <!-- 12-Month Detailed Ledger Table -->
    <div class="mb-4">
        <h3 class="h6 font-heading fw-bold mb-2" style="color: var(--primary-navy);">
            1. Month-Wise Sales & Acquisition Ledger (FY <?php echo $selected_year; ?>)
        </h3>
        <div class="table-responsive border rounded-3 overflow-hidden">
            <table class="table table-bordered align-middle mb-0" style="font-size: 0.85rem;">
                <thead class="table-light text-uppercase">
                    <tr>
                        <th style="width: 25%;">Month</th>
                        <th style="width: 20%;" class="text-center">Artworks Sold</th>
                        <th style="width: 25%;">Realized Revenue (₹)</th>
                        <th style="width: 15%;" class="text-center">Share (%)</th>
                        <th style="width: 15%;" class="text-end">Avg / Piece (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monthly_data as $m): 
                        $avg_m = $m['sold_count'] > 0 ? $m['revenue'] / $m['sold_count'] : 0;
                    ?>
                        <tr>
                            <td class="fw-semibold">
                                <i class="bi bi-calendar3 me-2 text-primary d-print-none"></i><?php echo $m['name']; ?>
                            </td>
                            <td class="text-center fw-bold">
                                <?php echo $m['sold_count']; ?>
                            </td>
                            <td class="fw-bold" style="color: <?php echo $m['revenue'] > 0 ? 'var(--primary-blue)' : '#64748B'; ?>;">
                                ₹<?php echo number_format($m['revenue'], 2); ?>
                            </td>
                            <td class="text-center">
                                <?php echo $m['percentage']; ?>%
                            </td>
                            <td class="text-end">
                                <?php echo $m['sold_count'] > 0 ? '₹' . number_format($avg_m, 2) : '—'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td>Total Fiscal Year <?php echo $selected_year; ?></td>
                        <td class="text-center"><?php echo $year_sold_count; ?> pieces</td>
                        <td class="text-primary">₹<?php echo number_format($year_revenue, 2); ?></td>
                        <td class="text-center">100%</td>
                        <td class="text-end">
                            <?php echo $year_sold_count > 0 ? '₹' . number_format($year_revenue / $year_sold_count, 2) : '—'; ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Category Contribution Summary -->
    <div class="mb-4">
        <h3 class="h6 font-heading fw-bold mb-2" style="color: var(--primary-navy);">
            2. Revenue Contribution by Art Medium / Genre
        </h3>
        <div class="table-responsive border rounded-3 overflow-hidden">
            <table class="table table-bordered table-sm align-middle mb-0" style="font-size: 0.85rem;">
                <thead class="table-light text-uppercase">
                    <tr>
                        <th>Medium / Category</th>
                        <th class="text-center">Sold Count</th>
                        <th>Realized Revenue (₹)</th>
                        <th class="text-center">Share of Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($category_breakdown)): ?>
                        <?php foreach ($category_breakdown as $cb): ?>
                            <tr>
                                <td class="fw-semibold"><?php echo htmlspecialchars($cb['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-center"><?php echo $cb['sold_count']; ?></td>
                                <td class="fw-bold">₹<?php echo number_format($cb['revenue'], 2); ?></td>
                                <td class="text-center"><?php echo $cb['percentage']; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-3 text-muted">No sales categorized yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Formal Sign-Off Box (Print/PDF) -->
    <div class="row pt-4 mt-4 border-top">
        <div class="col-6">
            <div class="small text-muted">Curator Verification:</div>
            <div class="fw-bold mt-1" style="color: var(--primary-navy);"><?php echo htmlspecialchars($current_admin_user['name'] ?? 'Curator Admin', ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="small text-muted">Authorized Gallery Registrar</div>
        </div>
        <div class="col-6 text-end">
            <div class="small text-muted">Authorized Signature & Seal:</div>
            <div class="d-inline-block border-bottom border-dark mt-4" style="width: 200px;"></div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
