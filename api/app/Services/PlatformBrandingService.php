<?php

namespace App\Services;

use App\Models\PlatformBranding;
use App\Models\PlatformSettingHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PlatformBrandingService
{
    public function defaults(): array
    {
        return [
            'platform_name' => 'IVISION',
            'tagline' => 'Intelligent Digital Technology',
            'administration_title' => 'Platform Administration',
            'browser_title' => 'IVISION — Administration Portal',

            'primary_logo_id' => null,
            'light_logo_id' => null,
            'dark_logo_id' => null,
            'sidebar_logo_id' => null,
            'favicon_id' => null,
            'login_logo_id' => null,
            'login_background_id' => null,
            'email_logo_id' => null,

            'primary_colour' => '#111827',
            'secondary_colour' => '#334155',
            'accent_colour' => '#C9A86A',
            'background_colour' => '#F5F6F9',
            'text_colour' => '#111827',
            'sidebar_colour' => '#111827',
            'sidebar_text_colour' => '#E5E7EB',
            'active_menu_colour' => '#374151',
            'header_colour' => '#FFFFFF',
            'footer_colour' => '#FFFFFF',
            'button_colour' => '#111827',
            'button_text_colour' => '#FFFFFF',
            'login_background_colour' => '#F5F6F9',

            'font_family' => 'Geist',
            'appearance' => 'light',
            'copyright_text' => 'All rights reserved.',
        ];
    }

    public function relationships(): array
    {
        return [
            'primaryLogo',
            'lightLogo',
            'darkLogo',
            'sidebarLogo',
            'favicon',
            'loginLogo',
            'loginBackground',
            'emailLogo',
        ];
    }

    public function get(): PlatformBranding
    {
        $branding = PlatformBranding::firstOrCreate(
            ['identifier' => 'default'],
            $this->defaults()
        );

        return $branding->load(
            $this->relationships()
        );
    }

    public function update(
        array $data,
        User $administrator
    ): PlatformBranding {
        $this->get();

        return DB::transaction(function () use (
            $data,
            $administrator
        ) {
            $branding = PlatformBranding::query()
                ->where('identifier', 'default')
                ->lockForUpdate()
                ->firstOrFail();

            $data = array_intersect_key(
                $data,
                $this->defaults()
            );

            $previous = $branding->only(
                array_keys($data)
            );

            $branding->fill($data);

            $branding->updated_by =
                $administrator->id;

            $branding->save();

            PlatformSettingHistory::create([
                'user_id' => $administrator->id,
                'section' => 'branding',
                'setting_key' => 'platform_branding',
                'previous_value' => $previous,
                'new_value' => $branding->only(
                    array_keys($data)
                ),
                'action' => 'branding.updated',
            ]);

            DB::table('audit_logs')->insert([
                'user_id' => $administrator->id,
                'action' => 'platform.branding.updated',
                'entity_type' => 'platform_branding',
                'entity_id' => $branding->id,
                'metadata' => json_encode([
                    'fields' => array_keys($data),
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $branding->load(
                $this->relationships()
            );
        });
    }

    public function reset(
        User $administrator
    ): PlatformBranding {
        return $this->update(
            $this->defaults(),
            $administrator
        );
    }

    public function publicBranding(): array
    {
        $branding = $this->get();

        $data = $branding->only(
            array_keys($this->defaults())
        );

        foreach ($this->relationships() as $relation) {
            $data[$relation] =
                $branding->{$relation}?->url;
        }

        return $data;
    }
}