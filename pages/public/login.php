<?php
/**
 * TokenFlow Pro — Login Page
 * Premium split-layout authentication with glass form
 */
require_once __DIR__ . '/../../includes/helpers.php';
tfInit();

// Redirect if already logged in
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
    <meta name="description" content="Sign in to TokenFlow Pro — Intelligent Digital Queue & Service Optimization Platform">
    <meta name="theme-color" content="#060a13">
    <?= CSRF::metaTag() ?>
    <title>Sign In — <?= APP_NAME ?></title>
    
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
    <!-- Background -->
    <div class="page-background" aria-hidden="true">
        <img src="<?= ASSETS_URL ?>images/backgrounds/login.webp" alt="" onerror="this.style.display='none'">
    </div>
    <div class="page-background-overlay" aria-hidden="true"></div>
    <div class="atmosphere-glow" aria-hidden="true"></div>
    
    <div class="auth-layout">
        <!-- Left — Branding -->
        <div class="auth-branding animate-fade-in">
            <div style="max-width: 520px;">
                <div class="landing-hero-badge animate-fade-up delay-1">
                    <i class="bi bi-layers"></i>
                    <span>TOKENFLOW PRO</span>
                </div>
                
                <h1 class="text-hero animate-fade-up delay-2" style="margin-bottom: var(--space-6); color: #ffffff;">
                    Intelligence for <span class="text-gradient">every queue.</span>
                </h1>
                
                <p class="text-body animate-fade-up delay-3" style="color: #ffffff !important; font-size: 1.2rem; line-height: 1.7; margin-bottom: var(--space-10); font-weight: 500; text-shadow: 0 2px 10px rgba(0,0,0,0.9); opacity: 1;">
                    Manage digital tokens, walk-in queues, appointments, and priority scheduling with real-time tracking and predictive analytics.
                </p>
                
                <!-- Animated stats -->
                <div class="flex gap-8 animate-fade-up delay-4">
                    <div>
                        <div class="metric-value text-gradient" data-count-to="12845" data-count-duration="2000">0</div>
                        <div class="metric-label" style="color: #f1f5f9 !important; font-weight: 600;">Tokens Managed</div>
                    </div>
                    <div>
                        <div class="metric-value text-gradient" data-count-to="98" data-count-suffix="%" data-count-duration="1500">0</div>
                        <div class="metric-label" style="color: #f1f5f9 !important; font-weight: 600;">SLA Compliance</div>
                    </div>
                    <div>
                        <div class="metric-value text-gradient" data-count-to="4.8" data-count-decimals="1" data-count-duration="1500">0</div>
                        <div class="metric-label" style="color: #f1f5f9 !important; font-weight: 600;">Satisfaction</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right — Login Form -->
        <div class="auth-form-wrapper">
            <div class="animate-fade-up" style="max-width: 400px; width: 100%;">
                <!-- Logo -->
                <div class="auth-logo">
                    <div class="auth-logo-icon">
                        <i class="bi bi-layers"></i>
                    </div>
                    <span class="auth-logo-text" style="color: #ffffff;">TokenFlow</span>
                </div>
                
                <h2 class="auth-title" style="color: #ffffff;">Welcome back</h2>
                <p class="auth-subtitle" style="color: #e2e8f0 !important; font-weight: 500;">Sign in to your account to continue.</p>
                
                <!-- Role Selector Tabs -->
                <div class="role-selector-tabs mb-4" style="display: flex; gap: var(--space-2); background: rgba(255,255,255,0.03); padding: 4px; border-radius: var(--radius-lg); border: 1px solid var(--border-glass);">
                    <button type="button" class="role-tab btn-tf btn-ghost btn-sm flex-1 active" id="tab-customer" onclick="selectRole('customer')">
                        <i class="bi bi-person" style="color: var(--accent-primary);"></i> Customer
                    </button>
                    <button type="button" class="role-tab btn-tf btn-ghost btn-sm flex-1" id="tab-staff" onclick="selectRole('staff')">
                        <i class="bi bi-person-badge" style="color: var(--accent-warning);"></i> Staff
                    </button>
                    <button type="button" class="role-tab btn-tf btn-ghost btn-sm flex-1" id="tab-admin" onclick="selectRole('admin')">
                        <i class="bi bi-shield-check" style="color: var(--accent-danger);"></i> Admin
                    </button>
                </div>

                <!-- Alert -->
                <div id="login-alert" class="hidden" style="padding: var(--space-3) var(--space-4); border-radius: var(--radius-md); margin-bottom: var(--space-5); font-size: var(--text-sm);"></div>
                
                <!-- Form -->
                <form id="login-form" class="auth-form" novalidate>
                    <input type="hidden" id="login-role" name="role" value="customer">

                    <div class="form-group">
                        <div class="input-icon-wrapper">
                            <input type="email" class="form-input" id="login-email" name="email" 
                                   placeholder="Email address" required autocomplete="email" autofocus>
                            <i class="bi bi-envelope input-icon"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="input-icon-wrapper" style="position: relative;">
                            <input type="password" class="form-input" id="login-password" name="password" 
                                   placeholder="Password" required autocomplete="current-password">
                            <i class="bi bi-lock input-icon"></i>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer" style="font-size: var(--text-sm); color: var(--text-secondary);">
                            <input type="checkbox" name="remember" style="accent-color: var(--accent-primary);">
                            Remember me
                        </label>
                        <a href="<?= PAGES_URL ?>public/forgot-password.php" style="font-size: var(--text-sm); font-weight: 500;">
                            Forgot password?
                        </a>
                    </div>
                    
                    <button type="submit" class="btn-tf btn-primary btn-lg w-full" id="login-btn">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Sign In as <span id="btn-role-label" style="text-transform: capitalize;">Customer</span>
                    </button>
                </form>
                
                <div class="auth-footer">
                    Don't have an account? <a href="<?= PAGES_URL ?>public/register.php">Create one</a>
                </div>
                
                <!-- Quick Demo Fill -->
                <div class="glass-surface" style="margin-top: var(--space-6); padding: var(--space-4) var(--space-5); border-radius: var(--radius-lg);">
                    <div class="text-overline" style="margin-bottom: var(--space-2);">Quick Autofill Credentials</div>
                    <div class="flex flex-col gap-2">
                        <button class="btn-tf btn-ghost btn-sm w-full" style="justify-content: flex-start;" 
                                onclick="selectRole('admin')">
                            <i class="bi bi-shield-check" style="color: var(--accent-danger);"></i>
                            Admin Credentials — admin@tokenflow.com
                        </button>
                        <button class="btn-tf btn-ghost btn-sm w-full" style="justify-content: flex-start;" 
                                onclick="selectRole('staff')">
                            <i class="bi bi-person-badge" style="color: var(--accent-warning);"></i>
                            Staff Credentials — staff@tokenflow.com
                        </button>
                        <button class="btn-tf btn-ghost btn-sm w-full" style="justify-content: flex-start;" 
                                onclick="selectRole('customer')">
                            <i class="bi bi-person" style="color: var(--accent-primary);"></i>
                            Customer Credentials — customer@tokenflow.com
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Toast Container -->
    <div class="toast-container" id="toast-container"></div>
    
    <script src="<?= ASSETS_URL ?>js/app.js"></script>
    <script src="<?= ASSETS_URL ?>js/api.js"></script>
    <script src="<?= ASSETS_URL ?>js/utils.js"></script>
    <script>
        const demoCredentials = {
            customer: { email: 'customer@tokenflow.com', pass: 'customer123' },
            staff: { email: 'staff@tokenflow.com', pass: 'staff123' },
            admin: { email: 'admin@tokenflow.com', pass: 'admin123' }
        };

        function selectRole(role) {
            document.querySelectorAll('.role-tab').forEach(tab => tab.classList.remove('active'));
            const activeTab = document.getElementById('tab-' + role);
            if (activeTab) activeTab.classList.add('active');
            
            const roleInput = document.getElementById('login-role');
            if (roleInput) roleInput.value = role;
            
            const roleLabel = document.getElementById('btn-role-label');
            if (roleLabel) roleLabel.textContent = role;
            
            if (demoCredentials[role]) {
                document.getElementById('login-email').value = demoCredentials[role].email;
                document.getElementById('login-password').value = demoCredentials[role].pass;
            }
        }
        
        // Initialize default role on load
        document.addEventListener('DOMContentLoaded', () => {
            selectRole('customer');
        });
        
        // Login form handler
        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const btn = document.getElementById('login-btn');
            const alert = document.getElementById('login-alert');
            const email = document.getElementById('login-email').value.trim();
            const password = document.getElementById('login-password').value;
            
            if (!email || !password) {
                showAlert('Please enter your email and password.', 'warning');
                return;
            }
            
            btn.classList.add('loading');
            btn.disabled = true;
            alert.classList.add('hidden');
            
            try {
                const result = await API.post('auth/login.php', { email, password });
                
                if (result.success) {
                    showAlert('Login successful! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = result.data?.redirect || '<?= BASE_URL ?>';
                    }, 500);
                } else {
                    showAlert(result.message || 'Login failed.', 'danger');
                    btn.classList.remove('loading');
                    btn.disabled = false;
                }
            } catch (err) {
                showAlert('Connection error. Please try again.', 'danger');
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        });
        
        function showAlert(message, type) {
            const alert = document.getElementById('login-alert');
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
