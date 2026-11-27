<?php

namespace Database\Seeders;

use App\Models\PlatformTaxRate;
use Illuminate\Database\Seeder;

class PlatformTaxRateSeeder extends Seeder
{
    public function run(): void
    {
        $rates = [
            [
                'name' => 'UK Standard VAT',
                'country' => 'GB',
                'percentage' => 20.00,
                'is_inclusive' => false,
                'is_active' => false,
            ],
            [
                'name' => 'UK Reduced VAT',
                'country' => 'GB',
                'percentage' => 5.00,
                'is_inclusive' => false,
                'is_active' => false,
            ],
            [
                'name' => 'UK Zero-Rated VAT',
                'country' => 'GB',
                'percentage' => 0.00,
                'is_inclusive' => false,
                'is_active' => false,
            ],
        ];

        foreach ($rates as $rate) {
            PlatformTaxRate::firstOrCreate(
                [
                    'name' => $rate['name'],
                    'country' => $rate['country'],
                ],
                [
                    'percentage' => $rate['percentage'],
                    'is_inclusive' => $rate['is_inclusive'],
                    'is_active' => $rate['is_active'],
                    'starts_at' => null,
                    'ends_at' => null,
                ]
            );
        }

        $this->command?->info(
            'IVISION tax rates seeded successfully.'
        );
    }
}