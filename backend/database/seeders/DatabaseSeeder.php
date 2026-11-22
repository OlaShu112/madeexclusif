<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@madeexlusif.test'], [
            'name' => 'Store Admin', 'password' => 'ChangeMe!2026', 'role' => 'admin',
        ]);
        User::updateOrCreate(['email' => 'customer@madeexlusif.test'], [
            'name' => 'Demo Customer', 'password' => 'password123', 'role' => 'customer',
        ]);

        $cats = collect(['Floral', 'Woody', 'Oriental', 'Fresh', 'Gourmand'])
            ->mapWithKeys(fn ($n) => [$n => Category::firstOrCreate(['name' => $n])]);
        $brands = collect(['MadeExlusif', 'Maison Orée', 'Atelier Noir'])
            ->mapWithKeys(fn ($n) => [$n => Brand::firstOrCreate(['name' => $n])]);

        // [name, brand, category, gender, concentration, accent, top, heart, base, featured, description]
        $catalogue = [
            ['Nuit de Oud', 'MadeExlusif', 'Oriental', 'unisex', 'Parfum', '#3b2a1e', 'Saffron, Bergamot', 'Rose, Agarwood', 'Amber, Musk', true,
                'A dark, resinous signature built around aged oud softened by Bulgarian rose.'],
            ['Velvet Iris', 'MadeExlusif', 'Floral', 'women', 'Eau de Parfum', '#8e7ab5', 'Pink Pepper, Pear', 'Orris, Iris', 'Vanilla, Cedar', true,
                'Powdery iris butter wrapped in warm vanilla - quietly luxurious.'],
            ['Santal Blanc', 'Maison Orée', 'Woody', 'unisex', 'Eau de Parfum', '#d9b98a', 'Cardamom, Fig Leaf', 'Sandalwood, Cashmeran', 'Cedar, White Musk', true,
                'Creamy sandalwood with a clean, skin-like musk dry-down.'],
            ['Aqua Meridian', 'Atelier Noir', 'Fresh', 'men', 'Eau de Toilette', '#5fa8c4', 'Sea Salt, Lemon', 'Neroli, Sage', 'Vetiver, Ambergris', false,
                'A bright Mediterranean morning: salt air, citrus, and mineral vetiver.'],
            ['Rose Éternelle', 'Maison Orée', 'Floral', 'women', 'Eau de Parfum', '#c4677a', 'Lychee, Raspberry', 'Damask Rose, Peony', 'Patchouli, Musk', true,
                'A modern rose - juicy at the top, earthy and long-lasting underneath.'],
            ['Tonka Noir', 'Atelier Noir', 'Gourmand', 'unisex', 'Parfum', '#6b3f2a', 'Black Pepper, Cacao', 'Tonka, Coffee', 'Vanilla, Benzoin', false,
                'Dark chocolate and roasted tonka over a smoky vanilla base.'],
            ['Cèdre Sauvage', 'MadeExlusif', 'Woody', 'men', 'Eau de Parfum', '#4f6b4a', 'Juniper, Grapefruit', 'Cedar, Geranium', 'Oakmoss, Leather', false,
                'Forest-floor freshness with a polished leather edge.'],
            ['Ambre Soleil', 'Maison Orée', 'Oriental', 'women', 'Eau de Parfum', '#d98e3a', 'Mandarin, Orange Blossom', 'Amber, Jasmine', 'Honey, Sandalwood', false,
                'Golden amber and orange blossom - a sunset in a bottle.'],
        ];

        $sizes = [[30, 0.6], [50, 1.0], [100, 1.65]]; // ml, price multiplier

        foreach ($catalogue as $i => [$name, $brand, $cat, $gender, $conc, $accent, $top, $heart, $base, $featured, $desc]) {
            $product = Product::firstOrCreate(['name' => $name], [
                'brand_id' => $brands[$brand]->id,
                'category_id' => $cats[$cat]->id,
                'description' => $desc,
                'notes_top' => $top, 'notes_heart' => $heart, 'notes_base' => $base,
                'gender' => $gender, 'concentration' => $conc, 'accent' => $accent,
                'is_featured' => $featured,
            ]);

            $basePrice = 58 + ($i * 7);
            foreach ($sizes as [$ml, $mult]) {
                $product->variants()->firstOrCreate(['size_ml' => $ml], [
                    'sku' => 'MX-' . $product->id . '-' . $ml,
                    'price' => round($basePrice * $mult, 2),
                    'stock' => 25,
                ]);
            }
        }

        Coupon::firstOrCreate(['code' => 'WELCOME10'], [
            'type' => 'percent', 'value' => 10, 'min_subtotal' => 30, 'is_active' => true,
        ]);
    }
}
