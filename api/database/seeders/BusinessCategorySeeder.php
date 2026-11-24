<?php

namespace Database\Seeders;

use App\Models\BusinessCategory;
use Illuminate\Database\Seeder;

class BusinessCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'perfume' => 'Perfumes & Fragrances',
            'clothing' => 'Clothing & Fashion',
            'jewellery' => 'Jewellery, Gold & Precious Metals',
            'automotive' => 'Automotive & Vehicle Parts',
            'tyres' => 'Tyres & Wheels',
            'electronics' => 'Electronics',
            'computers' => 'Computers & Accessories',
            'mobile_phones' => 'Mobile Phones & Accessories',
            'beauty' => 'Beauty & Cosmetics',
            'health' => 'Health & Wellness',
            'furniture' => 'Furniture',
            'home_garden' => 'Home & Garden',
            'food' => 'Food & Groceries',
            'sports' => 'Sports & Fitness',
            'footwear' => 'Footwear',
            'bags' => 'Bags & Accessories',
            'watches' => 'Watches',
            'books' => 'Books & Stationery',
            'toys' => 'Toys & Games',
            'baby' => 'Baby & Children',
            'pets' => 'Pets & Pet Supplies',
            'building' => 'Building & Construction',
            'industrial' => 'Industrial Equipment',
            'agriculture' => 'Agriculture & Farming',
            'art' => 'Art & Collectibles',
            'gifts' => 'Gifts & Lifestyle',
            'general' => 'General Merchandise',
        ];

        $position = 1;

        foreach ($categories as $slug => $name) {

            BusinessCategory::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'is_active' => true,
                    'sort_order' => $position,
                ]
            );

            $position++;
        }
    }
}