# M-Pesa Sandbox Testing Guide

This guide will help you set up and test M-Pesa payment integration using the Safaricom Daraja API sandbox environment.

## Prerequisites

1. **Safaricom Developer Account**
   - Visit [Safaricom Developer Portal](https://developer.safaricom.co.ke/)
   - Register for a free developer account
   - Verify your email address

2. **Create a Sandbox App**
   - Log in to the Developer Portal
   - Navigate to "My Apps" → "Create New App"
   - Select "Lipa na M-Pesa Sandbox"
   - Name your application (e.g., "Hotela Payment Gateway")

## Getting Sandbox Credentials

After creating your sandbox app, you'll receive:

### 1. Consumer Key & Consumer Secret
- Found in your app dashboard under "Keys"
- These are used for OAuth authentication

### 2. Test Credentials
Navigate to "APIs" → Select your app → View test credentials:

- **Shortcode (PayBill)**: `174379` (sandbox default)
- **Passkey**: Provided in the test credentials section
- **Test Phone Numbers**: `254700000000` to `254700000009`
- **Test M-Pesa PIN**: `1234`

## Configuration Steps

### Step 1: Configure M-Pesa Settings

1. Go to **Settings** → **Payment Gateways** → **M-Pesa** tab
2. Enter your credentials:
   - **PayBill Number / Till Number**: `174379` (sandbox)
   - **Consumer Key**: Your app's consumer key
   - **Consumer Secret**: Your app's consumer secret
   - **Passkey**: Your app's passkey from test credentials
   - **Environment**: Select "Sandbox (Testing)"
   - **Shortcode**: `174379` (sandbox)
3. Click **Save Configuration**

### Step 2: Test the Integration

1. Navigate to **Payment Gateways** → **M-Pesa** → Click **Test M-Pesa (Sandbox)** button
   - Or go directly to: `/dashboard/payment-gateway/mpesa-test`

2. Fill in the test form:
   - **Phone Number**: Use a test number (e.g., `254700000000`)
   - **Amount**: Enter any amount (e.g., `100`)
   - **Account Reference**: Auto-generated or custom
   - **Transaction Description**: Description of the test

3. Click **Initiate STK Push**

4. **On the Test Phone**:
   - You should receive an M-Pesa prompt
   - Enter PIN: `1234`
   - Confirm the payment

5. **Check Status**:
   - Use the "Query Payment Status" button to check transaction status
   - Or wait for the callback (automatically processed)

## Understanding the Test Results

### Success Response
- **Response Code**: `0` means the STK Push was initiated successfully
- **Checkout Request ID**: Unique identifier for this transaction
- **Customer Message**: Message shown to the customer

### Payment Status Codes
- **Result Code `0`**: Payment successful
- **Result Code `1032`**: User cancelled the payment
- **Result Code `1037`**: Timeout waiting for user input
- **Result Code `1`**: Insufficient balance (in sandbox, this is simulated)

## Callback URL

The system automatically sets the callback URL to:
```
https://your-domain.com/api/mpesa/callback
```

This endpoint receives payment confirmations from Safaricom. Make sure:
- Your server is accessible from the internet (for production)
- For local testing, use a service like ngrok to expose your local server

### Using ngrok for Local Testing

```bash
# Install ngrok (if not installed)
# Download from https://ngrok.com/

# Expose your local server
ngrok http 80

# Use the provided HTTPS URL in your callback configuration
# Example: https://abc123.ngrok.io/api/mpesa/callback
```

## Common Issues & Solutions

### Issue: "Failed to get M-Pesa access token"
**Solution**: 
- Verify Consumer Key and Consumer Secret are correct
- Check that your app is active in the Developer Portal
- Ensure you're using sandbox credentials (not production)

### Issue: "STK Push failed"
**Solution**:
- Verify Shortcode and Passkey are correct
- Check that the phone number is in the correct format (254XXXXXXXXX)
- Ensure the amount is valid (minimum 1 KES)

### Issue: "No prompt received on phone"
**Solution**:
- Verify you're using a test phone number (254700000000 - 254700000009)
- Check your internet connection
- Wait a few seconds and try querying the status

### Issue: "Callback not received"
**Solution**:
- Verify your callback URL is publicly accessible
- Check server logs for incoming requests
- For local testing, use ngrok or similar service
- Ensure your server can accept POST requests

## Testing Checklist

- [ ] Developer account created
- [ ] Sandbox app created
- [ ] Credentials obtained (Consumer Key, Secret, Passkey)
- [ ] M-Pesa settings configured in admin panel
- [ ] Test STK Push initiated successfully
- [ ] Payment prompt received on test phone
- [ ] Payment completed with test PIN (1234)
- [ ] Payment status query returns success
- [ ] Callback received (check server logs)

## Moving to Production

Once sandbox testing is successful:

1. **Apply for Production Credentials**
   - Contact Safaricom through the Developer Portal
   - Complete the required documentation
   - Wait for approval (usually 1-2 business days)

2. **Update Settings**
   - Change Environment to "Production"
   - Enter production credentials (Consumer Key, Secret, Passkey, Shortcode)
   - Update callback URL to your production domain

3. **Test with Real Phone Numbers**
   - Use real M-Pesa registered numbers
   - Test with small amounts first
   - Monitor transactions in your M-Pesa business account

## Security Best Practices

1. **Never commit credentials to version control**
   - Use environment variables or secure settings storage
   - The system stores credentials securely in the database

2. **Use HTTPS in production**
   - M-Pesa requires HTTPS for callbacks
   - Ensure SSL certificate is valid

3. **Validate callbacks**
   - Verify callback signatures (if implemented)
   - Log all callbacks for audit purposes
   - Handle duplicate callbacks gracefully

4. **Monitor transactions**
   - Keep logs of all payment attempts
   - Set up alerts for failed transactions
   - Regularly reconcile with M-Pesa statements

## Support

For issues with:
- **M-Pesa API**: Contact Safaricom Developer Support
- **Integration**: Check server logs and error messages
- **Configuration**: Verify all settings match your Developer Portal

## Additional Resources

- [Safaricom Developer Portal](https://developer.safaricom.co.ke/)
- [Daraja API Documentation](https://developer.safaricom.co.ke/docs)
- [M-Pesa API Status](https://developer.safaricom.co.ke/api-status)

