# Fix: Email From Address Issue

## Problem
Emails are being sent from `ezrahkiilu@gmail.com` instead of `info@joyceresorts.com` or `noreply@joyceresorts.com`.

## Root Cause
When using Gmail SMTP, Gmail requires the "From" address to match the authenticated email address (`ezrahkiilu@gmail.com`). Gmail will override any custom "From" address you try to set.

## Solutions

### Option 1: Configure Gmail "Send mail as" (Recommended)
1. Go to Gmail Settings: https://mail.google.com/mail/u/0/#settings/accounts
2. Click "Add another email address" under "Send mail as"
3. Enter `info@joyceresorts.com` or `noreply@joyceresorts.com`
4. Verify the email address (Gmail will send a verification email)
5. Set it as the default "Send mail as" address
6. Update the SMTP settings in Hotela to use this verified address

### Option 2: Use a Different SMTP Service
If you have access to an SMTP server for `joyceresorts.com`:
1. Update SMTP settings in Hotela admin panel
2. Use the SMTP server for your domain (not Gmail)
3. Configure SMTP username as `info@joyceresorts.com` or `noreply@joyceresorts.com`

### Option 3: Use Desired Email as SMTP Username
If you have access to `info@joyceresorts.com` or `noreply@joyceresorts.com`:
1. Update SMTP username in Hotela settings to use the desired email
2. Update SMTP password to match that email account
3. This will make Gmail send from that address

## Current Implementation
The code has been updated to:
- Use `noreply@joyceresorts.com` as the default From email (from settings)
- Set Reply-To header to `info@joyceresorts.com` (from branding contact email)
- Use "Joyce Resorts" as the From name

However, Gmail will still show `ezrahkiilu@gmail.com` as the sender until one of the above solutions is implemented.

## Settings Location
Email settings are stored in:
- `storage/settings.json` (notifications section)
- Admin panel: Settings > Notifications

Current settings:
- `default_from_email`: `noreply@joyceresorts.com`
- `default_from_name`: `Joyce Resorts`
- `branding.contact_email`: `info@joyceresorts.com`

