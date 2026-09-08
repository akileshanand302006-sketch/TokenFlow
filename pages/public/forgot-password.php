<?php
/**
 * TokenFlow Pro — Forgot Password
 */
require_once __DIR__ . '/../../includes/helpers.php';
tfInit();

if (Auth::isLoggedIn()) {
    header('Location: ' . Auth::getDashboardUrl());
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::requireValid();
    $email = trim($_POST['email'] ?? '');
    if ($email) {
        $message = "If an account exists with this email, a password reset link has been sent.";
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — <?= APP_NAME ?></title>
    <?= CSRF::metaTag() ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>css/design-system.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>css/components.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>css/animations.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>css/layouts.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>css/pages.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>css/responsive.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>css/light-theme.css" rel="stylesheet">
</head>
<body>
    <div class="page-background" aria-hidden="true"><img src="<?= ASSETS_URL ?>images/backgrounds/login.webp" alt="" onerror="this.style.display='none'"></div>
    <div class="page-background-overlay" aria-hidden="true"></div>
    
    <div style="display:flex;align-items:center;justify-content:center;min-height:100vh;padding:var(--space-4);">
        <div class="glass-elevated animate-fade-up" style="max-width:420px;width:100%;padding:var(--space-10);border-radius:var(--radius-2xl);">
            <div class="auth-logo">
                <div class="auth-logo-icon"><i class="bi bi-layers"></i></div>
                <span class="auth-logo-text">TokenFlow</span>
            </div>
            
            <h2 class="auth-title">Reset Password</h2>
            <p class="auth-subtitle">Enter your email to receive a reset link.</p>
            
            <?php if ($message): ?>
            <div style="padding:var(--space-3) var(--space-4);border-radius:var(--radius-md);background:rgba(52,211,153,0.1);border:1px solid var(--accent-success);color:var(--accent-success);font-size:var(--text-sm);margin-bottom:var(--space-5);">
                <i class="bi bi-check-circle"></i> <?= e($message) ?>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">
                <div class="form-group">
                    <div class="input-icon-wrapper">
                        <input type="email" class="form-input" name="email" placeholder="Email address" required autofocus>
                        <i class="bi bi-envelope input-icon"></i>
                    </div>
                </div>
                <button type="submit" class="btn-tf btn-primary btn-lg w-full"><i class="bi bi-send"></i> Send Reset Link</button>
            </form>
            
            <div class="auth-footer"><a href="<?= PAGES_URL ?>public/login.php">Back to Sign In</a></div>
        </div>
    </div>
</body>
</html>
