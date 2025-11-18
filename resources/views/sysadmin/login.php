<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SysAdmin Login</title>
    <link rel="stylesheet" href="<?= asset('css/main.css'); ?>">
</head>
<body class="auth-wrapper">
    <div class="auth-card">
        <h1>System Operator Login</h1>
        <?php if (!empty($error)): ?>
            <div class="alert danger"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post" action="<?= base_url('sysadmin/login'); ?>">
            <label>
                <span>Access Key</span>
                <input type="password" name="password" required autofocus>
            </label>
            <button class="btn btn-primary" type="submit">Enter</button>
        </form>
    </div>
</body>
</html>

