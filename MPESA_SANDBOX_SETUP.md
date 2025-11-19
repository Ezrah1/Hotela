# M-Pesa Sandbox Setup Guide

This guide will walk you through setting up M-Pesa sandbox for testing payments in your Hotela system.

## Step 1: Create Safaricom Developer Account

1. Visit [Safaricom Developer Portal](https://developer.safaricom.co.ke/)
2. Click **"Sign Up"** or **"Register"**
3. Fill in your details:
   - Email address
   - Password
   - Phone number
   - Company/Organization name
4. Verify your email address
5. Log in to your account

## Step 2: Create a Sandbox App

1. Once logged in, navigate to **"My Apps"** in the dashboard
2. Click **"Create New App"**
3. Select **"Lipa na M-Pesa Online"** or **"Lipa na M-Pesa Sandbox"**
4. Fill in the app details:
   - **App Name**: `Hotela Payment Gateway` (or any name you prefer)
   - **Description**: `Payment gateway for Hotela hospitality management system`
5. Click **"Create App"**

## Step 3: Get Your Sandbox Credentials

After creating your app, you'll need to collect the following credentials:

### A. Consumer Key & Consumer Secret

1. In your app dashboard, go to **"Keys"** or **"Credentials"** section
2. You'll see:
   - **Consumer Key**: A long string (e.g., `xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`)
   - **Consumer Secret**: Another long string
3. **Copy both** - you'll need them in Step 4

### B. Test Credentials

1. Navigate to **"APIs"** → Select your app → **"Test Credentials"** or **"Sandbox"**
2. You'll find:
   - **Shortcode (PayBill)**: `174379` (this is the default sandbox shortcode)
   - **Passkey**: A long string provided in the test credentials section
   - **Test Phone Numbers**: `254700000000` to `254700000009`
   - **Test M-Pesa PIN**: `1234`

**Important**: Copy the **Passkey** - it's different from Consumer Secret!

## Step 4: Configure M-Pesa in Hotela

1. **Log in to your Hotela admin panel**
2. Navigate to: **Settings** → **Payment Gateways** tab
3. Click on the **M-Pesa** tab (💰 icon)
4. Fill in the following fields:

   ```
   PayBill Number / Till Number: 174379
   Consumer Key: [Paste your Consumer Key from Step 3A]
   Consumer Secret: [Paste your Consumer Secret from Step 3A]
   Passkey: [Paste your Passkey from Step 3B]
   Environment: Sandbox (Testing) [Select from dropdown]
   Shortcode: 174379
   ```

5. **Enable Gateway**: Toggle the switch to **ON**
6. Click **"Save Configuration"**

## Step 5: Test the Integration

1. After saving, click the **"Test M-Pesa (Sandbox)"** button
   - Or navigate directly to: `/staff/dashboard/payment-gateway/mpesa-test`

2. On the test page, you'll see:
   - Your callback URL (copy this if needed)
   - Test information (phone numbers, PIN)
   - A test form

3. Fill in the test form:
   - **Phone Number**: `254700000000` (or any test number 254700000000-254700000009)
   - **Amount**: `100` (or any amount)
   - **Account Reference**: Auto-generated or custom
   - **Transaction Description**: `M-Pesa Sandbox Test`

4. Click **"Initiate STK Push"**

5. **On your test phone** (if you have the Safaricom test app):
   - You should receive an M-Pesa prompt
   - Enter PIN: `1234`
   - Confirm the payment

6. **Check Status**:
   - Use the **"Query Payment Status"** button to check transaction status
   - Or wait for the callback (automatically processed)

## Step 6: Understanding Test Results

### Success Indicators:
- ✅ **Response Code**: `0` = STK Push initiated successfully
- ✅ **Result Code**: `0` = Payment successful
- ✅ **Checkout Request ID**: Unique identifier for tracking

### Common Status Codes:
- **Result Code `0`**: Payment successful ✅
- **Result Code `1032`**: User cancelled the payment
- **Result Code `1037`**: Timeout waiting for user input
- **Result Code `1`**: Insufficient balance (simulated in sandbox)

## Callback URL Configuration

Your callback URL is automatically set to:
```
https://hotela.ezrahkiilu.com/api/mpesa/callback
```

**For Local Testing:**
If testing locally, you'll need to expose your local server using:
- **ngrok**: `ngrok http 80` (or your local port)
- **Cloudflare Tunnel**: Already configured for your production domain
- Update the callback URL in Safaricom Developer Portal if needed

## Troubleshooting

### Issue: "Failed to get M-Pesa access token"
**Solutions:**
- ✅ Verify Consumer Key and Consumer Secret are correct
- ✅ Check that your app is active in Developer Portal
- ✅ Ensure you're using sandbox credentials (not production)
- ✅ Check your internet connection

### Issue: "STK Push failed"
**Solutions:**
- ✅ Verify Shortcode is `174379` (sandbox default)
- ✅ Verify Passkey is correct (from test credentials, not Consumer Secret)
- ✅ Check phone number format: `254XXXXXXXXX` (no spaces, no +)
- ✅ Ensure amount is valid (minimum 1 KES)

### Issue: "No prompt received on phone"
**Solutions:**
- ✅ Verify you're using a test phone number (254700000000 - 254700000009)
- ✅ Check your internet connection
- ✅ Wait a few seconds and query the status
- ✅ Note: In sandbox, you may not receive actual prompts - use status query instead

### Issue: "Callback not received"
**Solutions:**
- ✅ Verify your callback URL is publicly accessible
- ✅ Check server logs for incoming requests
- ✅ For local testing, use ngrok or Cloudflare Tunnel
- ✅ Ensure your server can accept POST requests

## Testing Checklist

Before moving to production, ensure:

- [ ] Developer account created and verified
- [ ] Sandbox app created successfully
- [ ] All credentials obtained (Consumer Key, Secret, Passkey)
- [ ] M-Pesa settings configured in Hotela admin panel
- [ ] Gateway enabled in settings
- [ ] Test STK Push initiated successfully
- [ ] Payment status query returns success
- [ ] Callback received (check server logs)
- [ ] All test scenarios completed

## Next Steps: Moving to Production

Once sandbox testing is successful:

1. **Apply for Production Credentials**
   - Contact Safaricom through Developer Portal
   - Complete required documentation
   - Wait for approval (usually 1-2 business days)

2. **Update Settings**
   - Change Environment to **"Production"**
   - Enter production credentials (Consumer Key, Secret, Passkey, Shortcode)
   - Update callback URL to your production domain

3. **Test with Real Phone Numbers**
   - Use real M-Pesa registered numbers
   - Test with small amounts first
   - Monitor transactions in your M-Pesa business account

## Security Best Practices

1. **Never commit credentials to version control**
   - Credentials are stored securely in the database
   - Use environment variables for sensitive data

2. **Use HTTPS in production**
   - M-Pesa requires HTTPS for callbacks
   - Ensure SSL certificate is valid

3. **Monitor transactions**
   - Keep logs of all payment attempts
   - Set up alerts for failed transactions
   - Regularly reconcile with M-Pesa statements

## Support Resources

- **Safaricom Developer Portal**: https://developer.safaricom.co.ke/
- **Daraja API Documentation**: https://developer.safaricom.co.ke/docs
- **M-Pesa API Status**: https://developer.safaricom.co.ke/api-status
- **Hotela M-Pesa README**: See `README_MPESA.md` in project root

## Quick Reference

### Sandbox Default Values:
- **Shortcode**: `174379`
- **Test Phone Numbers**: `254700000000` to `254700000009`
- **Test PIN**: `1234`
- **Base URL**: `https://sandbox.safaricom.co.ke`

### Production Values:
- **Shortcode**: Your business shortcode (from Safaricom)
- **Phone Numbers**: Real M-Pesa registered numbers
- **PIN**: Customer's actual M-Pesa PIN
- **Base URL**: `https://api.safaricom.co.ke`

---

**Need Help?** Check the server logs for detailed error messages, or refer to the Safaricom Developer Portal documentation.

