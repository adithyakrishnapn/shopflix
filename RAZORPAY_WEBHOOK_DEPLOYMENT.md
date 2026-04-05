# Razorpay Webhook Quick Deployment Checklist

## Before Deploying

- [ ] Review changes in `app/Http/Controllers/RazorpayController.php` - webhook handler added
- [ ] Check `routes/web.php` - new webhook route (CSRF exempt)
- [ ] Verify migrations created: `2026_04_05_000001`, `2026_04_05_000002`
- [ ] Review config changes in `packages/Webkul/Admin/src/Config/system.php`

## Deployment Steps

1. **Backup Database**
   ```bash
   # Create backup before migration
   mysqldump -u root shopflix > backup_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Deploy Code**
   ```bash
   git pull origin main
   composer install
   ```

3. **Run Migrations**
   ```bash
   php artisan migrate
   ```

4. **Clear Cache**
   ```bash
   php artisan optimize:clear
   ```

5. **Setup Scheduler** (if not already running)
   ```bash
   # Add to crontab for Linux/Mac servers
   * * * * * cd /path/to/shopflix && php artisan schedule:run >> /dev/null 2>&1
   ```

6. **Configure Webhook in Razorpay Admin**
   - URL: `https://yourdomain.com/razorpay-webhook`
   - Events: `payment.authorized`, `payment.failed`
   - Copy webhook secret

7. **Add Webhook Secret to Laravel Admin**
   - Admin Panel → Configuration → Sales → Payment Methods → Razorpay
   - Paste webhook secret into "Razorpay Webhook Secret" field
   - Save

## Post-Deployment Testing

1. **Test Payment Flow (Non-Production)**
   ```bash
   # Use Razorpay test credentials and test card numbers
   # Card: 4111 1111 1111 1111, Any future date, Any CVV
   ```

2. **Monitor Logs**
   ```bash
   tail -f storage/logs/laravel.log | grep razorpay
   ```

3. **Check DB**
   ```sql
   -- Verify columns added
   DESCRIBE orders; -- Should have razorpay_payment_id column
   DESCRIBE razorpay_orders; -- Should exist with razorpay_order_id, cart_data
   ```

## Rollback (If Issues)

1. **Remove code changes**
   ```bash
   git revert <commit-hash>
   composer install
   ```

2. **Rollback migrations**
   ```bash
   php artisan migrate:rollback
   ```

3. **Clear cache**
   ```bash
   php artisan optimize:clear
   ```

## Success Indicators

✅ Test payment completes → webhook fires → order appears in admin  
✅ Navigate away during payment → webhook still creates order  
✅ No duplicate orders even if webhook fires twice  
✅ Logs show "Order created via webhook"  

## Support

- Check `RAZORPAY_WEBHOOK_SETUP.md` for detailed setup
- Review troubleshooting guide in that file
