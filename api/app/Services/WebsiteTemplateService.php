<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Store;
use App\Models\StoreTheme;
use Illuminate\Support\Facades\DB;

class WebsiteTemplateService
{
    /**
     * Supported business website templates.
     */
    private const TEMPLATES = [

        'perfume' => [
            'title' => 'Discover your signature scent',
            'subtitle' => 'Explore our collection of perfumes and fragrances.',
            'button' => 'Shop Fragrances',
            'shop_label' => 'All Fragrances',
        ],

        'clothing' => [
            'title' => 'Discover your personal style',
            'subtitle' => 'Explore our latest fashion and clothing collections.',
            'button' => 'Shop Collection',
            'shop_label' => 'All Clothing',
        ],

        'jewellery' => [
            'title' => 'Timeless elegance',
            'subtitle' => 'Discover our jewellery and precious metal collections.',
            'button' => 'Explore Jewellery',
            'shop_label' => 'All Jewellery',
        ],

        'automotive' => [
            'title' => 'Keep your vehicle moving',
            'subtitle' => 'Explore automotive products and vehicle parts.',
            'button' => 'Shop Vehicle Parts',
            'shop_label' => 'All Vehicle Parts',
        ],

        'tyres' => [
            'title' => 'Find the right tyres and wheels',
            'subtitle' => 'Explore our range of tyres, wheels and accessories.',
            'button' => 'Shop Tyres',
            'shop_label' => 'All Tyres & Wheels',
        ],

        'electronics' => [
            'title' => 'Discover the world of technology',
            'subtitle' => 'Explore our electronics and technology products.',
            'button' => 'Shop Electronics',
            'shop_label' => 'All Electronics',
        ],

        'computers' => [
            'title' => 'Technology for work and everyday life',
            'subtitle' => 'Discover computers and accessories.',
            'button' => 'Shop Computers',
            'shop_label' => 'All Computers',
        ],

        'mobile_phones' => [
            'title' => 'Stay connected',
            'subtitle' => 'Explore mobile phones and accessories.',
            'button' => 'Shop Mobile Phones',
            'shop_label' => 'All Mobile Phones',
        ],

        'beauty' => [
            'title' => 'Discover your beauty essentials',
            'subtitle' => 'Explore skincare, cosmetics and beauty products.',
            'button' => 'Shop Beauty',
            'shop_label' => 'All Beauty Products',
        ],

        'health' => [
            'title' => 'Everyday health and wellbeing',
            'subtitle' => 'Explore our health and wellness collection.',
            'button' => 'Explore Products',
            'shop_label' => 'Health & Wellness',
        ],

        'furniture' => [
            'title' => 'Create a space you love',
            'subtitle' => 'Discover furniture for your home and workplace.',
            'button' => 'Shop Furniture',
            'shop_label' => 'All Furniture',
        ],

        'home_garden' => [
            'title' => 'Make your house a home',
            'subtitle' => 'Explore products for your home and garden.',
            'button' => 'Shop Home & Garden',
            'shop_label' => 'Home & Garden',
        ],

        'food' => [
            'title' => 'Discover your everyday favourites',
            'subtitle' => 'Explore our food and grocery collection.',
            'button' => 'Shop Groceries',
            'shop_label' => 'All Groceries',
        ],

        'sports' => [
            'title' => 'Move with confidence',
            'subtitle' => 'Explore sportswear, fitness products and equipment.',
            'button' => 'Shop Sports',
            'shop_label' => 'Sports & Fitness',
        ],

        'footwear' => [
            'title' => 'Step into your next adventure',
            'subtitle' => 'Discover footwear for every occasion.',
            'button' => 'Shop Footwear',
            'shop_label' => 'All Footwear',
        ],

        'bags' => [
            'title' => 'Complete your everyday look',
            'subtitle' => 'Explore our bags and accessories collection.',
            'button' => 'Shop Accessories',
            'shop_label' => 'Bags & Accessories',
        ],

        'watches' => [
            'title' => 'Make every moment count',
            'subtitle' => 'Discover our collection of watches.',
            'button' => 'Shop Watches',
            'shop_label' => 'All Watches',
        ],

        'books' => [
            'title' => 'Discover your next inspiration',
            'subtitle' => 'Explore books, stationery and everyday essentials.',
            'button' => 'Browse Collection',
            'shop_label' => 'Books & Stationery',
        ],

        'toys' => [
            'title' => 'Discover a world of play',
            'subtitle' => 'Explore toys, games and creative products.',
            'button' => 'Shop Toys',
            'shop_label' => 'Toys & Games',
        ],

        'baby' => [
            'title' => 'For every little moment',
            'subtitle' => 'Discover products for babies and children.',
            'button' => 'Shop Collection',
            'shop_label' => 'Baby & Children',
        ],

        'pets' => [
            'title' => 'Everything for your favourite companion',
            'subtitle' => 'Explore pet products and everyday essentials.',
            'button' => 'Shop Pet Supplies',
            'shop_label' => 'All Pet Supplies',
        ],

        'building' => [
            'title' => 'Build with confidence',
            'subtitle' => 'Explore building materials, tools and supplies.',
            'button' => 'Shop Building Supplies',
            'shop_label' => 'Building & Construction',
        ],

        'industrial' => [
            'title' => 'Equipment for your business',
            'subtitle' => 'Discover industrial equipment and supplies.',
            'button' => 'Browse Equipment',
            'shop_label' => 'Industrial Equipment',
        ],

        'agriculture' => [
            'title' => 'Supporting your farming needs',
            'subtitle' => 'Explore agricultural products and equipment.',
            'button' => 'Shop Agriculture',
            'shop_label' => 'Agriculture & Farming',
        ],

        'art' => [
            'title' => 'Discover something extraordinary',
            'subtitle' => 'Explore art, collectibles and creative pieces.',
            'button' => 'Explore Collection',
            'shop_label' => 'Art & Collectibles',
        ],

        'gifts' => [
            'title' => 'Something special for every occasion',
            'subtitle' => 'Discover gifts and lifestyle products.',
            'button' => 'Shop Gifts',
            'shop_label' => 'Gifts & Lifestyle',
        ],

        'general' => [
            'title' => 'Discover our latest collection',
            'subtitle' => 'Explore our products and everyday essentials.',
            'button' => 'Shop Collection',
            'shop_label' => 'Shop All',
        ],
    ];

    /**
     * Determine the appropriate website template.
     */
    public function templateKey(Store $store): string
    {
        $key = $store->businessCategory?->slug
            ?: $store->type
            ?: 'general';

        return array_key_exists($key, self::TEMPLATES)
            ? $key
            : 'general';
    }

    /**
     * Generate business-specific navigation.
     */
    public function navigation(Store $store): array
    {
        $key = $this->templateKey($store);

        $template = self::TEMPLATES[$key];

        $items = [
            [
                'label' => 'Home',
                'type' => 'home',
                'enabled' => true,
            ],
            [
                'label' => $template['shop_label'],
                'type' => 'shop',
                'query' => [],
                'enabled' => true,
            ],
        ];

        $categories = Category::query()
            ->where(
                'business_category_id',
                $store->business_category_id
            )
            ->orderBy('id')
            ->get(['id', 'name', 'slug']);

        foreach ($categories as $category) {
            $items[] = [
                'label' => $category->name,
                'type' => 'category',
                'category_id' => $category->id,
                'category_slug' => $category->slug,
                'enabled' => true,
            ];
        }

        $items[] = [
            'label' => 'Featured',
            'type' => 'shop',
            'query' => ['featured' => 1],
            'enabled' => true,
        ];

        return $items;
    }

    /**
     * Generate default website configuration.
     */
    public function defaults(Store $store): array
    {
        $key = $this->templateKey($store);

        $template = self::TEMPLATES[$key];

        $primary = preg_match(
            '/^#[0-9a-fA-F]{6}$/',
            $store->accent ?? ''
        )
            ? $store->accent
            : '#222222';

        return [

            // Template and typography.

            'template_key' => $key,
            'font_family' => 'system',

            // Website colours.

            'primary_colour' => $primary,
            'secondary_colour' => '#f5f5f5',
            'background_colour' => '#ffffff',
            'text_colour' => '#222222',
            'header_colour' => $primary,
            'footer_colour' => $primary,

            // Announcement bar.

            'announcement_enabled' => true,
            'announcement_text' =>
                'Welcome to ' . $store->name
                . ' — Explore our collection',

            'announcement_colour' => $primary,

            // Header and navigation.

            'navigation_items' => $this->navigation($store),

            'header_settings' => [
                'show_logo' => true,
                'show_business_name' => true,
                'show_search' => true,
                'show_account' => true,
                'show_cart' => true,
                'sticky_header' => false,
            ],

            // Homepage.

            'hero_title' => $template['title'],

            'hero_subtitle' => $store->tagline
                ?: $template['subtitle'],

            'hero_image' => null,

            'hero_button_text' => $template['button'],

            'homepage_sections' => [
                [
                    'type' => 'hero',
                    'enabled' => true,
                    'position' => 1,
                ],
                [
                    'type' => 'categories',
                    'title' => 'Shop by Category',
                    'enabled' => true,
                    'position' => 2,
                ],
                [
                    'type' => 'featured_products',
                    'title' => 'Featured Products',
                    'enabled' => true,
                    'position' => 3,
                ],
                [
                    'type' => 'about_business',
                    'title' => 'About ' . $store->name,
                    'enabled' => true,
                    'position' => 4,
                ],
            ],

            // Footer.

            'footer_description' => $store->tagline
                ?: $template['subtitle'],

            'footer_columns' => [
                [
                    'type' => 'business',
                    'title' => $store->name,
                    'enabled' => true,
                ],
                [
                    'type' => 'navigation',
                    'title' => 'Shop',
                    'enabled' => true,
                ],
                [
                    'type' => 'contact',
                    'title' => 'Customer Support',
                    'enabled' => true,
                ],
                [
                    'type' => 'delivery',
                    'title' => 'Delivery & Returns',
                    'enabled' => true,
                ],
            ],

            'contact_email' => null,
            'contact_phone' => null,
            'delivery_information' => null,

            // Social media.

            'social_links' => [],

            // Search engine optimisation.

            'seo_title' => $store->name,

            'seo_description' => $store->tagline
                ?: $template['subtitle'],

            // Publication.

            'published_at' => null,
        ];
    }

    /**
     * Initialise a website without overwriting
     * existing seller customisation.
     */
    public function initialise(Store $store): StoreTheme
    {
        return DB::transaction(function () use ($store) {

            $lockedStore = Store::query()
                ->lockForUpdate()
                ->findOrFail($store->id);

            $defaults = $this->defaults($lockedStore);

            $theme = $lockedStore
                ->websiteTheme()
                ->first();

            if (!$theme) {
                return $lockedStore
                    ->websiteTheme()
                    ->create($defaults);
            }

            $updates = [];

            $fields = [
                'navigation_items',
                'header_settings',
                'hero_title',
                'hero_subtitle',
                'homepage_sections',
                'footer_columns',
                'social_links',
                'seo_title',
                'seo_description',
            ];

            foreach ($fields as $field) {
                if ($theme->{$field} === null) {
                    $updates[$field] = $defaults[$field];
                }
            }

            // Identify older themes that have not yet
            // received website builder navigation.

            if ($theme->navigation_items === null) {

                $updates['template_key'] =
                    $defaults['template_key'];

                $updates['hero_button_text'] =
                    $defaults['hero_button_text'];
            }

            if (!empty($updates)) {
                $theme->update($updates);
            }

            return $theme->refresh();

        }, 3);
    }
}