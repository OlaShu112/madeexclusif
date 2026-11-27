<?php

namespace Database\Seeders;

use App\Models\PlatformExpenseCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlatformExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Hosting and Infrastructure',
                'description' => 'Cloud hosting, servers, databases, storage and infrastructure services.',
            ],
            [
                'name' => 'Domain Registration',
                'description' => 'Domain registration, renewals and DNS management.',
            ],
            [
                'name' => 'Software Subscriptions',
                'description' => 'Software licences, development tools and SaaS subscriptions.',
            ],
            [
                'name' => 'Artificial Intelligence Services',
                'description' => 'AI models, API usage, machine learning and intelligent automation services.',
            ],
            [
                'name' => 'Payment Processing Fees',
                'description' => 'Payment gateway charges, banking fees and transaction processing costs.',
            ],
            [
                'name' => 'Marketing and Advertising',
                'description' => 'Digital marketing, advertising, branding and promotional campaigns.',
            ],
            [
                'name' => 'Employee Salaries',
                'description' => 'Employee wages, salaries and payroll-related expenses.',
            ],
            [
                'name' => 'Contractor Payments',
                'description' => 'Freelancers, independent contractors and outsourced development services.',
            ],
            [
                'name' => 'Professional Services',
                'description' => 'Accounting, consultancy and other professional services.',
            ],
            [
                'name' => 'Legal and Compliance',
                'description' => 'Legal advice, regulatory compliance and data protection expenses.',
            ],
            [
                'name' => 'Office Expenses',
                'description' => 'Office supplies, equipment and workplace operating costs.',
            ],
            [
                'name' => 'Telecommunications',
                'description' => 'Internet, telephone, SMS and communication services.',
            ],
            [
                'name' => 'Insurance',
                'description' => 'Business insurance, professional indemnity and cyber insurance.',
            ],
            [
                'name' => 'Travel and Transportation',
                'description' => 'Business travel, accommodation and transportation expenses.',
            ],
            [
                'name' => 'Other Operating Expenses',
                'description' => 'Other legitimate business operating expenses.',
            ],
        ];

        foreach ($categories as $category) {
            PlatformExpenseCategory::firstOrCreate(
                [
                    'slug' => Str::slug($category['name']),
                ],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info(
            'IVISION expense categories seeded successfully.'
        );
    }
}