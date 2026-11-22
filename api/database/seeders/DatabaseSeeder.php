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

        // name, brand, category, for, concentration, bottle colour, top, heart, base, bestseller, £50ml, £100ml, description
        $catalogue = [
            ['Nuit de Oud', 'MadeExlusif', 'Oriental', 'unisex', 'Parfum', '#3b2a1e', 'Saffron, Bergamot', 'Rose, Agarwood', 'Amber, Musk', true, 89, 145,
                'A dark, resinous signature built around aged oud softened by Bulgarian rose.'],
            ['Velvet Iris', 'MadeExlusif', 'Floral', 'women', 'Eau de Parfum', '#8e7ab5', 'Pink Pepper, Pear', 'Orris, Iris', 'Vanilla, Cedar', true, 78, 125,
                'Powdery iris butter wrapped in warm vanilla - quietly luxurious.'],
            ['Santal Blanc', 'Maison Orée', 'Woody', 'unisex', 'Eau de Parfum', '#d9b98a', 'Cardamom, Fig Leaf', 'Sandalwood, Cashmeran', 'Cedar, White Musk', true, 82, 135,
                'Creamy sandalwood with a clean, skin-like musk dry-down.'],
            ['Aqua Meridian', 'Atelier Noir', 'Fresh', 'men', 'Eau de Toilette', '#5fa8c4', 'Sea Salt, Lemon', 'Neroli, Sage', 'Vetiver, Ambergris', false, 64, 105,
                'A bright Mediterranean morning: salt air, citrus and mineral vetiver.'],
            ['Rose Éternelle', 'Maison Orée', 'Floral', 'women', 'Eau de Parfum', '#c4677a', 'Lychee, Raspberry', 'Damask Rose, Peony', 'Patchouli, Musk', true, 76, 122,
                'A modern rose - juicy at the top, earthy and long-lasting underneath.'],
            ['Tonka Noir', 'Atelier Noir', 'Gourmand', 'unisex', 'Parfum', '#6b3f2a', 'Black Pepper, Cacao', 'Tonka, Coffee', 'Vanilla, Benzoin', false, 85, 138,
                'Dark chocolate and roasted tonka over a smoky vanilla base.'],
            ['Cèdre Sauvage', 'MadeExlusif', 'Woody', 'men', 'Eau de Parfum', '#4f6b4a', 'Juniper, Grapefruit', 'Cedar, Geranium', 'Oakmoss, Leather', false, 72, 118,
                'Forest-floor freshness with a polished leather edge.'],
            ['Ambre Soleil', 'Maison Orée', 'Oriental', 'women', 'Eau de Parfum', '#d98e3a', 'Mandarin, Orange Blossom', 'Amber, Jasmine', 'Honey, Sandalwood', false, 80, 130,
                'Golden amber and orange blossom - a sunset in a bottle.'],
            ['Jasmin de Nuit', 'MadeExlusif', 'Floral', 'women', 'Eau de Parfum', '#e8d9b5', 'Green Mandarin, Tuberose', 'Night Jasmine, Ylang', 'Musk, Sandalwood', true, 79, 128,
                'Heady white flowers that open after dark and linger on the skin.'],
            ['Vétiver Royal', 'Atelier Noir', 'Woody', 'men', 'Eau de Parfum', '#556b4e', 'Bergamot, Pink Pepper', 'Haitian Vetiver, Nutmeg', 'Cedar, Tonka', false, 74, 120,
                'Regal, earthy vetiver with a crisp spice lift.'],
            ['Bois d\'Argent', 'Maison Orée', 'Woody', 'unisex', 'Eau de Parfum', '#a9aeb5', 'Cypress, Lemon', 'Silver Birch, Iris', 'Cedar, Amber', false, 83, 136,
                'Cool, smoky birch wood with a silvery metallic shimmer.'],
            ['Fleur d\'Oranger', 'MadeExlusif', 'Floral', 'women', 'Eau de Toilette', '#f0c08a', 'Orange Blossom, Petitgrain', 'Neroli, Jasmine', 'White Musk', false, 62, 100,
                'Sun-warmed orange blossom, soft and radiant.'],
            ['Cuir Impérial', 'Atelier Noir', 'Oriental', 'men', 'Parfum', '#4a2f25', 'Saffron, Cardamom', 'Leather, Incense', 'Amber, Patchouli', true, 92, 150,
                'Supple black leather wrapped in smoky incense and amber.'],
            ['Citron Riviera', 'Maison Orée', 'Fresh', 'unisex', 'Eau de Toilette', '#e6d84a', 'Sicilian Lemon, Basil', 'Green Tea, Neroli', 'White Cedar, Musk', false, 58, 95,
                'A sparkling Riviera citrus that feels like sea breeze at noon.'],
            ['Vanille Gourmande', 'MadeExlusif', 'Gourmand', 'women', 'Eau de Parfum', '#e3c9a0', 'Pear, Pink Pepper', 'Vanilla Orchid, Almond', 'Tonka, Caramel', true, 77, 124,
                'Creamy Madagascan vanilla with a warm caramelised glow.'],
            ['Musc Blanc', 'Maison Orée', 'Fresh', 'unisex', 'Eau de Parfum', '#f1efe9', 'Aldehydes, Bergamot', 'White Musk, Lily', 'Cashmere Wood', false, 70, 115,
                'Clean laundered cotton and soft musk - your skin, but better.'],
            ['Oud Royal', 'MadeExlusif', 'Oriental', 'men', 'Parfum', '#2e2118', 'Cinnamon, Rum', 'Oud, Rose', 'Amber, Leather', true, 98, 160,
                'An opulent, spiced oud for evenings that matter.'],
            ['Pivoine Rosée', 'Atelier Noir', 'Floral', 'women', 'Eau de Parfum', '#e9a3b8', 'Peony, Mandarin', 'Rose Water, Lily of the Valley', 'Musk, Cedar', false, 68, 110,
                'Dewy peony petals at dawn, light and romantic.'],
            ['Noir Intense', 'Atelier Noir', 'Gourmand', 'men', 'Eau de Parfum', '#1f1b1b', 'Black Pepper, Coffee', 'Dark Chocolate, Tobacco', 'Vanilla, Cedar', false, 81, 133,
                'Bittersweet cocoa and smoky tobacco - dark, dense and magnetic.'],
            ['Lavande Impériale', 'Maison Orée', 'Fresh', 'men', 'Eau de Toilette', '#8f86c2', 'Lavender, Lemon', 'Geranium, Clary Sage', 'Tonka, Amber', false, 63, 102,
                'French lavender given a modern, barbershop-fresh twist.'],
        ];

        foreach ($catalogue as [$name, $brand, $cat, $gender, $conc, $accent, $top, $heart, $base, $featured, $p50, $p100, $desc]) {
            $product = Product::updateOrCreate(['name' => $name], [
                'brand_id' => $brands[$brand]->id,
                'category_id' => $cats[$cat]->id,
                'description' => $desc,
                'notes_top' => $top, 'notes_heart' => $heart, 'notes_base' => $base,
                'gender' => $gender, 'concentration' => $conc, 'accent' => $accent,
                'is_featured' => $featured,
                'is_active' => true,
            ]);

            foreach ([50 => $p50, 100 => $p100] as $ml => $price) {
                $product->variants()->updateOrCreate(['size_ml' => $ml], [
                    'sku' => 'MX-' . $product->id . '-' . $ml,
                    'price' => $price,
                    'stock' => 30,
                ]);
            }
        }

        Coupon::firstOrCreate(['code' => 'WELCOME10'], [
            'type' => 'percent', 'value' => 10, 'min_subtotal' => 30, 'is_active' => true,
        ]);
    }
}