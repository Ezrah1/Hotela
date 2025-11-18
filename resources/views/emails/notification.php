<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Notification'); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #1e293b;
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
        }
        .header {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 700;
        }
        .content {
            padding: 2rem;
            background: #ffffff;
        }
        .message {
            color: #475569;
            line-height: 1.7;
            margin: 1.5rem 0;
            white-space: pre-wrap;
        }
        .footer {
            background: #f8fafc;
            padding: 1.5rem 2rem;
            text-align: center;
            color: #64748b;
            font-size: 0.875rem;
            border-top: 1px solid #e2e8f0;
        }
        .footer p {
            margin: 0.5rem 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1><?= htmlspecialchars($title ?? 'Notification'); ?></h1>
        </div>
        <div class="content">
            <div class="message">
                <?= nl2br(htmlspecialchars($body ?? $message ?? '')); ?>
            </div>
        </div>
        <div class="footer">
            <p>This is an automated message from Hotela.</p>
            <p>Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>

