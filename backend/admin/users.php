<?php
$page_title = 'Registered Users & Collectors';
$active_menu = 'users';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/includes/auth.php';

requireAdmin('login.php');

$current_admin_id = (int)$_SESSION['user_id'];
$errors = [];
$success_msg = $_SESSION['flash_success'] ?? null;
$error_msg = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Handle Delete User
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];
    if ($del_id === $current_admin_id) {
        $_SESSION['flash_error'] = "You cannot delete your own active administrator account.";
    } else {
        try {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $del_id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['flash_success'] = "User account removed.";
        } catch (Exception $e) {
            $_SESSION['flash_error'] = "Failed to delete user: " . $e->getMessage();
        }
    }
    header("Location: users.php");
    exit;
}

// Handle Add / Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    if (!validateCsrfToken()) {
        $errors[] = "Security token mismatch. Please reload and try again.";
    }

    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = in_array($_POST['role'] ?? '', ['admin', 'user']) ? $_POST['role'] : 'user';
    $password = trim($_POST['password'] ?? '');

    if (empty($name)) {
        $errors[] = "User name is required.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    }

    // Check duplicate email
    if (empty($errors)) {
        if ($user_id > 0) {
            $chk = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
            $chk->bind_param("si", $email, $user_id);
        } else {
            $chk = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $chk->bind_param("s", $email);
        }
        $chk->execute();
        if ($chk->get_result()->fetch_assoc()) {
            $errors[] = "This email address is already in use by another account.";
        }
        $chk->close();
    }

    if ($user_id === 0 && (empty($password) || strlen($password) < 6)) {
        $errors[] = "Password must be at least 6 characters for new accounts.";
    } elseif (!empty($password) && strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if (empty($errors)) {
        try {
            if ($user_id > 0) {
                if (!empty($password)) {
                    $hashed = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role = ?, password = ? WHERE id = ?");
                    $stmt->bind_param("sssssi", $name, $email, $phone, $role, $hashed, $user_id);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role = ? WHERE id = ?");
                    $stmt->bind_param("ssssi", $name, $email, $phone, $role, $user_id);
                }
                $stmt->execute();
                $stmt->close();
                $_SESSION['flash_success'] = "User account updated successfully.";
            } else {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("INSERT INTO users (name, email, phone, role, password) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $name, $email, $phone, $role, $hashed);
                $stmt->execute();
                $stmt->close();
                $_SESSION['flash_success'] = "New user created successfully.";
            }
            header("Location: users.php");
            exit;
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch all users with wishlists and inquiries count
$users = [];
try {
    $q = $conn->query("
        SELECT u.*, 
            (SELECT COUNT(*) FROM wishlists w WHERE w.user_id = u.id) AS wishlist_count,
            (SELECT COUNT(*) FROM inquiries i WHERE i.email = u.email) AS inquiry_count
        FROM users u 
        ORDER BY u.id DESC
    ");
    if ($q) {
        while ($row = $q->fetch_assoc()) {
            $users[] = $row;
        }
    }
} catch (Exception $e) {
    $users = [];
}

require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 font-heading fw-bold mb-1" style="color: var(--primary-navy);">Registered Collectors & Users</h1>
        <p class="text-muted small mb-0">View registered user accounts, wishlist statistics, and gallery inquiries.</p>
    </div>
    <div>
        <button class="btn btn-primary-blue btn-sm" onclick="resetUserForm()">
            <i class="bi bi-person-plus-fill me-1"></i>Add New User
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
                    <th class="ps-4" style="width: 50px;">#</th>
                    <th>User Name</th>
                    <th>Email & Phone</th>
                    <th>Role</th>
                    <th>Wishlist Items</th>
                    <th>Inquiries</th>
                    <th>Joined Date</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td class="ps-4 text-muted fw-bold"><?php echo (int)$u['id']; ?></td>
                            <td>
                                <div class="fw-bold" style="color: var(--primary-navy);"><?php echo htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td class="small">
                                <div><a href="mailto:<?php echo htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none fw-semibold" style="color: var(--primary-blue);"><?php echo htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?></a></div>
                                <?php if (!empty($u['phone'])): ?>
                                    <div class="text-muted"><?php echo htmlspecialchars($u['phone'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge rounded-pill <?php echo ($u['role'] === 'admin') ? 'bg-primary' : 'bg-secondary'; ?>" style="font-size: 0.72rem; padding: 0.35rem 0.65rem;">
                                    <?php echo ucfirst($u['role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge rounded-pill bg-light text-danger border" style="padding: 0.35rem 0.65rem;">
                                    <i class="bi bi-heart-fill text-danger me-1"></i><?php echo (int)$u['wishlist_count']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge rounded-pill bg-light text-primary border" style="padding: 0.35rem 0.65rem;">
                                    <i class="bi bi-chat-heart-fill text-primary me-1"></i><?php echo (int)$u['inquiry_count']; ?>
                                </span>
                            </td>
                            <td class="small text-muted"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-secondary rounded-3 me-1" 
                                        onclick='viewUser(<?php echo json_encode($u, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)' 
                                        title="View Details">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-primary rounded-3 me-1" 
                                        onclick='editUser(<?php echo json_encode($u, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)' 
                                        title="Edit User">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php if ((int)$u['id'] !== $current_admin_id): ?>
                                    <a href="users.php?action=delete&id=<?php echo (int)$u['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger rounded-3" 
                                       onclick="return confirm('Delete this user account? All their saved wishlists will also be deleted.');" 
                                       title="Delete User">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No users found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add / Edit User Modal -->
<div class="modal fade" id="userFormModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal-header-navy">
                <h5 class="modal-title" id="userFormModalLabel">Add New User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="users.php" method="POST" id="userForm">
                <?php echo getCsrfField(); ?>
                <input type="hidden" name="save_user" value="1">
                <input type="hidden" name="user_id" id="form_user_id" value="0">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="form_user_name" class="form-label small fw-semibold text-secondary">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" id="form_user_name" name="name" required placeholder="e.g. Eleanor Vance">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="form_user_email" class="form-label small fw-semibold text-secondary">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control rounded-3" id="form_user_email" name="email" required placeholder="collector@domain.com">
                        </div>
                        <div class="col-md-6">
                            <label for="form_user_phone" class="form-label small fw-semibold text-secondary">Phone Number</label>
                            <input type="tel" class="form-control rounded-3" id="form_user_phone" name="phone" placeholder="+1 (555) 000-0000">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="form_user_role" class="form-label small fw-semibold text-secondary">Role</label>
                            <select class="form-select rounded-3" id="form_user_role" name="role">
                                <option value="user">User (Collector)</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="form_user_password" class="form-label small fw-semibold text-secondary" id="form_password_label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control rounded-3" id="form_user_password" name="password" placeholder="Min. 6 characters">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-blue rounded-pill px-4" id="userSaveBtn">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal-header-navy">
                <h5 class="modal-title" id="userModalLabel">Collector Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2 text-white fw-bold" 
                         id="u_avatar" style="width: 64px; height: 64px; background: var(--primary-blue); font-size: 1.5rem; box-shadow: 0 6px 16px rgba(27,77,255,0.25);">
                        U
                    </div>
                    <h5 class="h6 font-heading fw-bold mb-0" id="u_name" style="color: var(--primary-navy);"></h5>
                    <span class="badge rounded-pill bg-primary mt-1" id="u_role"></span>
                </div>
                <table class="table table-sm">
                    <tbody>
                        <tr>
                            <th class="text-muted small">User ID</th>
                            <td id="u_id" class="fw-semibold"></td>
                        </tr>
                        <tr>
                            <th class="text-muted small">Email Address</th>
                            <td id="u_email" class="fw-semibold"></td>
                        </tr>
                        <tr>
                            <th class="text-muted small">Phone</th>
                            <td id="u_phone"></td>
                        </tr>
                        <tr>
                            <th class="text-muted small">Saved Artworks</th>
                            <td id="u_wishlist"></td>
                        </tr>
                        <tr>
                            <th class="text-muted small">Total Inquiries</th>
                            <td id="u_inquiries"></td>
                        </tr>
                        <tr>
                            <th class="text-muted small">Registration Date</th>
                            <td id="u_joined"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    let userModalInstance = null;
    let userFormModalInstance = null;

    function getUserModal() {
        if (!userModalInstance) {
            const el = document.getElementById('userModal');
            if (el && typeof bootstrap !== 'undefined') {
                userModalInstance = new bootstrap.Modal(el);
            }
        }
        return userModalInstance;
    }

    function getUserFormModal() {
        if (!userFormModalInstance) {
            const el = document.getElementById('userFormModal');
            if (el && typeof bootstrap !== 'undefined') {
                userFormModalInstance = new bootstrap.Modal(el);
            }
        }
        return userFormModalInstance;
    }

    function resetUserForm() {
        document.getElementById('userFormModalLabel').textContent = 'Add New User';
        document.getElementById('form_user_id').value = '0';
        document.getElementById('form_user_name').value = '';
        document.getElementById('form_user_email').value = '';
        document.getElementById('form_user_phone').value = '';
        document.getElementById('form_user_role').value = 'user';
        document.getElementById('form_user_password').value = '';
        document.getElementById('form_user_password').required = true;
        document.getElementById('form_password_label').innerHTML = 'Password <span class="text-danger">*</span>';
        document.getElementById('userSaveBtn').textContent = 'Add User';
        const modal = getUserFormModal();
        if (modal) modal.show();
    }

    function editUser(u) {
        if (!u) return;
        document.getElementById('userFormModalLabel').textContent = 'Edit User: ' + (u.name || '');
        document.getElementById('form_user_id').value = u.id || 0;
        document.getElementById('form_user_name').value = u.name || '';
        document.getElementById('form_user_email').value = u.email || '';
        document.getElementById('form_user_phone').value = u.phone || '';
        document.getElementById('form_user_role').value = u.role || 'user';
        document.getElementById('form_user_password').value = '';
        document.getElementById('form_user_password').required = false;
        document.getElementById('form_password_label').innerHTML = 'Change Password <span class="text-muted small fw-normal">(Leave blank to keep current)</span>';
        document.getElementById('userSaveBtn').textContent = 'Update User';
        const modal = getUserFormModal();
        if (modal) modal.show();
    }

    function viewUser(u) {
        if (!u) return;
        document.getElementById('u_avatar').textContent = (u.name || 'U').charAt(0).toUpperCase();
        document.getElementById('u_name').textContent = u.name || '';
        document.getElementById('u_role').textContent = u.role ? u.role.toUpperCase() : 'USER';
        document.getElementById('u_id').textContent = '#' + (u.id || '');
        document.getElementById('u_email').textContent = u.email || '';
        document.getElementById('u_phone').textContent = u.phone || 'Not provided';
        document.getElementById('u_wishlist').textContent = (u.wishlist_count || 0) + ' artworks saved';
        document.getElementById('u_inquiries').textContent = (u.inquiry_count || 0) + ' messages sent';
        document.getElementById('u_joined').textContent = u.created_at || '';
        const modal = getUserModal();
        if (modal) modal.show();
    }
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
