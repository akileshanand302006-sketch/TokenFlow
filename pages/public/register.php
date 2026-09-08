<?php
/**
 * TokenFlow Pro — Register Page
 */
require_once __DIR__ . '/../../includes/helpers.php';
tfInit();

if (Auth::isLoggedIn()) {
    header('Location: ' . Auth::getDashboardUrl());
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Create your TokenFlow Pro account">
    <meta name="theme-color" content="#060a13">
    <?= CSRF::metaTag() ?>
    <title>Create Account — <?= APP_NAME ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>css/design-system.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>css/components.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>css/animations.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>css/layouts.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>css/pages.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>css/responsive.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>css/light-theme.css" rel="stylesheet">
    
    <script>
        const savedTheme = localStorage.getItem('tf_theme') || 'dark';
        if (savedTheme === 'light' || (savedTheme === 'system' && window.matchMedia('(prefers-color-scheme: light)').matches)) {
            document.documentElement.setAttribute('data-theme', 'light');
        }
        window.TF = { baseUrl: '<?= BASE_URL ?>', apiUrl: '<?= API_URL ?>', csrfToken: '<?= CSRF::getToken() ?>' };
    </script>
</head>
<body>
    <div class="page-background" aria-hidden="true">
        <img src="<?= ASSETS_URL ?>images/backgrounds/register.webp" alt="" onerror="this.style.display='none'">
    </div>
    <div class="page-background-overlay" aria-hidden="true"></div>
    <div class="atmosphere-glow" aria-hidden="true"></div>
    
    <div class="auth-layout">
        <div class="auth-branding animate-fade-in">
            <div style="max-width: 520px;">
                <div class="landing-hero-badge animate-fade-up delay-1">
                    <i class="bi bi-layers"></i>
                    <span>TOKENFLOW PRO</span>
                </div>
                <h1 class="text-hero animate-fade-up delay-2" style="margin-bottom: var(--space-6);">
                    Join the <span class="text-gradient">smart queue</span> revolution.
                </h1>
                <p class="text-body animate-fade-up delay-3" style="color: var(--text-secondary); font-size: var(--text-lg); line-height: var(--leading-relaxed);">
                    Create your account to generate tokens, track queues in real-time, book appointments, and never wait blindly again.
                </p>
            </div>
        </div>
        
        <div class="auth-form-wrapper">
            <div class="animate-fade-up" style="max-width: 400px; width: 100%;">
                <div class="auth-logo">
                    <div class="auth-logo-icon"><i class="bi bi-layers"></i></div>
                    <span class="auth-logo-text">TokenFlow</span>
                </div>
                
                <h2 class="auth-title">Create account</h2>
                <p class="auth-subtitle">Fill in your details to get started.</p>
                
                <div id="reg-alert" class="hidden" style="padding: var(--space-3) var(--space-4); border-radius: var(--radius-md); margin-bottom: var(--space-5); font-size: var(--text-sm);"></div>
                
                <form id="register-form" class="auth-form" novalidate>
                    <div class="form-group">
                        <div class="input-icon-wrapper">
                            <input type="text" class="form-input" id="reg-name" name="full_name" placeholder="Full name" required>
                            <i class="bi bi-person input-icon"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="input-icon-wrapper">
                            <input type="email" class="form-input" id="reg-email" name="email" placeholder="Email address" required autocomplete="email">
                            <i class="bi bi-envelope input-icon"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="input-icon-wrapper">
                            <input type="tel" class="form-input" id="reg-phone" name="phone" placeholder="Phone number (optional)">
                            <i class="bi bi-telephone input-icon"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="input-icon-wrapper">
                            <input type="password" class="form-input" id="reg-password" name="password" placeholder="Password (min 8 chars)" required minlength="8">
                            <i class="bi bi-lock input-icon"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="input-icon-wrapper">
                            <input type="password" class="form-input" id="reg-confirm" name="confirm_password" placeholder="Confirm password" required>
                            <i class="bi bi-lock-fill input-icon"></i>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-tf btn-primary btn-lg w-full" id="reg-btn">
                        <i class="bi bi-person-plus"></i>
                        Create Account
                    </button>
                </form>
                
                <div class="auth-footer">
                    Already have an account? <a href="<?= PAGES_URL ?>public/login.php">Sign in</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="toast-container" id="toast-container"></div>
    
    <script src="<?= ASSETS_URL ?>js/app.js"></script>
    <script src="<?= ASSETS_URL ?>js/api.js"></script>
    <script src="<?= ASSETS_URL ?>js/utils.js"></script>
    <script>
        document.getElementById('register-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const btn = document.getElementById('reg-btn');
            const data = {
                full_name: document.getElementById('reg-name').value.trim(),
                email: document.getElementById('reg-email').value.trim(),
                phone: document.getElementById('reg-phone').value.trim(),
                password: document.getElementById('reg-password').value,
                confirm_password: document.getElementById('reg-confirm').value
            };
            
            // Validate
            if (!data.full_name || !data.email || !data.password) {
                showRegAlert('Please fill in all required fields.', 'warning');
                return;
            }
            if (data.password.length < 8) {
                showRegAlert('Password must be at least 8 characters.', 'warning');
                return;
            }
            if (data.password !== data.confirm_password) {
                showRegAlert('Passwords do not match.', 'warning');
                return;
            }
            
            btn.classList.add('loading');
            btn.disabled = true;
            
            try {
                const result = await API.post('auth/register.php', data);
                
                if (result.success) {
                    showRegAlert('Account created! Redirecting to login...', 'success');
                    setTimeout(() => {
                        window.location.href = '<?= PAGES_URL ?>public/login.php';
                    }, 1500);
                } else {
                    showRegAlert(result.message || 'Registration failed.', 'danger');
                    btn.classList.remove('loading');
                    btn.disabled = false;
                }
            } catch (err) {
                showRegAlert('Connection error. Please try again.', 'danger');
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        });
        
        function showRegAlert(message, type) {
            const alert = document.getElementById('reg-alert');
            const colors = {
                success: { bg: 'rgba(52,211,153,0.1)', border: 'var(--accent-success)', color: 'var(--accent-success)' },
                danger: { bg: 'rgba(248,113,113,0.1)', border: 'var(--accent-danger)', color: 'var(--accent-danger)' },
                warning: { bg: 'rgba(251,191,36,0.1)', border: 'var(--accent-warning)', color: 'var(--accent-warning)' }
            };
            const c = colors[type] || colors.danger;
            alert.style.background = c.bg;
            alert.style.border = `1px solid ${c.border}`;
            alert.style.color = c.color;
            alert.textContent = message;
            alert.classList.remove('hidden');
        }
    </script>
</body>
</html>
