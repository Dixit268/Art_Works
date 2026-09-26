<?php
$page_title = 'Manage Artworks';
$active_menu = 'artworks';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/includes/auth.php';
require_once __DIR__ . '/../../backend/includes/image_helper.php';

requireAdmin('login.php');

$errors = [];
$success_msg = $_SESSION['flash_success'] ?? null;
$error_msg = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$upload_dir = __DIR__ . '/../../backend/uploads/artworks/';
$root_upload_dir = __DIR__ . '/../../../uploads/artworks/';

if (!is_dir($upload_dir)) @mkdir($upload_dir, 0777, true);
if (!is_dir($root_upload_dir)) @mkdir($root_upload_dir, 0777, true);

// -------------------------------------------------------------
// 1. Process Actions (Create, Update, Delete)
// -------------------------------------------------------------

// DELETE ACTION
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];
    try {
        $stmt = $conn->prepare("SELECT image_type, image_path FROM artworks WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $del_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($art = $res->fetch_assoc()) {
            if ($art['image_type'] === 'upload' && !empty($art['image_path'])) {
                @unlink($upload_dir . $art['image_path']);
                @unlink($root_upload_dir . $art['image_path']);
            }
            $stmt->close();

            $del_stmt = $conn->prepare("DELETE FROM artworks WHERE id = ?");
            $del_stmt->bind_param("i", $del_id);
            $del_stmt->execute();
            $del_stmt->close();

            $_SESSION['flash_success'] = "Artwork removed successfully.";
        }
    } catch (Exception $e) {
        $_SESSION['flash_error'] = "Failed to delete artwork: " . $e->getMessage();
    }
    header("Location: artworks.php");
    exit;
}

// CREATE / UPDATE ACTION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_artwork'])) {
    if (!validateCsrfToken()) {
        $errors[] = "Security token mismatch. Please reload and try again.";
    }

    $id = isset($_POST['artwork_id']) ? (int)$_POST['artwork_id'] : 0;
    $title = trim($_POST['title'] ?? '');
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $medium = trim($_POST['medium'] ?? '');
    $dimensions = trim($_POST['dimensions'] ?? '');
    $year_created = !empty($_POST['year_created']) ? (int)$_POST['year_created'] : null;
    $availability_status = in_array($_POST['availability_status'] ?? '', ['available', 'sold']) ? $_POST['availability_status'] : 'available';
    $featured = isset($_POST['featured']) ? 1 : 0;
    $image_type = trim($_POST['image_type'] ?? 'upload');

    if (empty($title)) {
        $errors[] = "Artwork Title is required.";
    }
    if ($price < 0) {
        $errors[] = "Price must be a positive value.";
    }

    $existing = null;
    if ($id > 0) {
        $q = $conn->prepare("SELECT * FROM artworks WHERE id = ? LIMIT 1");
        $q->bind_param("i", $id);
        $q->execute();
        $existing = $q->get_result()->fetch_assoc();
        $q->close();
    }

    $image_path = $existing['image_path'] ?? null;
    $image_url = $existing['image_url'] ?? null;

    if ($image_type === 'upload') {
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image_file'];
            $file_size = $file['size'];
            $file_tmp = $file['tmp_name'];
            $file_name = $file['name'];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
            $allowed_mimes = ['image/jpeg', 'image/pjpeg', 'image/png', 'image/webp'];

            if ($file_size > 5 * 1024 * 1024) {
                $errors[] = "Image exceeds the maximum allowed size of 5MB.";
            }

            if (!in_array($ext, $allowed_exts)) {
                $errors[] = "Only JPG, JPEG, PNG, and WEBP formats are allowed.";
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file_tmp);
            finfo_close($finfo);

            if (!in_array($mime, $allowed_mimes)) {
                $errors[] = "Invalid image file type ($mime).";
            }

            // Verify that the file is indeed a valid image
            $image_info = @getimagesize($file_tmp);
            if ($image_info === false) {
                $errors[] = "The uploaded file is not a valid image.";
            }

            if (empty($errors)) {
                $new_filename = 'art_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest_backend = $upload_dir . $new_filename;
                $dest_root = $root_upload_dir . $new_filename;

                if (move_uploaded_file($file_tmp, $dest_backend)) {
                    @copy($dest_backend, $dest_root);
                    if ($existing && $existing['image_type'] === 'upload' && !empty($existing['image_path'])) {
                        @unlink($upload_dir . $existing['image_path']);
                        @unlink($root_upload_dir . $existing['image_path']);
                    }
                    $image_path = $new_filename;
                    $image_url = null;
                } else {
                    $errors[] = "Failed to upload image file to disk.";
                }
            }
        }
    } elseif ($image_type === 'link') {
        $raw_url = trim($_POST['image_url'] ?? '');
        if (empty($raw_url)) {
            $errors[] = "Please provide an image link URL.";
        } elseif (!filter_var($raw_url, FILTER_VALIDATE_URL)) {
            $errors[] = "Please provide a valid web URL (http:// or https://).";
        } else {
            $image_url = $raw_url;
            $image_path = null;
        }
    }

    if (empty($errors)) {
        try {
            if ($id > 0) {
                $stmt = $conn->prepare("
                    UPDATE artworks SET 
                        title = ?, category_id = ?, description = ?, image_type = ?, 
                        image_path = ?, image_url = ?, price = ?, medium = ?, 
                        dimensions = ?, year_created = ?, availability_status = ?, featured = ? 
                    WHERE id = ?
                ");
                $stmt->bind_param("sissssissisii", $title, $category_id, $description, $image_type, $image_path, $image_url, $price, $medium, $dimensions, $year_created, $availability_status, $featured, $id);
                $stmt->execute();
                $stmt->close();
                $_SESSION['flash_success'] = "Artwork \"$title\" updated successfully.";
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO artworks (title, category_id, description, image_type, image_path, image_url, price, medium, dimensions, year_created, availability_status, featured, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->bind_param("sissssissisi", $title, $category_id, $description, $image_type, $image_path, $image_url, $price, $medium, $dimensions, $year_created, $availability_status, $featured);
                $stmt->execute();
                $stmt->close();
                $_SESSION['flash_success'] = "Artwork \"$title\" added to catalogue.";
            }
            header("Location: artworks.php");
            exit;
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// -------------------------------------------------------------
// 2. Fetch Artworks with Search & Pagination
// -------------------------------------------------------------
$search = trim($_GET['search'] ?? '');
$category_filter = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : 0;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 8;
$offset = ($page - 1) * $per_page;

$where_clauses = ["1=1"];
$params = [];
$types = "";

if ($search !== '') {
    $where_clauses[] = "(a.title LIKE ? OR a.medium LIKE ?)";
    $sp = '%' . $search . '%';
    $params[] = $sp;
    $params[] = $sp;
    $types .= "ss";
}

if ($category_filter > 0) {
    $where_clauses[] = "a.category_id = ?";
    $params[] = $category_filter;
    $types .= "i";
}

$where_sql = implode(" AND ", $where_clauses);

// Count
$total_artworks = 0;
try {
    $c_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM artworks a WHERE $where_sql");
    if (!empty($params)) {
        $c_stmt->bind_param($types, ...$params);
    }
    $c_stmt->execute();
    $total_artworks = (int)$c_stmt->get_result()->fetch_assoc()['total'];
    $c_stmt->close();
} catch (Exception $e) {
    $total_artworks = 0;
}

$total_pages = ceil($total_artworks / $per_page);
if ($total_pages < 1) $total_pages = 1;

// Fetch Artworks
$artworks = [];
try {
    $q_sql = "
        SELECT a.*, c.name AS category_name 
        FROM artworks a 
        LEFT JOIN categories c ON a.category_id = c.id 
        WHERE $where_sql 
        ORDER BY a.id DESC 
        LIMIT ? OFFSET ?
    ";
    $q_stmt = $conn->prepare($q_sql);
    $q_params = $params;
    $q_types = $types . "ii";
    $q_params[] = $per_page;
    $q_params[] = $offset;
    $q_stmt->bind_param($q_types, ...$q_params);
    $q_stmt->execute();
    $res = $q_stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $artworks[] = $row;
    }
    $q_stmt->close();
} catch (Exception $e) {
    $artworks = [];
}

// Fetch Categories
$categories = [];
try {
    $c_res = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
    if ($c_res) {
        while ($c = $c_res->fetch_assoc()) {
            $categories[] = $c;
        }
    }
} catch (Exception $e) {
    $categories = [];
}

// Check edit trigger
$edit_artwork = null;
if (isset($_GET['edit_id'])) {
    $eq = $conn->prepare("SELECT * FROM artworks WHERE id = ? LIMIT 1");
    $edit_id = (int)$_GET['edit_id'];
    $eq->bind_param("i", $edit_id);
    $eq->execute();
    $edit_artwork = $eq->get_result()->fetch_assoc();
    $eq->close();
}

require_once __DIR__ . '/includes/admin_header.php';
?>

<!-- Title & Action Bar -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 font-heading fw-bold mb-1" style="color: var(--primary-navy);">Manage Artworks</h1>
        <p class="text-muted small mb-0">Catalogue curated art pieces, set pricing, upload files, and feature on the homepage.</p>
    </div>
    <div>
        <button class="btn btn-primary-blue btn-sm" data-bs-toggle="modal" data-bs-target="#artworkModal" onclick="resetArtworkForm()">
            <i class="bi bi-plus-circle-fill me-1"></i>Add New Artwork
        </button>
    </div>
</div>

<!-- Alerts -->
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

<!-- Filter & Search Toolbar -->
<div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
    <form action="artworks.php" method="GET" class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 rounded-start-pill ps-3"><i class="bi bi-search text-muted"></i></span>
                <input type="text" class="form-control bg-light border-start-0 rounded-end-pill ps-0" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search title or medium...">
            </div>
        </div>
        <div class="col-md-4">
            <select class="form-select rounded-pill bg-light" name="category_id">
                <option value="">All Categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?php echo (int)$c['id']; ?>" <?php echo ($category_filter === (int)$c['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary-blue rounded-pill flex-grow-1">Filter</button>
            <a href="artworks.php" class="btn btn-outline-secondary rounded-pill px-3" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</div>

<!-- Artworks Table -->
<div class="admin-table-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4" style="width: 70px;">Image</th>
                    <th>Title & Medium</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Dimensions</th>
                    <th>Status</th>
                    <th>Featured</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($artworks)): ?>
                    <?php foreach ($artworks as $art): ?>
                        <tr>
                            <td class="ps-4">
                                <img src="<?php echo getArtworkImageSrc($art, 'admin'); ?>" 
                                     alt="" 
                                     class="table-thumb">
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($art['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="small text-muted">
                                    <?php echo htmlspecialchars($art['medium'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if (!empty($art['year_created'])): ?>
                                        &bull; <?php echo (int)$art['year_created']; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary fw-semibold" style="padding: 0.4rem 0.75rem;">
                                    <?php echo htmlspecialchars($art['category_name'] ?? 'Unassigned', ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td class="fw-bold" style="color: var(--primary-blue);">
                                ₹<?php echo number_format((float)$art['price'], 2); ?>
                            </td>
                            <td class="small text-muted">
                                <?php echo htmlspecialchars($art['dimensions'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td>
                                <span class="badge rounded-pill <?php echo ($art['availability_status'] === 'available') ? 'bg-success' : 'bg-secondary'; ?>" style="font-size: 0.72rem; padding: 0.35rem 0.65rem;">
                                    <?php echo ucfirst($art['availability_status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($art['featured']): ?>
                                    <span class="badge rounded-pill bg-warning text-dark"><i class="bi bi-star-fill me-1"></i>Featured</span>
                                <?php else: ?>
                                    <span class="text-muted small">&mdash;</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <a href="../../frontend/artwork-details.php?id=<?php echo (int)$art['id']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary rounded-3 me-1" title="View Public Page">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-primary rounded-3 me-1" 
                                        onclick='editArtwork(<?php echo json_encode($art, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)' 
                                        title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger rounded-3" 
                                        onclick="confirmDelete(<?php echo (int)$art['id']; ?>, '<?php echo htmlspecialchars(addslashes($art['title']), ENT_QUOTES, 'UTF-8'); ?>')" 
                                        title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            No artworks found. Try changing your search query or add a new piece.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="p-3 border-top d-flex justify-content-center">
            <ul class="pagination pagination-sm mb-0 gap-1">
                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link rounded-pill" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category_id=<?php echo $category_filter; ?>">&laquo;</a>
                </li>
                <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                    <li class="page-item <?php echo ($page === $p) ? 'active' : ''; ?>">
                        <a class="page-link rounded-pill <?php echo ($page === $p) ? 'bg-primary border-primary text-white' : ''; ?>" href="?page=<?php echo $p; ?>&search=<?php echo urlencode($search); ?>&category_id=<?php echo $category_filter; ?>">
                            <?php echo $p; ?>
                        </a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link rounded-pill" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category_id=<?php echo $category_filter; ?>">&raquo;</a>
                </li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<!-- ADD / EDIT ARTWORK MODAL -->
<div class="modal fade" id="artworkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal-header-navy">
                <h5 class="modal-title" id="artworkModalLabel">Add Artwork</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="artworks.php" method="POST" enctype="multipart/form-data" id="artworkForm">
                <?php echo getCsrfField(); ?>
                <input type="hidden" name="save_artwork" value="1">
                <input type="hidden" name="artwork_id" id="form_artwork_id" value="0">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="form_title" class="form-label small fw-semibold text-secondary">Artwork Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" id="form_title" name="title" required placeholder="e.g. Whispers of Solitude">
                        </div>

                        <div class="col-md-4">
                            <label for="form_category_id" class="form-label small fw-semibold text-secondary">Category</label>
                            <select class="form-select rounded-3" id="form_category_id" name="category_id">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="form_price" class="form-label small fw-semibold text-secondary">Price ($ USD) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control rounded-3" id="form_price" name="price" required placeholder="0.00">
                        </div>

                        <div class="col-md-4">
                            <label for="form_medium" class="form-label small fw-semibold text-secondary">Medium</label>
                            <input type="text" class="form-control rounded-3" id="form_medium" name="medium" placeholder="e.g. Oil on Canvas">
                        </div>

                        <div class="col-md-4">
                            <label for="form_year_created" class="form-label small fw-semibold text-secondary">Year Created</label>
                            <input type="number" class="form-control rounded-3" id="form_year_created" name="year_created" placeholder="e.g. 2024">
                        </div>

                        <div class="col-md-6">
                            <label for="form_dimensions" class="form-label small fw-semibold text-secondary">Dimensions</label>
                            <input type="text" class="form-control rounded-3" id="form_dimensions" name="dimensions" placeholder="e.g. 36 x 48 in">
                        </div>

                        <div class="col-md-3">
                            <label for="form_availability_status" class="form-label small fw-semibold text-secondary">Status</label>
                            <select class="form-select rounded-3" id="form_availability_status" name="availability_status">
                                <option value="available">Available</option>
                                <option value="sold">Sold</option>
                            </select>
                        </div>

                        <div class="col-md-3 d-flex align-items-center pt-3">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="form_featured" name="featured" value="1">
                                <label class="form-check-label small fw-semibold text-secondary" for="form_featured">Featured on Home</label>
                            </div>
                        </div>

                        <!-- IMAGE TOGGLE SECTION -->
                        <div class="col-12 mt-3 pt-3 border-top">
                            <label class="form-label fw-bold text-dark d-block mb-2">Artwork Image Source</label>
                            
                            <div class="btn-group w-100 toggle-btn-group mb-3" role="group">
                                <input type="radio" class="btn-check" name="image_type" id="toggle_upload" value="upload" checked onchange="switchImageMode('upload')">
                                <label class="btn btn-outline-primary py-2 fw-semibold rounded-start-pill" for="toggle_upload">
                                    <i class="bi bi-upload me-2"></i>Upload Image
                                </label>

                                <input type="radio" class="btn-check" name="image_type" id="toggle_link" value="link" onchange="switchImageMode('link')">
                                <label class="btn btn-outline-primary py-2 fw-semibold rounded-end-pill" for="toggle_link">
                                    <i class="bi bi-link-45deg me-2"></i>Paste Image Link
                                </label>
                            </div>

                            <div id="section_upload" class="p-3 bg-light border rounded-3">
                                <label for="form_image_file" class="form-label small fw-semibold text-secondary">Choose File</label>
                                <input type="file" class="form-control rounded-3" id="form_image_file" name="image_file" accept=".jpg,.jpeg,.png,.webp" onchange="previewUploadImage(this)">
                                <div class="form-text small text-muted">
                                    Supported: <strong>JPG, JPEG, PNG, WEBP</strong> &bull; Max: <strong>5MB</strong>.
                                </div>
                            </div>

                            <div id="section_link" class="p-3 bg-light border rounded-3 d-none">
                                <label for="form_image_url" class="form-label small fw-semibold text-secondary">Direct Image URL</label>
                                <input type="url" class="form-control rounded-3" id="form_image_url" name="image_url" placeholder="https://images.unsplash.com/..." oninput="previewLinkImage(this.value)">
                            </div>

                            <div class="mt-3 text-center d-none" id="preview_box">
                                <span class="small text-muted d-block mb-1">Preview:</span>
                                <img src="" id="img_preview" alt="Preview" class="rounded-3 shadow-sm" style="max-height: 180px; object-fit: contain;">
                            </div>
                        </div>

                        <div class="col-12 mt-3">
                            <label for="form_description" class="form-label small fw-semibold text-secondary">Description / Curatorial Notes</label>
                            <textarea class="form-control rounded-3" id="form_description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-blue rounded-pill px-4" id="saveBtn">Save Artwork</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DELETE CONFIRMATION MODAL -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title font-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Artwork Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="mb-0">Are you sure you want to delete <strong id="deleteArtworkTitle"></strong>? This will permanently remove the record and any associated image files.</p>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="deleteConfirmBtn" class="btn btn-danger rounded-pill px-4">Delete Artwork</a>
            </div>
        </div>
    </div>
</div>

<script>
    let modalInstance = null;
    let deleteModal = null;

    function getArtworkModal() {
        if (!modalInstance) {
            const el = document.getElementById('artworkModal');
            if (el && typeof bootstrap !== 'undefined') {
                modalInstance = new bootstrap.Modal(el);
            }
        }
        return modalInstance;
    }

    function getDeleteModal() {
        if (!deleteModal) {
            const el = document.getElementById('deleteConfirmModal');
            if (el && typeof bootstrap !== 'undefined') {
                deleteModal = new bootstrap.Modal(el);
            }
        }
        return deleteModal;
    }

    function switchImageMode(mode) {
        const secUpload = document.getElementById('section_upload');
        const secLink = document.getElementById('section_link');
        const previewBox = document.getElementById('preview_box');
        const imgPreview = document.getElementById('img_preview');

        if (mode === 'upload') {
            secUpload.classList.remove('d-none');
            secLink.classList.add('d-none');
            const fileInput = document.getElementById('form_image_file');
            if (fileInput && fileInput.files && fileInput.files[0]) {
                previewUploadImage(fileInput);
            }
        } else {
            secUpload.classList.add('d-none');
            secLink.classList.remove('d-none');
            const linkInput = document.getElementById('form_image_url');
            if (linkInput && linkInput.value) {
                previewLinkImage(linkInput.value);
            }
        }
    }

    function previewUploadImage(input) {
        const previewBox = document.getElementById('preview_box');
        const imgPreview = document.getElementById('img_preview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imgPreview.src = e.target.result;
                previewBox.classList.remove('d-none');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function previewLinkImage(url) {
        const previewBox = document.getElementById('preview_box');
        const imgPreview = document.getElementById('img_preview');
        if (url && url.trim().length > 5) {
            imgPreview.src = url.trim();
            previewBox.classList.remove('d-none');
        } else {
            previewBox.classList.add('d-none');
        }
    }

    function resetArtworkForm() {
        document.getElementById('artworkModalLabel').textContent = 'Add New Artwork';
        document.getElementById('form_artwork_id').value = '0';
        document.getElementById('artworkForm').reset();
        document.getElementById('toggle_upload').checked = true;
        switchImageMode('upload');
        document.getElementById('preview_box').classList.add('d-none');
        document.getElementById('saveBtn').textContent = 'Add Artwork';
        const modal = getArtworkModal();
        if (modal) modal.show();
    }

    function editArtwork(art) {
        if (!art) return;
        document.getElementById('artworkModalLabel').textContent = 'Edit Artwork: ' + (art.title || '');
        document.getElementById('form_artwork_id').value = art.id || 0;
        document.getElementById('form_title').value = art.title || '';
        document.getElementById('form_category_id').value = art.category_id || '';
        document.getElementById('form_price').value = art.price || '';
        document.getElementById('form_medium').value = art.medium || '';
        document.getElementById('form_dimensions').value = art.dimensions || '';
        document.getElementById('form_year_created').value = art.year_created || '';
        document.getElementById('form_availability_status').value = art.availability_status || 'available';
        document.getElementById('form_featured').checked = (parseInt(art.featured) === 1);
        document.getElementById('form_description').value = art.description || '';
        document.getElementById('saveBtn').textContent = 'Update Artwork';

        const imageType = art.image_type || 'upload';
        const imgPreview = document.getElementById('img_preview');
        const previewBox = document.getElementById('preview_box');

        if (imageType === 'upload') {
            document.getElementById('toggle_upload').checked = true;
            switchImageMode('upload');
            if (art.image_path) {
                const imgPath = art.image_path.startsWith('uploads/') ? '../' + art.image_path : '../uploads/artworks/' + art.image_path;
                imgPreview.src = imgPath;
                previewBox.classList.remove('d-none');
            } else {
                previewBox.classList.add('d-none');
            }
        } else {
            document.getElementById('toggle_link').checked = true;
            document.getElementById('form_image_url').value = art.image_url || '';
            switchImageMode('link');
            if (art.image_url) {
                imgPreview.src = art.image_url;
                previewBox.classList.remove('d-none');
            } else {
                previewBox.classList.add('d-none');
            }
        }

        const modal = getArtworkModal();
        if (modal) modal.show();
    }

    function confirmDelete(id, title) {
        document.getElementById('deleteArtworkTitle').textContent = title;
        document.getElementById('deleteConfirmBtn').href = 'artworks.php?action=delete&id=' + id;
        const modal = getDeleteModal();
        if (modal) modal.show();
    }

    <?php if ($edit_artwork): ?>
        document.addEventListener('DOMContentLoaded', () => {
            editArtwork(<?php echo json_encode($edit_artwork, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>);
        });
    <?php endif; ?>
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
