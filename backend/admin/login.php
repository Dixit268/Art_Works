<?php
$page_title = 'Admin Sign In';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/includes/auth.php';

// If already logged in as admin, redirect to dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: dashboard.php");
        exit;
    } else {
        // Logged in as regular user, destroy or warn
        $_SESSION['flash_error'] = "Current account does not possess administrator privileges.";
    }
}

$errors = [];
$email = '';
$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $errors[] = "Security token validation failed. Please refresh.";
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email)) {
        $errors[] = "Email address is required.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($user = $result->fetch_assoc()) {
                if (password_verify($password, $user['password'])) {
                    // Check if role is admin
                    if ($user['role'] !== 'admin') {
                        $errors[] = "Access denied: This portal is restricted to gallery administrators only.";
                    } else {
                        // Valid Admin Login
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = (int)$user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role'] = 'admin';

                        $stmt->close();
                        header("Location: dashboard.php");
                        exit;
                    }
                } else {
                    $errors[] = "Invalid credentials provided.";
                }
            } else {
                $errors[] = "Invalid credentials provided.";
            }
            $stmt->close();
        } catch (Exception $e) {
            $errors[] = "A system error occurred during authentication.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | Art Gallery</title>
    <!-- Google Fonts: Poppins & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../frontend/assets/css/style.css">
    <link rel="stylesheet" href="../../frontend/assets/css/admin.css">
    <style>
        body {
            background-color: var(--primary-navy);
            background-image: radial-gradient(circle at 10% 20%, rgba(56, 189, 248, 0.15) 0%, transparent 40%),
                              radial-gradient(circle at 90% 80%, rgba(27, 77, 255, 0.25) 0%, transparent 40%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-body);
        }
        .admin-login-card {
            background: #FFFFFF;
            border-radius: var(--radius-xl);
            box-shadow: 0 24px 60px rgba(7, 27, 69, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5 col-xl-4">
            <div class="text-center mb-4">
                <a href="../../frontend/index.php" class="text-decoration-none">
                    <div class="d-inline-flex align-items-center justify-content-center p-3 rounded-4 mb-2 shadow" style="background: var(--accent-gradient);">
                        <i class="bi bi-palette-fill text-dark fs-2"></i>
                    </div>
                    <span class="font-heading fw-bold text-white fs-3 d-block" style="letter-spacing: -0.02em;">
                        ART GALLERY
                    </span>
                    <span class="text-uppercase small fw-bold" style="color: var(--accent-cyan); letter-spacing: 0.2em; font-size: 0.72rem;">
                        Curator Control Portal
                    </span>
                </a>
            </div>

            <div class="card admin-login-card p-4 p-sm-5">
                <div class="text-center mb-4">
                    <h1 class="h4 fw-bold font-heading mb-1" style="color: var(--primary-navy);">Admin Sign In</h1>
                    <p class="text-muted small mb-0">Authorized personnel & curators only</p>
                </div>

                <?php if ($flash_success): ?>
                    <div class="alert alert-success py-2 px-3 rounded-3 small mb-3">
                        <i class="bi bi-check-circle-fill me-1"></i><?php echo htmlspecialchars($flash_success, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <?php if ($flash_error): ?>
                    <div class="alert alert-warning py-2 px-3 rounded-3 small mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i><?php echo htmlspecialchars($flash_error, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger py-2 px-3 rounded-3 small mb-3">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" novalidate>
                    <?php echo getCsrfField(); ?>
                    <div class="mb-3">
                        <label for="admin_email" class="form-label small fw-semibold text-secondary">Admin Email</label>
                        <input type="email" class="form-control rounded-3 py-2" id="admin_email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" placeholder="admin@gallery.com" required autofocus>
                    </div>

                    <div class="mb-4">
                        <label for="admin_password" class="form-label small fw-semibold text-secondary">Password</label>
                        <input type="password" class="form-control rounded-3 py-2" id="admin_password" name="password" placeholder="••••••••" required>
                    </div>

                    <button type="submit" class="btn btn-primary-gradient w-100 py-2 fw-semibold">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Enter Control Panel
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top">
                    <a href="../../frontend/index.php" class="text-decoration-none small text-muted">
                        &larr; Return to Public Art Gallery
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
