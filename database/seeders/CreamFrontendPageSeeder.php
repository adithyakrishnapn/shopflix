<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Webkul\Core\Models\Locale;

/**
 * Cream Frontend Page Seeder
 * 
 * Creates essential CMS pages and frontend content for a fresh installation.
 * These pages help both frontend and backend demonstrate full functionality.
 */
class CreamFrontendPageSeeder extends Seeder
{
    /**
     * Seed the CMS pages table with minimal frontend content.
     */
    public function run(): void
    {
        $defaultLocale = Locale::where('code', config('app.locale', 'en'))->first();

        if (!$defaultLocale) {
            $this->command?->warn('Default locale not found. Skipping frontend page seeding.');
            return;
        }

        $now = Carbon::now();
        $cmsPageModel = 'Webkul\CMS\Models\Page';

        // Check if CMS module exists
        if (!class_exists($cmsPageModel)) {
            $this->command?->info('CMS module not installed. Skipping frontend page seeding.');
            return;
        }

        // About Us Page
        $this->createCMSPage(
            'About Us',
            'about-us',
            '<h1>About Our Store</h1>
<p>Welcome to our fresh installation! This is your opportunity to tell customers about your business.</p>
<p>We use Bagisto, a modern open-source e-commerce platform built on Laravel, to power this store.</p>
<h2>Why Choose Us?</h2>
<ul>
<li>Quality Products</li>
<li>Fast Shipping</li>
<li>Excellent Customer Service</li>
<li>Secure Checkout</li>
</ul>',
            1,
            $defaultLocale->code,
            'seo-keywords, about, store',
            'Learn about our store',
            $now
        );

        // Contact Us Page (usually handled separately but we create content)
        $this->createCMSPage(
            'Contact Us',
            'contact-us',
            '<h1>Get In Touch</h1>
<p>We would love to hear from you. Please reach out with any questions.</p>
<div class="contact-info">
<h2>Contact Information</h2>
<p><strong>Email:</strong> support@example.com</p>
<p><strong>Phone:</strong> +1 (555) 123-4567</p>
<p><strong>Hours:</strong> Monday - Friday, 9AM - 5PM EST</p>
</div>',
            1,
            $defaultLocale->code,
            'contact, support, help',
            'Contact our support team',
            $now
        );

        // Return Policy
        $this->createCMSPage(
            'Return Policy',
            'return-policy',
            '<h1>Return Policy</h1>
<p>We want you to be completely satisfied with your purchase.</p>
<h2>Return Guidelines</h2>
<ul>
<li>Returns accepted within 30 days of purchase</li>
<li>Items must be unused and in original packaging</li>
<li>Free return shipping for defective items</li>
<li>Refunds processed within 5-10 business days</li>
</ul>
<p>For more information, please contact our customer service team.</p>',
            1,
            $defaultLocale->code,
            'returns, policy, refund',
            'Our return and refund policy',
            $now
        );

        // Shipping Information
        $this->createCMSPage(
            'Shipping Information',
            'shipping-information',
            '<h1>Shipping Information</h1>
<p>We ship to customers worldwide with multiple shipping options.</p>
<h2>Shipping Methods</h2>
<ul>
<li><strong>Standard Shipping:</strong> 5-10 business days</li>
<li><strong>Express Shipping:</strong> 2-3 business days</li>
<li><strong>Overnight Shipping:</strong> Next business day</li>
</ul>
<h2>Shipping Costs</h2>
<p>Shipping costs are calculated at checkout based on your location and selected method.</p>',
            1,
            $defaultLocale->code,
            'shipping, delivery, orders',
            'Shipping options and rates',
            $now
        );

        // Privacy Policy
        $this->createCMSPage(
            'Privacy Policy',
            'privacy-policy',
            '<h1>Privacy Policy</h1>
<p>Your privacy is important to us. This page explains how we collect, use, and protect your information.</p>
<h2>Information We Collect</h2>
<ul>
<li>Personal information (name, email, address)</li>
<li>Payment information (processed securely)</li>
<li>Browsing behavior (via analytics)</li>
</ul>
<h2>Data Protection</h2>
<p>We use industry-standard security measures to protect your data. All payment processing is encrypted and PCI DSS compliant.</p>',
            1,
            $defaultLocale->code,
            'privacy, policy, data, security',
            'Our privacy and data protection policy',
            $now
        );

        // Terms of Service
        $this->createCMSPage(
            'Terms of Service',
            'terms-of-service',
            '<h1>Terms of Service</h1>
<p>By using our store, you agree to these terms and conditions.</p>
<h2>Product Availability</h2>
<p>We strive to keep product information accurate. We reserve the right to limit quantities and cancel orders at our discretion.</p>
<h2>User Responsibilities</h2>
<p>Users agree to provide accurate information and use the store lawfully and responsibly.</p>
<h2>Limitation of Liability</h2>
<p>We are not liable for indirect, incidental, or consequential damages resulting from your use of our store.</p>',
            1,
            $defaultLocale->code,
            'terms, conditions, legal',
            'Our terms and conditions',
            $now
        );

        $this->command?->info('Cream frontend pages seeded successfully.');

        // Seed homepage theme customizations (product carousels)
        $this->seedThemeCustomizations($defaultLocale->code);
    }

    /**
     * Seed theme_customizations and their translations so the homepage
     * renders product carousels (new products, featured, all products).
     */
    private function seedThemeCustomizations(string $locale): void
    {
        try {
            $channelId = \DB::table('channels')->where('code', 'default')->value('id');

            if (!$channelId) {
                $this->command?->warn('No default channel found. Skipping theme customizations.');
                return;
            }

            // Remove existing customizations for this channel to avoid duplicates
            $existingIds = \DB::table('theme_customizations')
                ->where('channel_id', $channelId)
                ->pluck('id');

            if ($existingIds->isNotEmpty()) {
                \DB::table('theme_customization_translations')
                    ->whereIn('theme_customization_id', $existingIds)
                    ->delete();
                \DB::table('theme_customizations')
                    ->whereIn('id', $existingIds)
                    ->delete();
            }

            $themeCode = 'default';
            $now = Carbon::now();

            $customizations = [
                [
                    'type'       => 'product_carousel',
                    'name'       => 'New Products',
                    'sort_order' => 1,
                    'status'     => 1,
                    'options'    => [
                        'title'   => 'New Products',
                        'filters' => ['new' => 1, 'sort' => 'name-asc', 'limit' => 12],
                    ],
                ],
                [
                    'type'       => 'product_carousel',
                    'name'       => 'Featured Products',
                    'sort_order' => 2,
                    'status'     => 1,
                    'options'    => [
                        'title'   => 'Featured Products',
                        'filters' => ['featured' => 1, 'sort' => 'name-desc', 'limit' => 12],
                    ],
                ],
                [
                    'type'       => 'product_carousel',
                    'name'       => 'All Products',
                    'sort_order' => 3,
                    'status'     => 1,
                    'options'    => [
                        'title'   => 'All Products',
                        'filters' => ['sort' => 'name-asc', 'limit' => 12],
                    ],
                ],
                [
                    'type'       => 'footer_links',
                    'name'       => 'Footer Links',
                    'sort_order' => 4,
                    'status'     => 1,
                    'options'    => [
                        'column_1' => [
                            ['url' => '/about-us',            'title' => 'About Us',             'sort_order' => 1],
                            ['url' => '/contact',             'title' => 'Contact Us',           'sort_order' => 2],
                            ['url' => '/return-policy',       'title' => 'Return Policy',        'sort_order' => 3],
                        ],
                        'column_2' => [
                            ['url' => '/privacy-policy',      'title' => 'Privacy Policy',       'sort_order' => 1],
                            ['url' => '/shipping-information','title' => 'Shipping Policy',      'sort_order' => 2],
                            ['url' => '/terms-of-service',    'title' => 'Terms & Conditions',   'sort_order' => 3],
                        ],
                    ],
                ],
                [
                    'type'       => 'services_content',
                    'name'       => 'Services',
                    'sort_order' => 5,
                    'status'     => 1,
                    'options'    => [
                        'services' => [
                            ['service_icon' => 'icon-truck',       'title' => 'Free Shipping',  'description' => 'Free shipping on orders over $50'],
                            ['service_icon' => 'icon-product',     'title' => 'Easy Returns',   'description' => '30 day return policy'],
                            ['service_icon' => 'icon-dollar-sign', 'title' => 'Secure Payment', 'description' => '100% secure transactions'],
                            ['service_icon' => 'icon-support',     'title' => '24/7 Support',   'description' => 'Dedicated support team'],
                        ],
                    ],
                ],
            ];

            foreach ($customizations as $item) {
                $id = \DB::table('theme_customizations')->insertGetId([
                    'theme_code' => $themeCode,
                    'type'       => $item['type'],
                    'name'       => $item['name'],
                    'sort_order' => $item['sort_order'],
                    'status'     => $item['status'],
                    'channel_id' => $channelId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                \DB::table('theme_customization_translations')->insert([
                    'theme_customization_id' => $id,
                    'locale'                 => $locale,
                    'options'               => json_encode($item['options']),
                ]);
            }

            $this->command?->info('✓ Theme customizations seeded (product carousels + footer + services).');
        } catch (\Exception $e) {
            $this->command?->warn('Could not seed theme customizations: ' . $e->getMessage());
        }
    }

    /**
     * Create a CMS page with translations.
     */
    private function createCMSPage(
        string $title,
        string $urlKey,
        string $content,
        int $status,
        string $locale,
        string $metaKeywords,
        string $metaDescription,
        Carbon $timestamp
    ): void {
        try {
            // In this Bagisto version, `url_key` is stored in cms_page_translations.
            $existingPageId = \DB::table('cms_page_translations')
                ->where('url_key', $urlKey)
                ->where('locale', $locale)
                ->value('cms_page_id');

            $pageId = $existingPageId ?: \DB::table('cms_pages')->insertGetId([
                'layout'     => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

            \DB::table('cms_page_translations')->updateOrInsert(
                [
                    'cms_page_id' => $pageId,
                    'locale'      => $locale,
                    'url_key'     => $urlKey,
                ],
                [
                    'page_title'       => $title,
                    'html_content'     => $content,
                    'meta_title'       => $title,
                    'meta_keywords'    => $metaKeywords,
                    'meta_description' => $metaDescription,
                ]
            );

            $defaultChannelId = \DB::table('channels')->where('code', 'default')->value('id');

            if ($defaultChannelId) {
                \DB::table('cms_page_channels')->updateOrInsert(
                    [
                        'cms_page_id' => $pageId,
                        'channel_id'  => $defaultChannelId,
                    ],
                    [
                        'cms_page_id' => $pageId,
                        'channel_id'  => $defaultChannelId,
                    ]
                );
            }

            $this->command?->line("Created page: {$title} ({$urlKey})");
        } catch (\Exception $e) {
            $this->command?->warn("Could not create page '{$title}': " . $e->getMessage());
        }
    }
}
