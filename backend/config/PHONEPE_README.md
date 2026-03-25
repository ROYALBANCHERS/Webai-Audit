# PhonePe Payment Gateway Integration

## Overview
This directory contains the PhonePe payment gateway configuration and integration files for WebAI Auditor's donation system.

## Files Structure

```
backend/
├── config/
│   ├── phonepe.xml          # Main configuration file
│   ├── phonepe-config.xsd   # XML schema validation
│   └── PHONEPE_README.md    # This file
└── api/
    ├── payment.php          # Payment API endpoints
    └── storage/
        └── transactions.json # Transaction records (auto-created)
```

## Setup Instructions

### 1. Get PhonePe Merchant Credentials

1. Register at [PhonePe Merchant Dashboard](https://dashboard.phonepe.com/)
2. Complete KYC verification
3. Get your:
   - Merchant ID
   - Salt Key
   - Salt Index (usually 1)

### 2. Update Configuration

Edit `config/phonepe.xml` and replace placeholder values:

```xml
<merchantId>YOUR_MERCHANT_ID</merchantId>
<saltKey>YOUR_SALT_KEY</saltKey>
```

### 3. Configure Redirect URLs

In PhonePe dashboard, add these redirect URLs:

**Production:**
```
Success: https://yourdomain.com/payment-success.html
Failure: https://yourdomain.com/payment-failure.html
Callback: https://yourdomain.com/api/payment/callback
```

**Test:**
```
Success: http://localhost:8080/payment-success.html
Failure: http://localhost:8080/payment-failure.html
Callback: http://localhost:8080/api/payment/callback
```

### 4. Server Setup

#### Option A: PHP Built-in Server (Development)
```bash
cd backend/api
php -S localhost:8000
```

#### Option B: Apache/Nginx (Production)
Configure your web server to point to the `backend/api` directory.

### 5. Frontend Configuration

Update `frontend/pricing.html` with your server URL:

```javascript
const PHONEPE_CONFIG = {
    apiBaseUrl: 'https://yourdomain.com/api/payment'
};
```

## API Endpoints

### Create Payment Transaction
```
POST /api/payment/create
Content-Type: application/json

{
    "amount": 100,
    "transaction_id": "TXN1234567890",
    "redirect_url": "https://yourdomain.com/payment-success.html",
    "callback_url": "https://yourdomain.com/api/payment/callback"
}

Response:
{
    "success": true,
    "encoded_payload": "base64_encoded_payload",
    "checksum": "sha256_checksum###1",
    "payment_url": "https://phonepe-rtu-webPg.../pay?payload=...",
    "transaction_id": "TXN1234567890"
}
```

### Check Transaction Status
```
GET /api/payment/status?transaction_id=TXN1234567890

Response:
{
    "success": true,
    "transaction_id": "TXN1234567890",
    "status": "COMPLETED",
    "amount": 10000,
    "payment_mode": "UPI"
}
```

### Callback Handler (PhonePe Webhook)
```
POST /api/payment/callback
Content-Type: application/json

{
    "response": "base64_encoded_response",
    "checksum": "sha256_checksum###1"
}
```

## Payment Flow

```
User clicks amount
    ↓
Frontend generates transaction ID
    ↓
Frontend calls /api/payment/create
    ↓
Backend saves transaction & generates payload
    ↓
Frontend redirects to PhonePe
    ↓
User completes payment on PhonePe
    ↓
PhonePe redirects to success/failure URL
    ↓
PhonePe sends callback to backend
    ↓
Backend updates transaction status
```

## Donation Tiers

Configured in `phonepe.xml`:

| Tier | Amount | Description |
|------|--------|-------------|
| Small Support | ₹50 | One-time contribution |
| Popular Choice | ₹100 | Help us grow |
| Major Support | ₹500 | Power our development |

## Security Notes

1. **Never commit** `phonepe.xml` with real credentials to version control
2. Use environment variables for production
3. Validate all callbacks using checksum verification
4. Implement rate limiting on API endpoints
5. Log all transactions for audit purposes

## Testing

### Test Mode
PhonePe provides test environment for development:
```
https://api-preprod.phonepe.com/apis/hermes
```

### Test Credentials
Use PhonePe's test merchant credentials for UAT.

### Test Cards/UPI
PhonePe provides test payment methods in their dashboard.

## Troubleshooting

### Payment Initialization Fails
- Check API base URL is correct
- Verify merchant credentials
- Ensure server is running
- Check browser console for errors

### Callback Not Received
- Verify callback URL in PhonePe dashboard
- Check server firewall rules
- Ensure HTTPS is enabled (production)

### Checksum Verification Failed
- Verify salt key and index
- Check payload encoding order
- Ensure UTF-8 encoding

## Support

For issues related to:
- **PhonePe API**: Contact PhonePe Merchant Support
- **Integration**: Check [PhonePe Developer Docs](https://developer.phonepe.com/)
- **This Implementation**: Open a GitHub issue

## License

This integration is part of WebAI Auditor project.
