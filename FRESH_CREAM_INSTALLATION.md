# Fresh Cream Installation Guide

A **Fresh Cream Installation** is a minimal but complete Bagisto setup designed to provide a clean slate with essential data for both frontend and backend functionality without the bloat of sample data.

## What's Included

### Database Setup
- ✅ Core configuration (locales, currencies, channels)
- ✅ Essential product attributes
- ✅ Product categories structure
- ✅ Inventory source
- ✅ Customer groups
- ✅ Admin user account (created during setup)

### Sample Data
- ✅ **4 Simple Products** - Various price points ($14.99 - $79.99)
  - Product 1: Basic product ($29.99)
  - Product 2: Premium product ($79.99)
  - Product 3: Budget product ($14.99)
  - Product 4: Sale item ($49.99 → $39.99)
  
- ✅ **1 Grouped Product** - Combines simple products
  
- ✅ **Frontend Pages** - Essential CMS pages:
  - About Us
  - Contact Us
  - Return Policy
  - Shipping Information
  - Privacy Policy
  - Terms of Service

## Installation Methods

### Method 1: Web Installer (Recommended) ⭐

This is the standard way to install Bagisto with cream mode.

**Setup:**

1. Set environment variable in `.env`:
   ```env
   BAGISTO_INSTALLATION_TYPE=cream
   ```

2. Start your application:
   ```bash
   php artisan serve
   ```

3. Visit the application in your browser:
   ```
   http://localhost:8000
   ```

4. The Bagisto installer wizard will automatically start:
   - Fill out the installer form (locales, currencies)
   - The cream data will be seeded automatically
   - Create your admin account
   - Complete the setup
   - The site is ready to use!

**Why this method?**
- ✅ Most user-friendly experience
- ✅ Works on any hosting (shared hosting, cloud, etc.)
- ✅ No command line knowledge required
- ✅ Perfect for client deployments
- ✅ Standard Bagisto installer flow

### Method 2: Command Line (For Development)

If you need to setup via command line:

```bash
# Full setup with migrations and seeding
php artisan migrate --force
BAGISTO_INSTALLATION_TYPE=cream php artisan db:seed --force
touch storage/installed
```

Or use the prepared commands:
```bash
# Interactive setup
php artisan setup:fresh-cream

# Non-interactive setup (CI/CD)
php artisan install:cream
```

### Method 3: Docker/Deployment Script

For automated deployments using environment variables:

```dockerfile
ENV BAGISTO_INSTALLATION_TYPE=cream

# In your entrypoint script:
php artisan migrate --force
php artisan db:seed --force
touch /app/storage/installed
```

## How It Works

### Web Installer Flow with Cream Mode

```
User visits: http://example.com
    ↓
Middleware checks: Is app installed? (checks storage/installed)
    ↓
No → Show installer wizard
    ↓
User fills form: Locales, currencies, admin account
    ↓
Run migrations (create tables)
    ↓
Run seeders with BAGISTO_INSTALLATION_TYPE=cream:
    ├─ Core seeder (locales, currencies, channels)
    ├─ Attribute seeder (product attributes)
    ├─ Category seeder (categories)
    ├─ Customer seeder (customer groups)
    ├─ Inventory seeder (inventory source)
    ├─ User seeder (admin role)
    ├─ CreamProductSeeder (4 sample products + 1 grouped product)
    └─ CreamFrontendPageSeeder (6 CMS pages)
    ↓
Admin account created (from installer form)
    ↓
SMTP/Email configuration (optional in installer)
    ↓
Mark as installed (create storage/installed)
    ↓
✓ Installation complete! Redirect to homepage
    ↓
Full e-commerce site ready to use
```

## Product Data Details

### Simple Products (4 examples)
Each product includes:
- Unique SKU (PRODUCT-001, PRODUCT-002, etc.)
- Product name and descriptions
- Base price and cost
- Weight specification
- 100 units in inventory
- Product categorization
- SEO metadata
- Assigned to default channel/locale

### Grouped Product
Combines the first 2 simple products in a grouped product bundle, useful for demonstrating grouped product functionality on the frontend.

## Frontend Capabilities

With fresh cream setup, your frontend can immediately:
- ✅ Display product catalog with 4 products
- ✅ Show product detail pages with prices
- ✅ Browse product categories
- ✅ Add products to cart and checkout
- ✅ Display CMS pages (About, Contact, Return Policy, etc.)
- ✅ Search and filter products
- ✅ View product grouped bundles
- ✅ Account creation and management
- ✅ Order history (after purchase)

## Backend Capabilities

Admin dashboard provides full access to:
- ✅ Products management (edit, add, delete)
- ✅ Categories management
- ✅ Orders management
- ✅ Customers management
- ✅ Configuration settings
- ✅ Inventory management
- ✅ Reports and analytics
- ✅ CMS pages
- ✅ User and role management

## After Installation Completes

### Next Steps

1. **Admin Dashboard Login**
   ```
   URL: http://your-site.com/admin
   Email: You created during installer
   Password: You created during installer
   ```

2. **Customize Your Store**
   - Update store name and information
   - Add product images
   - Customize categories
   - Update CMS pages
   - Configure payment methods
   - Set shipping options

3. **Add More Products**
   - Products → Products
   - Click "Create"
   - Fill in product details
   - Assign to categories

4. **Update Store Settings**
   - Configuration → General
   - Update store information
   - Set contact details
   - Configure email notifications

5. **Configure Payment & Shipping**
   - Configuration → Payment Methods
   - Configuration → Shipping Methods
   - Set up your specific requirements

## Environment Variables

### For Web Installer (Recommended)
```env
# Enable fresh cream mode
BAGISTO_INSTALLATION_TYPE=cream

# Standard Laravel/Bagisto config
APP_NAME=Bagisto
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=bagisto
DB_USERNAME=root
DB_PASSWORD=secret
```

### For Command Line Seeding
```env
# For full Bagisto with all sample data
BAGISTO_SEED_BASE_DATA=true

# For fresh cream (minimal data)
BAGISTO_INSTALLATION_TYPE=cream
```

## Troubleshooting

### Installer doesn't show
**Problem:** You visit the site but installer wizard doesn't appear.

**Solution:** 
1. Delete `storage/installed` file:
   ```bash
   rm storage/installed
   ```
2. Visit the application again
3. Installer should appear

### Database migration error
**Problem:** "No such table" errors during installer.

**Solution:**
- Ensure your database exists and is accessible
- Check DB credentials in `.env`
- Make sure the database user has CREATE permissions

### Products not showing
**Problem:** Sample products not created.

**Solution:**
- Check that CreamProductSeeder was called
- Refresh product flat:
  ```bash
  php artisan product:refresh-flat
  ```

### CMS pages missing
**Problem:** Frontend pages (About, Contact) not showing.

**Solution:**
- If CMS module not installed, pages won't be seeded
- Create them manually via Admin → CMS → Pages

## Customizing Cream Installation

### Add More Sample Products

Edit [database/seeders/CreamProductSeeder.php](database/seeders/CreamProductSeeder.php):

```php
$products = [
    [
        'sku'    => 'CUSTOM-001',
        'name'   => 'Your Product Name',
        'price'  => 49.99,
        // ... more fields
    ],
];
```

### Modify CMS Pages

Edit [database/seeders/CreamFrontendPageSeeder.php](database/seeders/CreamFrontendPageSeeder.php) to change page content.

### Extend Cream Installation

Create additional seeders and add calls to the Bagisto DatabaseSeeder's `runCreamInstallation()` method in `packages/Webkul/Installer/src/Database/Seeders/DatabaseSeeder.php`.

## Quick Reference

| Task | How to Do It |
|------|------------|
| Enable cream mode | Set `BAGISTO_INSTALLATION_TYPE=cream` in `.env` |
| Run installer | Visit `http://your-site.com` or `http://localhost:8000` |
| Reset and reinstall | `rm storage/installed && php artisan migrate:fresh --force` |
| Seed only | `BAGISTO_INSTALLATION_TYPE=cream php artisan db:seed --force` |
| View sample products | Visit homepage after installation |
| Access admin | Visit `/admin` and login with your created credentials |

---

**Ready to install?** Just set `BAGISTO_INSTALLATION_TYPE=cream` in your `.env` and visit your site!
