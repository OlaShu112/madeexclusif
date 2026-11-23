<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ClothingStoreSeeder extends Seeder
{
    /**
     * Seed a demonstration clothing store with products and variants.
     */
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Demo Seller
        |--------------------------------------------------------------------------
        */

        $seller = User::firstOrCreate(
            ['email' => 'seller@madeexlusif.test'],
            [
                'name' => 'Demo Seller',
                'password' => 'password123',
                'role' => 'customer',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Clothing Brand
        |--------------------------------------------------------------------------
        */

        $brand = Brand::firstOrCreate(
            ['name' => 'Atelier Mode'],
            ['slug' => 'atelier-mode']
        );

        /*
        |--------------------------------------------------------------------------
        | Clothing Store
        |--------------------------------------------------------------------------
        */

        $store = Store::updateOrCreate(
            ['user_id' => $seller->id],
            [
                'brand_id' => $brand->id,
                'name' => 'Atelier Mode',
                'slug' => 'atelier-mode',
                'type' => 'clothing',
                'tagline' => 'Everyday essentials, thoughtfully made',
                'accent' => '#2f3a45',
                'is_main' => false,
                'status' => 'active',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Clothing Categories
        |--------------------------------------------------------------------------
        */

        $categoryNames = [
            'T-Shirts',
            'Shirts',
            'Dresses',
            'Trousers',
            'Outerwear',
            'Knitwear',
        ];

        $categories = [];

        foreach ($categoryNames as $name) {
            $categories[$name] = Category::firstOrCreate(
                [
                    'name' => $name,
                    'type' => 'clothing',
                ],
                [
                    'slug' => Str::slug($name),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Clothing Catalogue
        |--------------------------------------------------------------------------
        */

        $catalogue = [
            [
                'name' => 'Classic Cotton Tee',
                'category' => 'T-Shirts',
                'gender' => 'unisex',
                'fabric' => '100% Organic Cotton',
                'colour' => 'White',
                'price' => 29.00,
                'accent' => '#e8e6e1',
                'description' => 'A relaxed everyday tee in heavyweight organic cotton.',
            ],
            [
                'name' => 'Oxford Button-Down',
                'category' => 'Shirts',
                'gender' => 'men',
                'fabric' => '100% Cotton',
                'colour' => 'Sky Blue',
                'price' => 55.00,
                'accent' => '#9fc3e0',
                'description' => 'A crisp oxford shirt that works from desk to dinner.',
            ],
            [
                'name' => 'Linen Summer Dress',
                'category' => 'Dresses',
                'gender' => 'women',
                'fabric' => '100% Linen',
                'colour' => 'Sand',
                'price' => 79.00,
                'accent' => '#d9c7a6',
                'description' => 'A breezy midi dress that softens with every wash.',
            ],
            [
                'name' => 'Tapered Chinos',
                'category' => 'Trousers',
                'gender' => 'men',
                'fabric' => '98% Cotton, 2% Elastane',
                'colour' => 'Navy',
                'price' => 65.00,
                'accent' => '#2c3e50',
                'description' => 'Slim through the leg with a little stretch for comfort.',
            ],
            [
                'name' => 'Wool Overcoat',
                'category' => 'Outerwear',
                'gender' => 'unisex',
                'fabric' => '80% Wool, 20% Nylon',
                'colour' => 'Charcoal',
                'price' => 189.00,
                'accent' => '#3a3a3c',
                'description' => 'A tailored overcoat with a clean, minimal line.',
            ],
            [
                'name' => 'Merino Crew Jumper',
                'category' => 'Knitwear',
                'gender' => 'women',
                'fabric' => '100% Merino Wool',
                'colour' => 'Oatmeal',
                'price' => 89.00,
                'accent' => '#cdbfa8',
                'description' => 'Fine-gauge merino that is warm without the bulk.',
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Create Products and Variants
        |--------------------------------------------------------------------------
        */

        foreach ($catalogue as $index => $item) {
            $product = Product::updateOrCreate(
                [
                    'store_id' => $store->id,
                    'slug' => Str::slug($item['name']),
                ],
                [
                    'name' => $item['name'],
                    'brand_id' => $brand->id,
                    'category_id' => $categories[$item['category']]->id,
                    'description' => $item['description'],
                    'gender' => $item['gender'],
                    'concentration' => $item['fabric'],
                    'accent' => $item['accent'],
                    'is_featured' => $index < 3,
                    'is_active' => true,
                ]
            );

            /*
            | Clothing sizes
            */

            $sizes = ['S', 'M', 'L', 'XL'];

            foreach ($sizes as $size) {
                $label = $size . ' - ' . $item['colour'];

                $sku = 'MX-' . $product->id . '-' . $size;

                $product->variants()->updateOrCreate(
                    [
                        'sku' => $sku,
                    ],
                    [
                        'size_ml' => null,
                        'size_label' => $label,
                        'price' => $item['price'],
                        'stock' => 15,
                    ]
                );
            }
        }

        $this->command?->info(
            'Atelier Mode clothing store seeded successfully.'
        );
    }
}

