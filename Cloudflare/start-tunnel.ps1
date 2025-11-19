# Cloudflare Tunnel Start Script
# This script starts the Cloudflare tunnel for Hotela

Write-Host "Starting Cloudflare Tunnel for Hotela..." -ForegroundColor Cyan

# Check if cloudflared exists
$cloudflaredPath = "cloudflared.exe"
if (-not (Test-Path $cloudflaredPath)) {
    # Try to find it in PATH
    $cloudflaredPath = Get-Command cloudflared -ErrorAction SilentlyContinue
    if (-not $cloudflaredPath) {
        Write-Host "Error: cloudflared.exe not found!" -ForegroundColor Red
        Write-Host "Please install cloudflared first:" -ForegroundColor Yellow
        Write-Host "  choco install cloudflared" -ForegroundColor White
        Write-Host "  OR download from: https://github.com/cloudflare/cloudflared/releases" -ForegroundColor White
        exit 1
    }
    $cloudflaredPath = $cloudflaredPath.Source
}

# Check if config file exists
$configPath = Join-Path $PSScriptRoot "config.yml"
if (-not (Test-Path $configPath)) {
    Write-Host "Error: config.yml not found in Cloudflare folder!" -ForegroundColor Red
    Write-Host "Please create the configuration file first." -ForegroundColor Yellow
    exit 1
}

# Check if credentials exist
$credentialsPath = Join-Path $PSScriptRoot "credentials.json"
if (-not (Test-Path $credentialsPath)) {
    Write-Host "Warning: credentials.json not found!" -ForegroundColor Yellow
    Write-Host "You may need to create a tunnel first:" -ForegroundColor Yellow
    Write-Host "  .\cloudflared.exe tunnel create hotela-tunnel" -ForegroundColor White
    Write-Host ""
    $continue = Read-Host "Continue anyway? (y/n)"
    if ($continue -ne "y") {
        exit 1
    }
}

# Check if XAMPP is running (check if port 80 is in use)
$port80 = Get-NetTCPConnection -LocalPort 80 -ErrorAction SilentlyContinue
if (-not $port80) {
    Write-Host "Warning: Port 80 doesn't appear to be in use!" -ForegroundColor Yellow
    Write-Host "Make sure XAMPP Apache is running." -ForegroundColor Yellow
    Write-Host ""
    $continue = Read-Host "Continue anyway? (y/n)"
    if ($continue -ne "y") {
        exit 1
    }
}

Write-Host ""
Write-Host "Configuration:" -ForegroundColor Green
Write-Host "  Config: $configPath" -ForegroundColor White
Write-Host "  Credentials: $credentialsPath" -ForegroundColor White
Write-Host "  Target: http://localhost:80" -ForegroundColor White
Write-Host "  Domain: https://hotela.ezrahkiilu.com/" -ForegroundColor White
Write-Host ""
Write-Host "Starting tunnel... (Press Ctrl+C to stop)" -ForegroundColor Cyan
Write-Host ""

# Start the tunnel
& $cloudflaredPath tunnel --config $configPath run

