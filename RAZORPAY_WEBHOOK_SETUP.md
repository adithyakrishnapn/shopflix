# Razorpay Webhook Setup Guide

## Issue Fixed
**Before**: Payments go through Razorpay but database doesn't update when user navigates away  
**After**: Server-to-server webhooks handle DB updates regardless of user behavior

## Implementation Done ✅

### Files Created/Modified:
- `app/Http/Controllers/RazorpayController.php` - Added `webhook()`, `handlePaymentAuthorized()`, `handlePaymentFailed()` methods
- `routes/web.php` - Added webhook route without CSRF protection
- `database/migrations/2026_04_05_000001_create_razorpay_orders_table.php` - Temporary order storage
- `database/migrations/2026_04_05_000002_add_razorpay_payment_id_to_orders_table.php` - Track payments
- `app/Console/Commands/CleanupRazorpayOrders.php` - Clean up expired records
- `app/Console/Kernel.php` - Schedule cleanup to run daily at 4:00 AM
- `packages/Webkul/Admin/src/Config/system.php` - Added webhook_secret config field

## Setup Steps

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Get Webhook Secret from Razorpay
1. Go to [Razorpay Dashboard](https://dashboard.razorpay.com)
2. Navigate to **Settings** → **Webhooks**
3. Click **Add Webhook**
4. **Webhook URL**: `https://yourdomain.com/razorpay-webhook`
5. **Events to Subscribe**: Select these:
   - `payment.authorized`
   - `payment.failed`
6. **Active**: Toggle ON
7. **Copy the Webhook Secret** (shows only once!)

### 3. Add Webhook Secret to Admin Config
1. Go to Admin Panel
2. Navigate to **Configuration** → **Sales** → **Payment Methods** → **Razorpay**
3. Paste the webhook secret into **Razorpay Webhook Secret** field
4. Save

### 4. Test the Webhook
**Razorpay Dashboard → Settings → Webhooks → Your Webhook:**
1. Click **Redeliver** next to "Test Event"
2. Check `storage/logs` for webhook processing logs
3. Should see: `"Razorpay webhook: Order created via webhook"`

### 5. Clear Cache & Deploy
```bash
php artisan optimize:clear
```

## How It Works

**User Flow:**
```
1. User clicks "Pay Now" → Cart data stored in razorpay_orders table
2. Razorpay payment modal opens
3. User completes payment OR navigates away
   ↓
Server-to-server webhook (independent of user browser):
4. Razorpay calls `/razorpay-webhook`
5. Signature verified with webhook_secret
6. Order created with stored cart data
7. DB marked with razorpay_payment_id (prevents duplicates)
8. Invoice generated if needed
9. Temp record cleaned up
```

**Idempotency**: If webhook fires twice (rare), the `razorpay_payment_id` check prevents duplicate orders.

**Cleanup**: Old temp records auto-deleted after 24 hours (daily at 4:00 AM).

## Monitoring

### Check Webhook Logs:
```bash
tail -f storage/logs/laravel.log | grep -i razorpay
```

### Manual Cleanup (if needed):
```bash
php artisan razorpay:cleanup
```

### Database Check:
```sql
-- See pending orders awaiting webhook
SELECT * FROM razorpay_orders WHERE created_at > NOW() - INTERVAL 1 DAY;

-- See processed orders with payment tracking
SELECT id, razorpay_payment_id, status FROM orders WHERE razorpay_payment_id IS NOT NULL;
```

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Webhook not firing | Check Razorpay webhook URL is correct and publicly accessible |
| "Signature verification failed" | Ensure webhook_secret is correctly copied (exact copy, no spaces) |
| Duplicate orders | Check `orders.razorpay_payment_id` - if duplicate payment IDs exist, there's a webhook double-fire |
| Orders not created | Check logs: `tail storage/logs/laravel.log` for error messages |
| Temp table not cleaning up | Run `php artisan razorpay:cleanup` manually or check scheduler with `php artisan schedule:list` |

## API Payment Flow (for AJAX checkout)

The `verifyJson()` method also works with webhooks for immediate client feedback:
- User submits payment → `verifyJson()` responds immediately
- If verification passes, also creates order locally (redundant but safe)
- Webhook may fire later (provides backup)
- `razorpay_payment_id` idempotency prevents duplicates

## Notes

- Webhook secret is NOT the same as Key Secret
- Webhook calls are HTTPS POST from Razorpay IPs
- Razorpay retries failed webhooks (so multiple fire chances)
- Signature verification prevents spoofed webhooks
