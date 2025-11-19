# Cloudflare Tunnel Setup for Hotela

This guide will help you set up Cloudflare Tunnel to connect your local development environment to `https://hotela.ezrahkiilu.com/`.

## Prerequisites

1. **Cloudflare Account** with the domain `ezrahkiilu.com` added
2. **Cloudflared** installed on your machine
3. **Domain configured** in Cloudflare DNS

## Step 1: Install Cloudflared

### Windows (PowerShell)
```powershell
# Download cloudflared
Invoke-WebRequest -Uri "https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe" -OutFile "cloudflared.exe"

# Or use Chocolatey
choco install cloudflared
```

### Alternative: Download from
- Visit: https://github.com/cloudflare/cloudflared/releases
- Download the Windows executable
- Place it in your system PATH or in the Cloudflare folder

## Step 2: Login to Cloudflare

```powershell
cd Cloudflare
.\cloudflared.exe login
```

This will open your browser to authenticate with Cloudflare.

## Step 3: Create a Tunnel

```powershell
.\cloudflared.exe tunnel create hotela-tunnel
```

This will:
- Create a tunnel named "hotela-tunnel"
- Generate a tunnel ID
- Save credentials to `Cloudflare/credentials.json`

**Important:** Copy the Tunnel ID that's displayed. You'll need it for the next step.

## Step 4: Update Configuration

1. Open `Cloudflare/config.yml`
2. Replace `YOUR_TUNNEL_ID` with the actual tunnel ID from Step 3
3. Verify the hostname is correct: `hotela.ezrahkiilu.com`
4. Verify the service URL matches your XAMPP port (default is `http://localhost:80`)

## Step 5: Route DNS to Tunnel

```powershell
.\cloudflared.exe tunnel route dns hotela-tunnel hotela.ezrahkiilu.com
```

This creates a CNAME record in Cloudflare DNS pointing `hotela.ezrahkiilu.com` to your tunnel.

## Step 6: Run the Tunnel

### Option A: Run Once (for testing)
```powershell
.\cloudflared.exe tunnel --config Cloudflare/config.yml run
```

### Option B: Run as Windows Service (recommended for production)
```powershell
.\cloudflared.exe service install
.\cloudflared.exe tunnel --config Cloudflare/config.yml run
```

Or use the provided script:
```powershell
.\start-tunnel.ps1
```

## Step 7: Verify

1. Open your browser
2. Visit: `https://hotela.ezrahkiilu.com/`
3. You should see your local Hotela application

## Troubleshooting

### Tunnel won't start
- Check that XAMPP is running on port 80
- Verify the tunnel ID in `config.yml` is correct
- Check that `credentials.json` exists in the Cloudflare folder

### DNS not resolving
- Wait a few minutes for DNS propagation
- Check Cloudflare DNS dashboard for the CNAME record
- Verify the hostname matches exactly

### Connection refused
- Ensure XAMPP Apache is running
- Check firewall isn't blocking localhost:80
- Verify the service URL in `config.yml`

### SSL Certificate Issues
- Cloudflare Tunnel automatically provides SSL
- No additional certificate configuration needed
- If issues persist, check Cloudflare SSL/TLS settings

## Stopping the Tunnel

Press `Ctrl+C` in the terminal, or if running as a service:
```powershell
.\cloudflared.exe service uninstall
```

## Updating Configuration

After changing `config.yml`, restart the tunnel:
```powershell
# Stop the tunnel (Ctrl+C)
# Then start it again
.\cloudflared.exe tunnel --config Cloudflare/config.yml run
```

## Files in this folder

- `config.yml` - Tunnel configuration
- `credentials.json` - Tunnel authentication (auto-generated, DO NOT share)
- `start-tunnel.ps1` - PowerShell script to start the tunnel
- `README.md` - This file

## Security Notes

- **Never commit `credentials.json` to Git** (already in .gitignore)
- Keep your Cloudflare account secure
- The tunnel provides encrypted connection automatically
- All traffic goes through Cloudflare's network

## Next Steps

Once the tunnel is running:
1. Your M-Pesa callback URL will be: `https://hotela.ezrahkiilu.com/api/mpesa/callback`
2. Update M-Pesa settings if needed
3. Test the callback endpoint

