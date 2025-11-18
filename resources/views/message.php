<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Message'); ?> | Hotela</title>
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
        .message-container {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 3rem;
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .message-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 1.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }
        .message-icon.error {
            background: #fee2e2;
            color: #dc2626;
        }
        .message-icon.success {
            background: #dcfce7;
            color: #16a34a;
        }
        .message-icon.info {
            background: #dbeafe;
            color: #2563eb;
        }
        .message-icon.warning {
            background: #fef3c7;
            color: #f59e0b;
        }
        h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
        }
        p {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .redirect-info {
            font-size: 0.875rem;
            color: #9ca3af;
            margin-top: 1.5rem;
        }
        .countdown {
            font-weight: 600;
            color: #3b82f6;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #3b82f6;
            color: #fff;
            text-decoration: none;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #2563eb;
        }
    </style>
</head>
<body>
    <div class="message-container">
        <div class="message-icon <?= htmlspecialchars($type ?? 'info'); ?>">
            <?php
            $icons = [
                'error' => '✕',
                'success' => '✓',
                'info' => 'ℹ',
                'warning' => '⚠',
            ];
            echo $icons[$type ?? 'info'] ?? 'ℹ';
            ?>
        </div>
        <h1><?= htmlspecialchars($title ?? 'Message'); ?></h1>
        <p><?= htmlspecialchars($message ?? ''); ?></p>
        <?php if (!empty($redirect)): ?>
            <div class="redirect-info">
                Redirecting in <span class="countdown" id="countdown"><?= (int)($delay ?? 5); ?></span> seconds...
            </div>
            <script>
                let countdown = <?= (int)($delay ?? 5); ?>;
                const countdownEl = document.getElementById('countdown');
                const redirectUrl = '<?= htmlspecialchars($redirect, ENT_QUOTES); ?>';
                
                const timer = setInterval(() => {
                    countdown--;
                    countdownEl.textContent = countdown;
                    
                    if (countdown <= 0) {
                        clearInterval(timer);
                        window.location.href = redirectUrl;
                    }
                }, 1000);
            </script>
        <?php endif; ?>
        <?php if (!empty($redirect)): ?>
            <a href="<?= htmlspecialchars($redirect); ?>" class="btn" style="margin-top: 1rem;">Continue Now</a>
        <?php endif; ?>
    </div>
</body>
</html>

