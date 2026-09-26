<?php
$page_title = 'Manage Categories';
$active_menu = 'categories';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/includes/auth.php';

requireAdmin('login.php');

$errors = [];
$success_msg = $_SESSION['flash_success'] ?? null;
$error_msg = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Toggle Status (active <-> inactive)
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $conn->prepare("UPDATE categories SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash_success'] = "Category status toggled successfully.";
    } catch (Exception $e) {
        $_SESSION['flash_error'] = "Error toggling status.";
    }
    header("Location: categories.php");
    exit;
}

// Delete Category
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];
    try {
        $del = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $del->bind_param("i", $del_id);
        $del->execute();
        $del->close();
        $_SESSION['flash_success'] = "Category deleted.";
    } catch (Exception $e) {
        $_SESSION['flash_error'] = "Cannot delete category: " . $e->getMessage();
    }
    header("Location: categories.php");
    exit;
}

// Add / Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    if (!validateCsrfToken()) {
        $errors[] = "Security token mismatch. Please reload and try again.";
    }

    $cat_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

    if (empty($name)) {
        $errors[] = "Category name is required.";
    }

    if (empty($errors)) {
        try {
            if ($cat_id > 0) {
                $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ?, status = ? WHERE id = ?");
                $stmt->bind_param("sssi", $name, $description, $status, $cat_id);
                $stmt->execute();
                $stmt->close();
                $_SESSION['flash_success'] = "Category updated successfully.";
            } else {
                $stmt = $conn->prepare("INSERT INTO categories (name, description, status) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $description, $status);
                $stmt->execute();
                $stmt->close();
                $_SESSION['flash_success'] = "New category created.";
            }
            header("Location: categories.php");
            exit;
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch all categories
$categories = [];
try {
    $q = $conn->query("
        SELECT c.*, COUNT(a.id) AS artwork_count 
        FROM categories c 
        LEFT JOIN artworks a ON c.id = a.category_id 
        GROUP BY c.id 
        ORDER BY c.id ASC
    ");
    if ($q) {
        while ($row = $q->fetch_assoc()) {
            $categories[] = $row;
        }
    }
} catch (Exception $e) {
    $categories = [];
}

require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 font-heading fw-bold mb-1" style="color: var(--primary-navy);">Artwork Categories</h1>
        <p class="text-muted small mb-0">Organize and toggle exhibition mediums, art genres, and classifications.</p>
    </div>
    <div>
        <button class="btn btn-primary-blue btn-sm" data-bs-toggle="modal" data-bs-target="#catModal" onclick="resetCatForm()">
            <i class="bi bi-plus-circle-fill me-1"></i>Add New Category
        </button>
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

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger rounded-3 small mb-4 shadow-sm">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="admin-table-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4" style="width: 60px;">#</th>
                    <th>Category Name</th>
                    <th>Description</th>
                    <th>Artworks Catalogued</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td class="ps-4 text-muted fw-bold"><?php echo (int)$cat['id']; ?></td>
                            <td class="fw-bold" style="color: var(--primary-navy);"><?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="small text-muted" style="max-width: 320px;"><?php echo htmlspecialchars($cat['description'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <span class="badge rounded-pill bg-light text-primary border" style="padding: 0.35rem 0.65rem;">
                                    <?php echo (int)$cat['artwork_count']; ?> pieces
                                </span>
                            </td>
                            <td>
                                <a href="categories.php?action=toggle&id=<?php echo (int)$cat['id']; ?>" 
                                   class="badge rounded-pill text-decoration-none <?php echo ($cat['status'] === 'active') ? 'bg-success' : 'bg-secondary'; ?>" 
                                   style="font-size: 0.72rem; padding: 0.35rem 0.65rem;"
                                   title="Click to toggle status">
                                    <?php echo ucfirst($cat['status']); ?> <i class="bi bi-arrow-repeat ms-1"></i>
                                </a>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-primary rounded-3 me-1" onclick='editCat(<?php echo json_encode($cat, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)' title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="categories.php?action=delete&id=<?php echo (int)$cat['id']; ?>" 
                                   class="btn btn-sm btn-outline-danger rounded-3" 
                                   onclick="return confirm('Delete this category? Any linked artworks will become unassigned.');" 
                                   title="Delete">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No categories created yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="catModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal-header-navy">
                <h5 class="modal-title" id="catModalLabel">Add Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="categories.php" method="POST">
                <?php echo getCsrfField(); ?>
                <input type="hidden" name="save_category" value="1">
                <input type="hidden" name="category_id" id="form_cat_id" value="0">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="form_cat_name" class="form-label small fw-semibold text-secondary">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" id="form_cat_name" name="name" required placeholder="e.g. Contemporary Oil">
                    </div>
                    <div class="mb-3">
                        <label for="form_cat_desc" class="form-label small fw-semibold text-secondary">Description</label>
                        <textarea class="form-control rounded-3" id="form_cat_desc" name="description" rows="3" placeholder="Brief summary of this classification..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="form_cat_status" class="form-label small fw-semibold text-secondary">Status</label>
                        <select class="form-select rounded-3" id="form_cat_status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-blue rounded-pill px-4" id="catSaveBtn">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let catModal = null;
    function getCatModal() {
        if (!catModal) {
            const el = document.getElementById('catModal');
            if (el && typeof bootstrap !== 'undefined') {
                catModal = new bootstrap.Modal(el);
            }
        }
        return catModal;
    }

    function resetCatForm() {
        document.getElementById('catModalLabel').textContent = 'Add Category';
        document.getElementById('form_cat_id').value = '0';
        document.getElementById('form_cat_name').value = '';
        document.getElementById('form_cat_desc').value = '';
        document.getElementById('form_cat_status').value = 'active';
        document.getElementById('catSaveBtn').textContent = 'Add Category';
        const modal = getCatModal();
        if (modal) modal.show();
    }

    function editCat(c) {
        if (!c) return;
        document.getElementById('catModalLabel').textContent = 'Edit Category: ' + (c.name || '');
        document.getElementById('form_cat_id').value = c.id || 0;
        document.getElementById('form_cat_name').value = c.name || '';
        document.getElementById('form_cat_desc').value = c.description || '';
        document.getElementById('form_cat_status').value = c.status || 'active';
        document.getElementById('catSaveBtn').textContent = 'Update Category';
        const modal = getCatModal();
        if (modal) modal.show();
    }
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
