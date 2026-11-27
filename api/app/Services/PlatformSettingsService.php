<?php

namespace App\Services;

use App\Models\PlatformSetting;
use App\Models\PlatformSettingHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PlatformSettingsService
{
    public function defaults(): array
    {
        return [
            'general' => [
                'domain' => 'ivisionone.com',
                'timezone' => 'Europe/London',
                'language' => 'en-GB',
                'currency' => 'GBP',
            ],

            'company' => [
                'registered_name' => 'IVISION',
                'registration_number' => '',
                'vat_number' => '',
                'address' => '',
                'telephone' => '',
            ],

            'contact' => [
                'support_email' => '',
                'administration_email' => '',
                'billing_email' => '',
                'privacy_email' => '',
            ],

            'website' => [
                'seo_title' =>
                    'IVISION — Intelligent Digital Technology',

                'seo_description' =>
                    'Intelligent digital solutions for modern businesses.',

                'facebook' => '',
                'instagram' => '',
                'linkedin' => '',
                'youtube' => '',
            ],
        ];
    }

    public function all(): array
    {
        $settings = $this->defaults();

        $records = PlatformSetting::query()
            ->whereIn(
                'group',
                array_keys($settings)
            )
            ->get();

        foreach ($records as $record) {
            if (
                array_key_exists(
                    $record->group,
                    $settings
                )
                && array_key_exists(
                    $record->key,
                    $settings[$record->group]
                )
            ) {
                $settings[$record->group][$record->key] =
                    $record->value['value'] ?? null;
            }
        }

        return $settings;
    }

    public function update(
        string $group,
        array $values,
        User $administrator
    ): array {
        $defaults = $this->defaults();

        abort_unless(
            array_key_exists($group, $defaults),
            422,
            'Invalid settings group.'
        );

        $values = array_intersect_key(
            $values,
            $defaults[$group]
        );

        DB::transaction(function () use (
            $group,
            $values,
            $administrator
        ) {
            foreach ($values as $key => $value) {
                $setting = PlatformSetting::firstOrCreate(
                    ['key' => $key],
                    [
                        'group' => $group,
                        'value' => [
                            'value' =>
                                $this->defaults()[$group][$key],
                        ],
                    ]
                );

                $setting = PlatformSetting::query()
                    ->whereKey($setting->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $previous = $setting->value;

                $setting->update([
                    'group' => $group,
                    'value' => ['value' => $value],
                    'updated_by' => $administrator->id,
                ]);

                PlatformSettingHistory::create([
                    'user_id' => $administrator->id,
                    'section' => $group,
                    'setting_key' => $key,
                    'previous_value' => $previous,
                    'new_value' => ['value' => $value],
                    'action' => 'setting.updated',
                ]);
            }

            DB::table('audit_logs')->insert([
                'user_id' => $administrator->id,
                'action' => 'platform.settings.updated',
                'entity_type' => 'platform_settings',
                'metadata' => json_encode([
                    'group' => $group,
                    'fields' => array_keys($values),
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return $this->all();
    }
}