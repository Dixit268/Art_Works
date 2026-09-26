<?php
$page_title = 'Collector Inquiries';
$active_menu = 'inquiries';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/includes/auth.php';

requireAdmin('login.php');

$success_msg = $_SESSION['flash_success'] ?? null;
$error_msg = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Status Update Action
if (isset($_GET['action']) && $_GET['action'] === 'status' && isset($_GET['id']) && isset($_GET['status'])) {
    $id = (int)$_GET['id'];
    $status = in_array($_GET['status'], ['pending', 'contacted', 'resolved']) ? $_GET['status'] : 'pending';
    try {
        $stmt = $conn->prepare("UPDATE inquiries SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash_success'] = "Inquiry #$id status set to " . ucfirst($status) . ".";
    } catch (Exception $e) {
        $_SESSION['flash_error'] = "Error updating status.";
    }
    header("Location: inquiries.php");
    exit;
}

// Delete Action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $conn->prepare("DELETE FROM inquiries WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash_success'] = "Inquiry record deleted.";
    } catch (Exception $e) {
        $_SESSION['flash_error'] = "Error deleting inquiry.";
    }
    header("Location: inquiries.php");
    exit;
}

// Filter by status
$status_filter = trim($_GET['status'] ?? 'all');
$where_clauses = ["1=1"];
$params = [];
$types = "";

if (in_array($status_filter, ['pending', 'contacted', 'resolved'])) {
    $where_clauses[] = "i.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$where_sql = implode(" AND ", $where_clauses);

// Fetch Inquiries
$inquiries = [];
try {
    $q_sql = "
        SELECT i.*, a.title AS artwork_title, a.price AS artwork_price 
        FROM inquiries i 
        LEFT JOIN artworks a ON i.artwork_id = a.id 
        WHERE $where_sql 
        ORDER BY i.id DESC
    ";
    $q_stmt = $conn->prepare($q_sql);
    if (!empty($params)) {
        $q_stmt->bind_param($types, ...$params);
    }
    $q_stmt->execute();
    $res = $q_stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $inquiries[] = $row;
    }
    $q_stmt->close();
} catch (Exception $e) {
    $inquiries = [];
}

require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 font-heading fw-bold mb-1" style="color: var(--primary-navy);">Collector Inquiries</h1>
        <p class="text-muted small mb-0">Track purchase inquiries, private viewing requests, and collector correspondence.</p>
    </div>
    <!-- Status Filter Buttons -->
    <div class="btn-group btn-group-sm p-1 bg-white rounded-pill shadow-sm border">
        <a href="inquiries.php?status=all" class="btn <?php echo ($status_filter === 'all') ? 'btn-primary-blue text-white' : 'btn-light'; ?> rounded-pill px-3">All</a>
        <a href="inquiries.php?status=pending" class="btn <?php echo ($status_filter === 'pending') ? 'btn-warning text-dark' : 'btn-light'; ?> rounded-pill px-3">Pending</a>
        <a href="inquiries.php?status=contacted" class="btn <?php echo ($status_filter === 'contacted') ? 'btn-primary-blue text-white' : 'btn-light'; ?> rounded-pill px-3">Contacted</a>
        <a href="inquiries.php?status=resolved" class="btn <?php echo ($status_filter === 'resolved') ? 'btn-success text-white' : 'btn-light'; ?> rounded-pill px-3">Resolved</a>
    </div>
</div>

<?php if ($success_msg): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 small mb-4 shadow-sm" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($success_msg, ENT_QUOTES, 'UTF-8'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error_msg): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-3 small mb-4 shadow-sm" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="admin-table-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4" style="width: 50px;">#</th>
                    <th>Client Details</th>
                    <th>Associated Piece</th>
                    <th>Message Content</th>
                    <th>Status</th>
                    <th>Received Date</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($inquiries)): ?>
                    <?php foreach ($inquiries as $inq): ?>
                        <tr>
                            <td class="ps-4 text-muted fw-bold"><?php echo (int)$inq['id']; ?></td>
                            <td>
                                <div class="fw-bold" style="color: var(--primary-navy);"><?php echo htmlspecialchars($inq['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="small">
                                    <a href="mailto:<?php echo htmlspecialchars($inq['email'], ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none fw-semibold" style="color: var(--primary-blue);"><?php echo htmlspecialchars($inq['email'], ENT_QUOTES, 'UTF-8'); ?></a>
                                </div>
                                <?php if (!empty($inq['phone'])): ?>
                                    <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($inq['phone'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($inq['artwork_title'])): ?>
                                    <a href="../../frontend/artwork-details.php?id=<?php echo (int)$inq['artwork_id']; ?>" target="_blank" class="fw-semibold text-decoration-none" style="color: var(--primary-navy);">
                                        <i class="bi bi-palette-fill me-1" style="color: var(--primary-blue);"></i><?php echo htmlspecialchars($inq['artwork_title'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                    <div class="small fw-bold" style="color: var(--primary-blue);">₹<?php echo number_format((float)$inq['artwork_price'], 2); ?></div>
                                <?php else: ?>
                                    <span class="badge rounded-pill bg-light text-muted border">General Inquiry</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-secondary" style="max-width: 280px;">
                                <?php echo nl2br(htmlspecialchars($inq['message'], ENT_QUOTES, 'UTF-8')); ?>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm dropdown-toggle rounded-pill py-1 px-3 <?php 
                                        echo ($inq['status'] === 'resolved') ? 'btn-success' : (($inq['status'] === 'contacted') ? 'btn-primary' : 'btn-warning text-dark'); 
                                    ?>" type="button" data-bs-toggle="dropdown" style="font-size: 0.75rem;">
                                        <?php echo ucfirst($inq['status']); ?>
                                    </button>
                                    <ul class="dropdown-menu rounded-3 shadow-sm border-0">
                                        <li><a class="dropdown-item small" href="inquiries.php?action=status&id=<?php echo (int)$inq['id']; ?>&status=pending">Pending</a></li>
                                        <li><a class="dropdown-item small" href="inquiries.php?action=status&id=<?php echo (int)$inq['id']; ?>&status=contacted">Contacted</a></li>
                                        <li><a class="dropdown-item small" href="inquiries.php?action=status&id=<?php echo (int)$inq['id']; ?>&status=resolved">Resolved</a></li>
                                    </ul>
                                </div>
                            </td>
                            <td class="small text-muted" style="white-space: nowrap;">
                                <?php echo date('M d, Y', strtotime($inq['created_at'])); ?>
                            </td>
                            <td class="text-end pe-4">
                                <a href="inquiries.php?action=delete&id=<?php echo (int)$inq['id']; ?>" 
                                   class="btn btn-sm btn-outline-danger rounded-3" 
                                   onclick="return confirm('Delete this inquiry record?');" 
                                   title="Delete Inquiry">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">No inquiries matching the selected status.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
