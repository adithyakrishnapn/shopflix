<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Category\Models\Category;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\Inventory\Models\InventorySource;

/**
 * Cream Product Seeder
 * Minimal product seed for fresh installation.
 */
class CreamProductSeeder extends Seeder
{
    public function run(): void
    {
        \DB::disableQueryLog();

        $defaultChannel = Channel::where('code', 'default')->first();
        $defaultLocale = Locale::where('code', config('app.locale', 'en'))->first();
        $inventorySource = InventorySource::first();
        $attributeFamilyId = $this->getAttributeFamilyId();

        if (!$defaultChannel || !$defaultLocale || !$inventorySource) {
            $this->command?->error('✗ Missing required core data.');
            return;
        }

            // Clear old data first
            \DB::statement('SET FOREIGN_KEY_CHECKS=0');
            \DB::table('product_price_indices')->truncate();
            \DB::table('product_inventory_indices')->truncate();
            \DB::table('product_grouped_products')->truncate();
            \DB::table('product_categories')->truncate();
            \DB::table('product_channels')->truncate();
            \DB::table('product_attribute_values')->truncate();
            \DB::table('product_inventories')->truncate();
            \DB::table('product_flat')->truncate();
            \DB::table('products')->truncate();
            \DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Ensure a displayable subcategory exists under root
        $category = $this->ensureProductCategory($defaultLocale);
        if (!$category) {
            $this->command?->warn('✗ No categories found.');
            return;
        }

        $products = [
            ['sku' => 'PRODUCT-001', 'name' => 'Sample Product - Basic',    'desc' => 'Basic sample product for your store.',         'short' => 'A basic sample product',          'price' => 29.99, 'new' => 1, 'featured' => 1],
            ['sku' => 'PRODUCT-002', 'name' => 'Sample Product - Premium',  'desc' => 'Premium product with advanced features.',       'short' => 'Premium quality sample product',   'price' => 79.99, 'new' => 1, 'featured' => 1],
            ['sku' => 'PRODUCT-003', 'name' => 'Sample Product - Budget',   'desc' => 'Affordable option that delivers quality.',      'short' => 'Budget-friendly sample product',   'price' => 14.99, 'new' => 1, 'featured' => 0],
            ['sku' => 'PRODUCT-004', 'name' => 'Sample Product - Sale Item','desc' => 'Featured product with special pricing.',        'short' => 'Sale product with discount',       'price' => 49.99, 'special' => 39.99, 'new' => 0, 'featured' => 1],
        ];

        $now = Carbon::now();
        $createdProductIds = [];

        // Get all customer groups for price index (NULL = all groups)
        $customerGroupIds = \DB::table('customer_groups')->pluck('id')->toArray();

        foreach ($products as $productData) {
            $productId = \DB::table('products')->insertGetId([
                'type'                => 'simple',
                'attribute_family_id' => $attributeFamilyId,
                'sku'                 => $productData['sku'],
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);

            $createdProductIds[] = $productId;

            // Add to category
            \DB::table('product_categories')->insert([
                'product_id'  => $productId,
                'category_id' => $category->id,
            ]);

            // Add channel assignment
            \DB::table('product_channels')->insert([
                'product_id' => $productId,
                'channel_id' => $defaultChannel->id,
            ]);

            $specialPrice = $productData['special'] ?? null;
            $effectivePrice = $specialPrice ?? $productData['price'];

            // product_flat — the denormalized display table the frontend reads
            \DB::table('product_flat')->insert([
                'product_id'            => $productId,
                'channel'               => $defaultChannel->code,
                'locale'                => $defaultLocale->code,
                'sku'                   => $productData['sku'],
                'name'                  => $productData['name'],
                'description'           => $productData['desc'],
                'short_description'     => $productData['short'],
                'url_key'               => strtolower(str_replace([' ', '_'], '-', preg_replace('/[^a-zA-Z0-9 _-]/', '', $productData['name']))),
                'meta_title'            => $productData['name'],
                'meta_keywords'         => strtolower(str_replace(' ', ', ', $productData['name'])),
                'meta_description'      => $productData['short'],
                'attribute_family_id'   => $attributeFamilyId,
                'type'                  => 'simple',
                'price'                 => $productData['price'],
                'special_price'         => $specialPrice,
                'weight'                => 1.00,
                'new'                   => $productData['new'] ?? 0,
                'featured'              => $productData['featured'] ?? 0,
                'status'                => 1,
                'visible_individually'  => 1,
                'created_at'            => $now,
                'updated_at'            => $now,
            ]);

            // Add attribute values
            $this->addProductAttributes($productId, $productData, $defaultChannel, $defaultLocale);

            // Add inventory
            \DB::table('product_inventories')->insert([
                'product_id'          => $productId,
                'inventory_source_id' => $inventorySource->id,
                'qty'                 => 100,
                'vendor_id'           => 0,
            ]);

            // Price indices — required for frontend price display and filtering
            foreach ($customerGroupIds as $groupId) {
                \DB::table('product_price_indices')->insert([
                    'product_id'        => $productId,
                    'customer_group_id' => $groupId,
                    'channel_id'        => $defaultChannel->id,
                    'min_price'         => $effectivePrice,
                    'regular_min_price' => $productData['price'],
                    'max_price'         => $effectivePrice,
                    'regular_max_price' => $productData['price'],
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]);
            }
            // Also insert a NULL group row (for guests)
            \DB::table('product_price_indices')->insert([
                'product_id'        => $productId,
                'customer_group_id' => null,
                'channel_id'        => $defaultChannel->id,
                'min_price'         => $effectivePrice,
                'regular_min_price' => $productData['price'],
                'max_price'         => $effectivePrice,
                'regular_max_price' => $productData['price'],
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);

            // Inventory index — required for Bagisto to show products on frontend
            \DB::table('product_inventory_indices')->insert([
                'product_id' => $productId,
                'channel_id' => $defaultChannel->id,
                'qty'        => 100,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->command?->info("  ✓ {$productData['sku']} — ₹{$productData['price']}");
        }

        // Create grouped product
        if (count($createdProductIds) >= 2) {
            $this->createGroupedProduct($createdProductIds, $category, $attributeFamilyId, $defaultChannel, $defaultLocale, $now);
        }

        $this->command?->info('✓ Fresh cream products seeded successfully.');
    }

    /**
     * Ensure a non-root product category exists for the storefront.
     */
    private function ensureProductCategory($defaultLocale): ?Category
    {
        // Use existing subcategory if one exists already
        $existing = Category::where('parent_id', '!=', null)->first();
        if ($existing) {
            return $existing;
        }

        // Get the root category
        $root = Category::whereNull('parent_id')->first() ?? Category::first();
        if (!$root) {
            return null;
        }

        // Create "Products" subcategory under root
        $now = Carbon::now();
        $categoryId = \DB::table('categories')->insertGetId([
            'position'     => 1,
            'status'       => 1,
            'display_mode' => 'products_and_description',
            '_lft'         => 0,
            '_rgt'         => 0,
            'parent_id'    => $root->id,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        \DB::table('category_translations')->insert([
            'category_id'      => $categoryId,
            'locale'           => $defaultLocale->code,
            'name'             => 'Products',
            'slug'             => 'products',
            'description'      => 'All products',
            'meta_title'       => '',
            'meta_description' => '',
            'meta_keywords'    => '',
        ]);

        return Category::find($categoryId);
    }

    private function addProductAttributes($productId, $productData, $channel, $locale)
    {
        $nameAttr = Attribute::where('code', 'name')->first();
        $descAttr = Attribute::where('code', 'description')->first();
        $shortDescAttr = Attribute::where('code', 'short_description')->first();
        $urlKeyAttr = Attribute::where('code', 'url_key')->first();
        $visibleAttr = Attribute::where('code', 'visible_individually')->first();
        $newAttr = Attribute::where('code', 'new')->first();
        $featuredAttr = Attribute::where('code', 'featured')->first();
        $priceAttr = Attribute::where('code', 'price')->first();
        $statusAttr = Attribute::where('code', 'status')->first();

        // Name - text, locale + channel specific
        if ($nameAttr) {
            \DB::table('product_attribute_values')->insert([
                'product_id'   => $productId,
                'attribute_id' => $nameAttr->id,
                'locale'       => $locale->code,
                'channel'      => $channel->code,
                'text_value'   => $productData['name'],
            ]);
        }

        // Description - text, locale + channel specific
        if ($descAttr) {
            \DB::table('product_attribute_values')->insert([
                'product_id'   => $productId,
                'attribute_id' => $descAttr->id,
                'locale'       => $locale->code,
                'channel'      => $channel->code,
                'text_value'   => $productData['desc'],
            ]);
        }

        // Short description - text, locale + channel specific
        if ($shortDescAttr) {
            \DB::table('product_attribute_values')->insert([
                'product_id'   => $productId,
                'attribute_id' => $shortDescAttr->id,
                'locale'       => $locale->code,
                'channel'      => $channel->code,
                'text_value'   => $productData['short'],
            ]);
        }

        // URL key - text, locale-specific
        if ($urlKeyAttr) {
            $urlKey = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $productData['name']));
            $urlKey = trim($urlKey, '-');

            \DB::table('product_attribute_values')->insert([
                'product_id'   => $productId,
                'attribute_id' => $urlKeyAttr->id,
                'locale'       => $locale->code,
                'text_value'   => $urlKey,
            ]);
        }

        // Visible individually - boolean
        if ($visibleAttr) {
            \DB::table('product_attribute_values')->insert([
                'product_id'    => $productId,
                'attribute_id'  => $visibleAttr->id,
                'boolean_value' => 1,
            ]);
        }

        // New - boolean
        if ($newAttr) {
            \DB::table('product_attribute_values')->insert([
                'product_id'    => $productId,
                'attribute_id'  => $newAttr->id,
                'boolean_value' => $productData['new'] ?? 0,
            ]);
        }

        // Featured - boolean
        if ($featuredAttr) {
            \DB::table('product_attribute_values')->insert([
                'product_id'    => $productId,
                'attribute_id'  => $featuredAttr->id,
                'boolean_value' => $productData['featured'] ?? 0,
            ]);
        }

        // Price - float, channel only (NO locale)
        if ($priceAttr) {
            \DB::table('product_attribute_values')->insert([
                'product_id'   => $productId,
                'attribute_id' => $priceAttr->id,
                'channel'      => $channel->code,
                'float_value'  => $productData['price'],
            ]);
        }

        // Status - boolean, channel only
        if ($statusAttr) {
            \DB::table('product_attribute_values')->insert([
                'product_id'    => $productId,
                'attribute_id'  => $statusAttr->id,
                'channel'       => $channel->code,
                'boolean_value' => 1,
            ]);
        }

        // Special price if provided
        if (isset($productData['special']) && $productData['special']) {
            $specialPriceAttr = Attribute::where('code', 'special_price')->first();
            if ($specialPriceAttr) {
                \DB::table('product_attribute_values')->insert([
                    'product_id'   => $productId,
                    'attribute_id' => $specialPriceAttr->id,
                    'channel'      => $channel->code,
                    'float_value'  => $productData['special'],
                ]);
            }
        }
    }

    private function createGroupedProduct($productIds, $category, $familyId, $channel, $locale, $now)
    {
        $groupedId = \DB::table('products')->insertGetId([
            'type'                => 'grouped',
            'attribute_family_id' => $familyId,
            'sku'                 => 'GROUP-CREAM-001',
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);

        \DB::table('product_categories')->insert([
            'product_id'  => $groupedId,
            'category_id' => $category->id,
        ]);

        \DB::table('product_channels')->insert([
            'product_id' => $groupedId,
            'channel_id' => $channel->id,
        ]);

        \DB::table('product_flat')->insert([
            'product_id'            => $groupedId,
            'channel'               => $channel->code,
            'locale'                => $locale->code,
            'sku'                   => 'GROUP-CREAM-001',
            'name'                  => 'Sample Grouped Products',
            'description'           => 'Grouped product combining multiple samples.',
            'short_description'     => 'Sample grouped product set',
            'url_key'               => 'grouped-sample-products',
            'meta_title'            => 'Grouped Sample Products',
            'meta_keywords'         => 'grouped, sample',
            'meta_description'      => 'Sample grouped product set',
            'attribute_family_id'   => $familyId,
            'type'                  => 'grouped',
            'price'                 => 0.00,
            'weight'                => 0.00,
            'new'                   => 1,
            'featured'              => 1,
            'status'                => 1,
            'visible_individually'  => 1,
            'created_at'            => $now,
            'updated_at'            => $now,
        ]);

        // Add attributes for grouped product
        $nameAttr = Attribute::where('code', 'name')->first();
        $statusAttr = Attribute::where('code', 'status')->first();

        if ($nameAttr) {
            \DB::table('product_attribute_values')->insert([
                'product_id'   => $groupedId,
                'attribute_id' => $nameAttr->id,
                'locale'       => $locale->code,
                'channel'      => $channel->code,
                'text_value'   => 'Sample Grouped Products',
            ]);
        }

        if ($statusAttr) {
            \DB::table('product_attribute_values')->insert([
                'product_id'    => $groupedId,
                'attribute_id'  => $statusAttr->id,
                'channel'       => $channel->code,
                'boolean_value' => 1,
            ]);
        }

        // Associate products (first 2)
        foreach (array_slice($productIds, 0, 2) as $index => $productId) {
            \DB::table('product_grouped_products')->insert([
                'product_id'  => $groupedId,
                'associated_product_id' => $productId,
                'qty'         => 1,
                'sort_order'  => $index,
            ]);
        }

        $this->command?->info("  ✓ GROUP-CREAM-001");
    }

    private function getAttributeFamilyId(): int
    {
        $family = AttributeFamily::first();
        return $family ? $family->id : AttributeFamily::create(['name' => 'Default', 'code' => 'default'])->id;
    }
}
