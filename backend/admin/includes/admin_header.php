<?php
if (!defined('ADMIN_PANEL')) {
    define('ADMIN_PANEL', true);
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/image_helper.php';

requireAdmin(__DIR__ . '/../login.php');

$current_admin_user = getCurrentUser();
$active_menu = $active_menu ?? '';
$admin_root = isset($admin_depth) && $admin_depth === 1 ? '../' : './';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') . ' - Admin' : 'Admin Panel'; ?> | Art Gallery</title>
    
    <!-- Google Fonts: Poppins (Headings) & Inter (Body) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <!-- Bootstrap 5 Bundle JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="<?php echo $admin_root; ?>../assets/css/style.css">
    <link rel="stylesheet" href="<?php echo $admin_root; ?>../assets/css/admin.css">
</head>
<body>

<div class="admin-wrapper">
    <!-- SIDEBAR NAVIGATION -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="brand-header">
            <a href="<?php echo $admin_root; ?>dashboard.php" class="text-decoration-none">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div style="width: 34px; height: 34px; border-radius: 10px; background: var(--accent-gradient); display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-palette-fill text-dark fs-5"></i>
                    </div>
                    <span class="brand-title">ART GALLERY</span>
                </div>
                <span class="brand-subtitle">Curator Control Panel</span>
            </a>
        </div>

        <div class="py-3 flex-grow-1">
            <div class="nav-section-title">
                Main Menu
            </div>
            <nav class="nav flex-column">
                <a class="nav-link <?php echo ($active_menu === 'dashboard') ? 'active' : ''; ?>" href="<?php echo $admin_root; ?>dashboard.php">
                    <i class="bi bi-grid-1x2-fill"></i>Dashboard
                </a>
                <a class="nav-link <?php echo ($active_menu === 'artworks') ? 'active' : ''; ?>" href="<?php echo $admin_root; ?>artworks.php">
                    <i class="bi bi-images"></i>Artworks
                </a>
                <a class="nav-link <?php echo ($active_menu === 'categories') ? 'active' : ''; ?>" href="<?php echo $admin_root; ?>categories.php">
                    <i class="bi bi-tags-fill"></i>Categories
                </a>
                <a class="nav-link <?php echo ($active_menu === 'users') ? 'active' : ''; ?>" href="<?php echo $admin_root; ?>users.php">
                    <i class="bi bi-people-fill"></i>Users
                </a>
                <a class="nav-link <?php echo ($active_menu === 'inquiries') ? 'active' : ''; ?>" href="<?php echo $admin_root; ?>inquiries.php">
                    <i class="bi bi-chat-heart-fill"></i>Inquiries
                </a>
                <a class="nav-link <?php echo ($active_menu === 'revenue') ? 'active' : ''; ?>" href="<?php echo $admin_root; ?>revenue.php">
                    <i class="bi bi-graph-up-arrow"></i>Revenue
                </a>
            </nav>
        </div>

        <!-- Sidebar Footer -->
        <div class="p-3 border-top border-white border-opacity-10">
            <a href="<?php echo $admin_root; ?>../../frontend/index.php" target="_blank" class="nav-link py-2 px-3 text-white-50 small mb-1">
                <i class="bi bi-box-arrow-up-right me-2" style="color: var(--accent-cyan);"></i>Live Gallery
            </a>
            <a href="<?php echo $admin_root; ?>logout.php" class="nav-link py-2 px-3 text-danger small">
                <i class="bi bi-box-arrow-right me-2"></i>Sign Out
            </a>
        </div>
    </aside>

    <!-- CONTENT WRAPPER -->
    <div class="admin-content-wrapper">
        <!-- TOPBAR -->
        <header class="admin-topbar">
            <div class="d-flex align-items-center">
                <button class="btn btn-light border d-lg-none me-3 p-1 px-2 rounded-3" onclick="document.getElementById('adminSidebar').classList.toggle('show')">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div class="small text-muted d-none d-sm-block fw-medium">
                    <i class="bi bi-calendar3 me-1 text-primary"></i><?php echo date('l, F j, Y'); ?>
                </div>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <div class="text-end d-none d-sm-block">
                    <div class="small fw-bold" style="color: var(--primary-navy);"><?php echo htmlspecialchars($current_admin_user['name'] ?? 'Administrator', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="text-muted" style="font-size: 0.75rem;"><?php echo htmlspecialchars($current_admin_user['email'] ?? 'admin@gallery.com', ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" 
                     style="width: 40px; height: 40px; background: var(--primary-blue); box-shadow: 0 4px 12px rgba(27,77,255,0.25);">
                    <?php echo strtoupper(substr($current_admin_user['name'] ?? 'A', 0, 1)); ?>
                </div>
            </div>
        </header>

        <!-- MAIN VIEW CONTAINER -->
        <main class="admin-main">
