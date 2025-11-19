# Cloudflare Tunnel Setup Script
# This script helps you set up Cloudflare Tunnel for the first time

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Cloudflare Tunnel Setup for Hotela" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if cloudflared is installed
$cloudflaredPath = Get-Command cloudflared -ErrorAction SilentlyContinue
if (-not $cloudflaredPath) {
    Write-Host "cloudflared is not installed or not in PATH." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Please install cloudflared first:" -ForegroundColor Yellow
    Write-Host "  1. Download from: https://github.com/cloudflare/cloudflared/releases" -ForegroundColor White
    Write-Host "  2. Or use Chocolatey: choco install cloudflared" -ForegroundColor White
    Write-Host ""
    Write-Host "After installing, run this script again." -ForegroundColor Yellow
    exit 1
}

Write-Host "✓ cloudflared found: $($cloudflaredPath.Source)" -ForegroundColor Green
Write-Host ""

# Step 1: Login
Write-Host "Step 1: Login to Cloudflare" -ForegroundColor Cyan
Write-Host "This will open your browser to authenticate..." -ForegroundColor Yellow
$login = Read-Host "Continue with login? (y/n)"
if ($login -eq "y") {
    & cloudflared tunnel login
    Write-Host ""
}

# Step 2: Create Tunnel
Write-Host "Step 2: Create Tunnel" -ForegroundColor Cyan
Write-Host "Creating tunnel named 'hotela-tunnel'..." -ForegroundColor Yellow
$create = Read-Host "Create tunnel? (y/n)"
if ($create -eq "y") {
    $output = & cloudflared tunnel create hotela-tunnel 2>&1
    Write-Host $output
    
    # Try to extract tunnel ID from output
    if ($output -match 'Created tunnel\s+(\S+)') {
        $tunnelId = $matches[1]
        Write-Host ""
        Write-Host "✓ Tunnel created with ID: $tunnelId" -ForegroundColor Green
        Write-Host ""
        Write-Host "Updating config.yml with tunnel ID..." -ForegroundColor Yellow
        
        # Update config.yml with the tunnel ID
        $configPath = Join-Path $PSScriptRoot "config.yml"
        if (Test-Path $configPath) {
            $configContent = Get-Content $configPath -Raw
            $configContent = $configContent -replace 'YOUR_TUNNEL_ID', $tunnelId
            Set-Content -Path $configPath -Value $configContent
            Write-Host "✓ config.yml updated" -ForegroundColor Green
        }
    } else {
        Write-Host "Please manually update config.yml with your tunnel ID" -ForegroundColor Yellow
        Write-Host "The tunnel ID was shown above." -ForegroundColor Yellow
    }
    Write-Host ""
}

# Step 3: Route DNS
Write-Host "Step 3: Route DNS to Tunnel" -ForegroundColor Cyan
Write-Host "This will create a CNAME record in Cloudflare DNS..." -ForegroundColor Yellow
$route = Read-Host "Route DNS for hotela.ezrahkiilu.com? (y/n)"
if ($route -eq "y") {
    & cloudflared tunnel route dns hotela-tunnel hotela.ezrahkiilu.com
    Write-Host ""
    Write-Host "✓ DNS route created" -ForegroundColor Green
    Write-Host "Note: DNS changes may take a few minutes to propagate" -ForegroundColor Yellow
    Write-Host ""
}

# Step 4: Verify XAMPP
Write-Host "Step 4: Verify XAMPP is Running" -ForegroundColor Cyan
$port80 = Get-NetTCPConnection -LocalPort 80 -ErrorAction SilentlyContinue
if ($port80) {
    Write-Host "✓ Port 80 is in use (XAMPP appears to be running)" -ForegroundColor Green
} else {
    Write-Host "⚠ Port 80 is not in use" -ForegroundColor Yellow
    Write-Host "Please make sure XAMPP Apache is running before starting the tunnel" -ForegroundColor Yellow
}
Write-Host ""

# Summary
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Setup Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "  1. Make sure XAMPP Apache is running" -ForegroundColor White
Write-Host '  2. Start the tunnel: .\start-tunnel.ps1' -ForegroundColor White
Write-Host '  3. Visit: https://hotela.ezrahkiilu.com/' -ForegroundColor White
Write-Host ""
Write-Host 'Your M-Pesa callback URL will be:' -ForegroundColor Cyan
Write-Host '  https://hotela.ezrahkiilu.com/api/mpesa/callback' -ForegroundColor White
Write-Host ""

