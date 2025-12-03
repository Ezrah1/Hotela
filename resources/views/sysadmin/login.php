<?php
$brandName = 'Hotela';
$logoPath = asset('assets/img/hotela-logo.svg');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Admin Login | <?= htmlspecialchars($brandName); ?></title>
    <link rel="stylesheet" href="<?= asset('css/main.css'); ?>">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .sysadmin-login-wrapper {
            width: 100%;
            max-width: 1200px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .sysadmin-login-intro {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .sysadmin-login-intro::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 20s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.3; }
        }
        
        .sysadmin-login-intro-content {
            position: relative;
            z-index: 1;
        }
        
        .sysadmin-logo {
            width: 80px;
            height: 80px;
            margin-bottom: 2rem;
            background: white;
            border-radius: 1rem;
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sysadmin-logo img {
            max-width: 100%;
            max-height: 100%;
        }
        
        .sysadmin-login-intro h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        
        .sysadmin-login-intro p {
            font-size: 1.125rem;
            opacity: 0.95;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        
        .sysadmin-features {
            list-style: none;
            margin-top: 2rem;
        }
        
        .sysadmin-features li {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }
        
        .sysadmin-features li svg {
            width: 20px;
            height: 20px;
            margin-right: 0.75rem;
            flex-shrink: 0;
        }
        
        .sysadmin-login-form {
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .sysadmin-login-header {
            margin-bottom: 2rem;
        }
        
        .sysadmin-login-header h2 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .sysadmin-login-header p {
            color: #64748b;
            font-size: 0.95rem;
        }
        
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }
        
        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #374151;
            font-weight: 500;
            font-size: 0.875rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.2s;
            background: white;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-primary {
            width: 100%;
            padding: 0.875rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 0.5rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            color: #64748b;
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.2s;
        }
        
        .btn-secondary:hover {
            color: #475569;
        }
        
        .form-group small {
            display: block;
            margin-top: 0.5rem;
            color: #64748b;
            font-size: 0.875rem;
        }
        
        .security-notice {
            margin-top: 2rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            color: #64748b;
            text-align: center;
            border: 1px solid #e2e8f0;
        }
        
        .security-notice strong {
            color: #475569;
            display: block;
            margin-bottom: 0.25rem;
        }
        
        @media (max-width: 768px) {
            .sysadmin-login-wrapper {
                grid-template-columns: 1fr;
            }
            
            .sysadmin-login-intro {
                padding: 2rem;
            }
            
            .sysadmin-login-intro h1 {
                font-size: 2rem;
            }
            
            .sysadmin-login-form {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="sysadmin-login-wrapper">
        <div class="sysadmin-login-intro">
            <div class="sysadmin-login-intro-content">
                <div class="sysadmin-logo">
                    <img src="<?= $logoPath; ?>" alt="<?= htmlspecialchars($brandName); ?>">
                </div>
                <h1>System Control Center</h1>
                <p>Software owner access to manage installations, licenses, and platform operations.</p>
                <ul class="sysadmin-features">
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                        Secure authentication with audit logging
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                        </svg>
                        License and tenant management
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        System health monitoring
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                        Analytics and reporting
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="sysadmin-login-form">
            <div class="sysadmin-login-header">
                <h2>System Admin Login</h2>
                <p>Enter your credentials to access the control center</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($_GET['logged_out'])): ?>
                <div class="alert alert-success">
                    You have been successfully logged out.
                </div>
            <?php endif; ?>

            <?php if (!empty($require2FA ?? false)): ?>
                <div class="alert alert-info" style="background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; margin-bottom: 1.5rem;">
                    <strong>Two-Factor Authentication Required</strong>
                    <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem;">Please enter the 6-digit code from your authenticator app.</p>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?= base_url('sysadmin/login'); ?>" id="loginForm">
                <?php if (empty($require2FA ?? false)): ?>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            required 
                            autofocus
                            autocomplete="username"
                            placeholder="Enter your username"
                            value="<?= htmlspecialchars($username ?? ($_POST['username'] ?? '')); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            autocomplete="current-password"
                            placeholder="Enter your password"
                        >
                    </div>
                <?php else: ?>
                    <div class="form-group">
                        <label for="two_factor_code">Two-Factor Authentication Code</label>
                        <input 
                            type="text" 
                            id="two_factor_code" 
                            name="two_factor_code" 
                            required 
                            autofocus
                            maxlength="10"
                            pattern="[0-9A-Za-z]{6,}"
                            placeholder="000000"
                            style="text-align: center; font-size: 1.5rem; letter-spacing: 0.5rem; font-family: monospace;"
                        >
                        <small style="display: block; margin-top: 0.5rem; color: #64748b; font-size: 0.875rem;">
                            Enter the 6-digit code from your authenticator app, or use a backup code (8 characters).
                        </small>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn-primary">
                    <?= !empty($require2FA ?? false) ? 'Verify Code' : 'Sign In to Control Center'; ?>
                </button>
                
                <?php if (!empty($require2FA ?? false)): ?>
                    <a href="<?= base_url('sysadmin/login'); ?>" class="btn-secondary" style="display: block; text-align: center; margin-top: 1rem; text-decoration: none;">
                        Cancel
                    </a>
                <?php endif; ?>
            </form>

            <div class="security-notice">
                <strong>⚠️ Restricted Access</strong>
                This panel is exclusively for software owners. All access attempts and actions are logged and monitored.
            </div>
        </div>
    </div>
</body>
</html>
