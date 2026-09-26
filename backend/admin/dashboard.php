<?php
$page_title = 'Executive Dashboard';
$active_menu = 'dashboard';
require_once __DIR__ . '/includes/admin_header.php';

// Fetch Counters
$total_artworks = 0;
$total_categories = 0;
$total_inquiries = 0;
$pending_inquiries = 0;
$total_users = 0;

try {
    $r1 = $conn->query("SELECT COUNT(*) AS c FROM artworks");
    if ($r1) $total_artworks = (int)$r1->fetch_assoc()['c'];

    $r2 = $conn->query("SELECT COUNT(*) AS c FROM categories");
    if ($r2) $total_categories = (int)$r2->fetch_assoc()['c'];

    $r3 = $conn->query("SELECT COUNT(*) AS c, SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS p FROM inquiries");
    if ($r3) {
        $row3 = $r3->fetch_assoc();
        $total_inquiries = (int)($row3['c'] ?? 0);
        $pending_inquiries = (int)($row3['p'] ?? 0);
    }

    $r4 = $conn->query("SELECT COUNT(*) AS c FROM users");
    if ($r4) $total_users = (int)$r4->fetch_assoc()['c'];
} catch (Exception $e) {
    // Fallback
}

// Fetch 5 Recent Artworks
$recent_artworks = [];
try {
    $q_art = $conn->query("
        SELECT a.*, c.name AS category_name 
        FROM artworks a 
        LEFT JOIN categories c ON a.category_id = c.id 
        ORDER BY a.id DESC 
        LIMIT 5
    ");
    if ($q_art) {
        while ($ra = $q_art->fetch_assoc()) {
            $recent_artworks[] = $ra;
        }
    }
} catch (Exception $e) {
    $recent_artworks = [];
}

// Fetch 5 Recent Inquiries
$recent_inquiries = [];
try {
    $q_inq = $conn->query("
        SELECT i.*, a.title AS artwork_title 
        FROM inquiries i 
        LEFT JOIN artworks a ON i.artwork_id = a.id 
        ORDER BY i.id DESC 
        LIMIT 5
    ");
    if ($q_inq) {
        while ($ri = $q_inq->fetch_assoc()) {
            $recent_inquiries[] = $ri;
        }
    }
} catch (Exception $e) {
    $recent_inquiries = [];
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 font-heading fw-bold mb-1" style="color: var(--primary-navy);">Executive Dashboard</h1>
        <p class="text-muted small mb-0">Overview of art pieces, categories, collectors, and customer inquiries.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="artworks.php" class="btn btn-primary-blue btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Add Artwork
        </a>
        <a href="../../frontend/index.php" target="_blank" class="btn btn-outline-secondary btn-sm" style="border-radius: var(--radius-md);">
            <i class="bi bi-box-arrow-up-right me-1"></i>Preview Site
        </a>
    </div>
</div>

<!-- 4 MODERN STAT CARDS -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="admin-stat-card">
            <div>
                <div class="stat-label">Total Artworks</div>
                <div class="stat-number"><?php echo $total_artworks; ?></div>
                <div class="text-muted" style="font-size: 0.75rem; margin-top: 4px;">In gallery catalog</div>
            </div>
            <div class="stat-icon-wrapper">
                <i class="bi bi-palette-fill"></i>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="admin-stat-card">
            <div>
                <div class="stat-label">Total Categories</div>
                <div class="stat-number"><?php echo $total_categories; ?></div>
                <div class="text-muted" style="font-size: 0.75rem; margin-top: 4px;">Curated styles</div>
            </div>
            <div class="stat-icon-wrapper">
                <i class="bi bi-grid-fill"></i>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="admin-stat-card">
            <div>
                <div class="stat-label">Total Users</div>
                <div class="stat-number"><?php echo $total_users; ?></div>
                <div class="text-muted" style="font-size: 0.75rem; margin-top: 4px;">Registered collectors</div>
            </div>
            <div class="stat-icon-wrapper">
                <i class="bi bi-people-fill"></i>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="admin-stat-card">
            <div>
                <div class="stat-label">Inquiries</div>
                <div class="stat-number"><?php echo $total_inquiries; ?></div>
                <div class="mt-1">
                    <?php if ($pending_inquiries > 0): ?>
                        <span class="badge rounded-pill bg-danger" style="font-size: 0.7rem;"><?php echo $pending_inquiries; ?> pending</span>
                    <?php else: ?>
                        <span class="text-muted" style="font-size: 0.75rem;">All answered</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="stat-icon-wrapper">
                <i class="bi bi-chat-heart-fill"></i>
            </div>
        </div>
    </div>
</div>

<!-- RECENT ARTWORKS & INQUIRIES TABLES -->
<div class="row g-4">
    <!-- Recent Artworks Table -->
    <div class="col-lg-7">
        <div class="admin-table-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h5 font-heading fw-bold mb-0" style="color: var(--primary-navy);">Recent Artworks</h2>
                <a href="artworks.php" class="small fw-semibold text-decoration-none" style="color: var(--primary-blue);">View All &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="table admin-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Artwork</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recent_artworks)): ?>
                            <?php foreach ($recent_artworks as $art): ?>
                                <tr>
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="<?php echo getArtworkImageSrc($art, 'admin'); ?>" alt="" class="table-thumb">
                                            <div>
                                                <div class="fw-bold text-dark small"><?php echo htmlspecialchars($art['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                <div class="text-muted" style="font-size: 0.75rem;"><?php echo htmlspecialchars($art['medium'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="small"><?php echo htmlspecialchars($art['category_name'] ?? 'General', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="small fw-bold" style="color: var(--primary-blue);">₹<?php echo number_format((float)$art['price'], 2); ?></td>
                                    <td>
                                        <span class="badge rounded-pill <?php echo $art['availability_status'] === 'available' ? 'bg-success' : 'bg-secondary'; ?>" style="font-size: 0.72rem; padding: 0.35rem 0.65rem;">
                                            <?php echo ucfirst($art['availability_status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="artworks.php?edit_id=<?php echo (int)$art['id']; ?>" class="btn btn-sm btn-outline-primary" style="border-radius: var(--radius-sm); font-size: 0.75rem;">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted small">No artworks catalogued yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Inquiries Table -->
    <div class="col-lg-5">
        <div class="admin-table-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h5 font-heading fw-bold mb-0" style="color: var(--primary-navy);">Recent Inquiries</h2>
                <a href="inquiries.php" class="small fw-semibold text-decoration-none" style="color: var(--primary-blue);">View All &rarr;</a>
            </div>
            <div class="p-0">
                <div class="list-group list-group-flush">
                    <?php if (!empty($recent_inquiries)): ?>
                        <?php foreach ($recent_inquiries as $inq): ?>
                            <div class="list-group-item p-3 border-bottom" style="border-color: #F1F5F9;">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <h6 class="mb-0 fw-bold" style="color: var(--primary-navy);"><?php echo htmlspecialchars($inq['name'], ENT_QUOTES, 'UTF-8'); ?></h6>
                                    <span class="badge rounded-pill <?php 
                                        echo ($inq['status'] === 'resolved') ? 'bg-success' : (($inq['status'] === 'contacted') ? 'bg-primary' : 'bg-warning text-dark'); 
                                    ?>" style="font-size: 0.7rem; padding: 0.35rem 0.6rem;">
                                        <?php echo ucfirst($inq['status']); ?>
                                    </span>
                                </div>
                                <div class="small text-muted mb-2">
                                    <?php if (!empty($inq['artwork_title'])): ?>
                                        Piece: <strong class="text-dark"><?php echo htmlspecialchars($inq['artwork_title'], ENT_QUOTES, 'UTF-8'); ?></strong> &bull;
                                    <?php endif; ?>
                                    <?php echo date('M d, Y', strtotime($inq['created_at'])); ?>
                                </div>
                                <p class="small text-muted mb-0 text-truncate fst-italic">
                                    "<?php echo htmlspecialchars($inq['message'], ENT_QUOTES, 'UTF-8'); ?>"
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted small">No client inquiries received yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
