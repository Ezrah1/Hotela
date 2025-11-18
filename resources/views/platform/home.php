<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Hotela'); ?></title>
    <link rel="stylesheet" href="<?= asset('css/main.css'); ?>">
</head>
<body class="public-body">
    <header class="site-header public-header">
        <div class="container">
            <a class="brand" href="/" aria-label="Hotela OS">
                <img src="<?= asset('assets/img/hotela-logo.svg'); ?>" alt="Hotela logo">
            </a>
            <nav class="nav public-nav">
                <a href="/">Home</a>
                <a href="#features">Features</a>
                <a href="#pricing">Pricing</a>
                <a class="btn btn-primary btn-small" href="/login">Tenant Login</a>
            </nav>
        </div>
    </header>
    <main class="public-main">
        <section class="hero showcase-hero">
            <div class="container">
                <p class="eyebrow">Hospitality OS for modern hotels</p>
                <h1>Launch and operate your hotel on Hotela.</h1>
                <p>Guest website builder, PMS, POS, inventory, and staff workflows in one secure platform.</p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="/signup">Create Your Hotel</a>
                    <a class="btn btn-outline" href="/login">Tenant Login</a>
                </div>
            </div>
        </section>
        <section class="feature-section" id="features">
            <div class="container">
                <h2>Platform Highlights</h2>
                <div class="feature-grid">
                    <article class="feature-card">
                        <h4>Multi-tenant ready</h4>
                        <p>Separate guest domains and staff portals per hotel with centralized control.</p>
                    </article>
                    <article class="feature-card">
                        <h4>SysAdmin Console</h4>
                        <p>Developer dashboard to monitor tenants, run diagnostics, and manage global settings.</p>
                    </article>
                    <article class="feature-card">
                        <h4>Developer-friendly</h4>
                        <p>Modular PHP stack, simple deployment, and environment-based branding.</p>
                    </article>
                </div>
            </div>
        </section>
        <section class="feature-section" id="pricing">
            <div class="container">
                <h2>Pricing</h2>
                <p>Contact our team for tailored onboarding and tenant setup.</p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="mailto:info@hotela.test">Talk to Sales</a>
                </div>
            </div>
        </section>
    </main>
    <footer class="public-footer">
        <div class="container">
            <p>&copy; <?= date('Y'); ?> Hotela Platform. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>

