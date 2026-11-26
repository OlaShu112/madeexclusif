<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Store;
use App\Services\WebsiteTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreThemeController extends Controller
{
    /**
     * Retrieve the authenticated seller's store.
     */
    private function currentStore(Request $request): Store
    {
        $store = $request->attributes->get('store');

        abort_unless(
            $store instanceof Store,
            403,
            'Store access denied.'
        );

        abort_unless(
            (int) $store->user_id ===
                (int) $request->user()->id,
            403,
            'Store ownership verification failed.'
        );

        abort_unless(
            $store->status === 'active',
            403,
            'Store is not active.'
        );

        return $store;
    }

    /**
     * Retrieve the seller's website configuration.
     *
     * Return published settings and unpublished
     * draft settings separately.
     */
    public function show(
        Request $request,
        WebsiteTemplateService $templates
    ): JsonResponse
    {
        $store = $this->currentStore($request);

        $theme = $store->websiteTheme;

        $categories = Category::query()
            ->whereNotNull('business_category_id')
            ->where(
                'business_category_id',
                $store->business_category_id
            )
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'slug',
            ]);

        return response()->json([

            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'logo_url' => $store->logo_url,
                'tagline' => $store->tagline,
                'type' => $store->type,
                'status' => $store->status,
                'business_category' =>
                    $store->businessCategory,
            ],

            /*
             * Published website configuration.
             */

            'theme' => $theme
                ? $theme->toArray()
                : $templates->defaults($store),

            /*
             * Unpublished website configuration.
             */

            'draft' => $theme?->draft_settings,

            'has_draft' =>
                $theme?->draft_settings !== null,

            'published_at' =>
                $theme?->published_at,

            'categories' => $categories,

            'available_fonts' => [
                'system',
                'Arial',
                'Georgia',
                'Verdana',
                'Tahoma',
                'Trebuchet MS',
                'Times New Roman',
            ],
        ]);
    }

    /**
     * Save the seller's website configuration as a draft.
     *
     * The published website remains unchanged.
     */
    public function update(
        Request $request,
        WebsiteTemplateService $templates
    ): JsonResponse
    {
        return $this->saveSettings(
            $request,
            $templates,
            false
        );
    }

    /**
     * Publish the seller's website configuration.
     */
    public function publish(
        Request $request,
        WebsiteTemplateService $templates
    ): JsonResponse
    {
        return $this->saveSettings(
            $request,
            $templates,
            true
        );
    }

    /**
     * Validate and save website settings.
     *
     * Supports both draft saving and live publication.
     */
    private function saveSettings(
        Request $request,
        WebsiteTemplateService $templates,
        bool $publish
    ): JsonResponse
    {
        $store = $this->currentStore($request);

        $colour = [
            'sometimes',
            'required',
            'regex:/^#[0-9a-fA-F]{6}$/',
        ];

        $data = $request->validate([

            /*
            |--------------------------------------------------------------------------
            | Brand colours
            |--------------------------------------------------------------------------
            */

            'primary_colour' => $colour,
            'secondary_colour' => $colour,
            'background_colour' => $colour,
            'text_colour' => $colour,
            'header_colour' => $colour,
            'footer_colour' => $colour,

            'font_family' => [
                'sometimes',
                'required',
                Rule::in([
                    'system',
                    'Arial',
                    'Georgia',
                    'Verdana',
                    'Tahoma',
                    'Trebuchet MS',
                    'Times New Roman',
                ]),
            ],

            /*
            |--------------------------------------------------------------------------
            | Announcement bar
            |--------------------------------------------------------------------------
            */

            'announcement_enabled' => [
                'sometimes',
                'boolean',
            ],

            'announcement_text' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
            ],

            'announcement_colour' => $colour,

            /*
            |--------------------------------------------------------------------------
            | Header settings
            |--------------------------------------------------------------------------
            |
            | Header and footer layout preferences are stored
            | within the existing header_settings JSON column.
            |
            */

            'header_settings' => [
                'sometimes',
                'array:show_logo,show_business_name,show_search,show_account,show_cart,sticky_header,layout,footer_layout',
            ],

            'header_settings.show_logo' => [
                'sometimes',
                'boolean',
            ],

            'header_settings.show_business_name' => [
                'sometimes',
                'boolean',
            ],

            'header_settings.show_search' => [
                'sometimes',
                'boolean',
            ],

            'header_settings.show_account' => [
                'sometimes',
                'boolean',
            ],

            'header_settings.show_cart' => [
                'sometimes',
                'boolean',
            ],

            'header_settings.sticky_header' => [
                'sometimes',
                'boolean',
            ],

            /*
             * Header layout preference.
             */

            'header_settings.layout' => [
                'sometimes',
                'string',
                'in:double_row,single_row',
            ],

            /*
             * Footer layout preference.
             */

            'header_settings.footer_layout' => [
                'sometimes',
                'string',
                'in:standard,classic',
            ],

            /*
            |--------------------------------------------------------------------------
            | Navigation menu
            |--------------------------------------------------------------------------
            */

            'navigation_items' => [
                'sometimes',
                'array',
                'max:30',
            ],

            'navigation_items.*' => [
                'required',
                'array:label,type,enabled,category_id,category_slug,query',
            ],

            'navigation_items.*.label' => [
                'required',
                'string',
                'max:100',
            ],

            'navigation_items.*.type' => [
                'required',
                Rule::in([
                    'home',
                    'shop',
                    'category',
                ]),
            ],

            'navigation_items.*.enabled' => [
                'required',
                'boolean',
            ],

            'navigation_items.*.category_id' => [
                'nullable',
                'integer',
            ],

            'navigation_items.*.category_slug' => [
                'nullable',
                'string',
                'max:150',
            ],

            'navigation_items.*.query' => [
                'sometimes',
                'array:featured',
            ],

            'navigation_items.*.query.featured' => [
                'sometimes',
                'boolean',
            ],

            /*
            |--------------------------------------------------------------------------
            | Homepage hero
            |--------------------------------------------------------------------------
            */

            'hero_title' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'hero_subtitle' => [
                'sometimes',
                'nullable',
                'string',
                'max:2000',
            ],

            'hero_button_text' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],

            /*
            |--------------------------------------------------------------------------
            | Homepage sections
            |--------------------------------------------------------------------------
            */

            'homepage_sections' => [
                'sometimes',
                'array',
                'max:20',
            ],

            'homepage_sections.*' => [
                'required',
                'array:type,title,enabled,position',
            ],

            'homepage_sections.*.type' => [
                'required',
                'distinct',
                Rule::in([
                    'hero',
                    'categories',
                    'featured_products',
                    'about_business',
                ]),
            ],

            'homepage_sections.*.title' => [
                'nullable',
                'string',
                'max:150',
            ],

            'homepage_sections.*.enabled' => [
                'required',
                'boolean',
            ],

            'homepage_sections.*.position' => [
                'required',
                'integer',
                'min:1',
                'max:100',
                'distinct',
            ],

            /*
            |--------------------------------------------------------------------------
            | Footer settings
            |--------------------------------------------------------------------------
            */

            'footer_description' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],

            'footer_columns' => [
                'sometimes',
                'array',
                'max:10',
            ],

            'footer_columns.*' => [
                'required',
                'array:type,title,enabled',
            ],

            'footer_columns.*.type' => [
                'required',
                'distinct',
                Rule::in([
                    'business',
                    'navigation',
                    'contact',
                    'delivery',
                ]),
            ],

            'footer_columns.*.title' => [
                'required',
                'string',
                'max:100',
            ],

            'footer_columns.*.enabled' => [
                'required',
                'boolean',
            ],

            /*
            |--------------------------------------------------------------------------
            | Contact information
            |--------------------------------------------------------------------------
            */

            'contact_email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
            ],

            'contact_phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],

            'delivery_information' => [
                'sometimes',
                'nullable',
                'string',
                'max:2000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Social media
            |--------------------------------------------------------------------------
            */

            'social_links' => [
                'sometimes',
                'array:facebook,instagram,tiktok,x,linkedin,youtube,pinterest',
            ],

            'social_links.*' => [
                'nullable',
                'url:https',
                'max:500',
            ],

            /*
            |--------------------------------------------------------------------------
            | Search engine optimisation
            |--------------------------------------------------------------------------
            */

            'seo_title' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'seo_description' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Save or publish website settings securely
        |--------------------------------------------------------------------------
        */

        $theme = DB::transaction(function () use (
            $store,
            $data,
            $templates,
            $request,
            $publish
        ) {
            /*
             * Lock the store to prevent simultaneous
             * website updates from conflicting.
             */

            $lockedStore = Store::query()
                ->lockForUpdate()
                ->findOrFail($store->id);

            /*
             * Verify that the business remains active.
             */

            abort_unless(
                $lockedStore->status === 'active',
                403,
                'Store is not active.'
            );

            /*
             * Verify that the authenticated seller
             * still owns the business.
             */

            abort_unless(
                (int) $lockedStore->user_id ===
                    (int) $request->user()->id,
                403,
                'Store ownership has changed.'
            );

            /*
             * Initialise missing website settings.
             */

            $theme = $templates->initialise($lockedStore);

            /*
             * Retrieve the existing published settings.
             */

            $existing = $theme->only([
                'primary_colour',
                'secondary_colour',
                'background_colour',
                'text_colour',
                'header_colour',
                'footer_colour',
                'font_family',
                'announcement_enabled',
                'announcement_text',
                'announcement_colour',
                'header_settings',
                'navigation_items',
                'hero_title',
                'hero_subtitle',
                'hero_button_text',
                'homepage_sections',
                'footer_description',
                'footer_columns',
                'contact_email',
                'contact_phone',
                'delivery_information',
                'social_links',
                'seo_title',
                'seo_description',
            ]);

            /*
             * Retrieve existing unpublished changes.
             */

            $draft = $theme->draft_settings ?? [];

            /*
             * Merge published settings, existing drafts
             * and newly submitted website changes.
             */

            $settings = array_replace(
                $existing,
                $draft,
                $data
            );

            /*
             * Preserve existing header settings when
             * only individual options are submitted.
             *
             * This also preserves header and footer
             * layout preferences.
             */

            if (isset($data['header_settings'])) {
                $settings['header_settings'] = array_replace(
                    $draft['header_settings']
                        ?? $theme->header_settings
                        ?? [],
                    $data['header_settings']
                );
            }

            /*
             * Preserve social links not included
             * in the current update.
             */

            if (isset($data['social_links'])) {
                $settings['social_links'] = array_replace(
                    $draft['social_links']
                        ?? $theme->social_links
                        ?? [],
                    $data['social_links']
                );
            }

            /*
             * Validate and normalise navigation categories.
             */

            if (isset($settings['navigation_items'])) {
                $settings['navigation_items'] =
                    $this->normaliseNavigation(
                        $lockedStore,
                        $settings['navigation_items']
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | Publication management
            |--------------------------------------------------------------------------
            */

            if ($publish) {

                /*
                 * Apply the website configuration
                 * to the published website.
                 */

                $theme->fill($settings);

                /*
                 * Remove the draft after publication.
                 */

                $theme->draft_settings = null;

                /*
                 * Record the publication timestamp.
                 */

                $theme->published_at = now();

            } else {

                /*
                 * Save unpublished website settings
                 * without changing the live website.
                 */

                $theme->draft_settings = $settings;
            }

            $theme->save();

            return $theme->refresh();

        }, 3);

        /*
         * Return the current published configuration
         * and unpublished draft separately.
         */

        return response()->json([

            'message' => $publish
                ? 'Your website has been published successfully.'
                : 'Your website draft has been saved successfully.',

            'theme' => $theme->toArray(),

            'draft' => $theme->draft_settings,

            'has_draft' =>
                $theme->draft_settings !== null,

            'published_at' =>
                $theme->published_at,
        ]);
    }

    /**
     * Restore the original business website template.
     *
     * Template defaults are saved as a draft.
     * The live website remains unchanged until published.
     */
    public function reset(
        Request $request,
        WebsiteTemplateService $templates
    ): JsonResponse
    {
        $store = $this->currentStore($request);

        $theme = DB::transaction(function () use (
            $store,
            $templates,
            $request
        ) {
            /*
             * Lock the store during template reset.
             */

            $lockedStore = Store::query()
                ->lockForUpdate()
                ->findOrFail($store->id);

            /*
             * Verify store status and ownership.
             */

            abort_unless(
                $lockedStore->status === 'active' &&
                (int) $lockedStore->user_id ===
                    (int) $request->user()->id,
                403,
                'Store access denied.'
            );

            /*
             * Initialise missing website settings.
             */

            $theme = $templates->initialise($lockedStore);

            /*
             * Retrieve the original category-specific
             * website template configuration.
             */

            $defaults = $templates->defaults($lockedStore);

            /*
             * Only restore editable website settings.
             */

            $editableFields = [
                'primary_colour',
                'secondary_colour',
                'background_colour',
                'text_colour',
                'header_colour',
                'footer_colour',
                'font_family',
                'announcement_enabled',
                'announcement_text',
                'announcement_colour',
                'header_settings',
                'navigation_items',
                'hero_title',
                'hero_subtitle',
                'hero_button_text',
                'homepage_sections',
                'footer_description',
                'footer_columns',
                'contact_email',
                'contact_phone',
                'delivery_information',
                'social_links',
                'seo_title',
                'seo_description',
            ];

            /*
             * Restore template defaults to the draft.
             *
             * Published website settings are preserved.
             */

            $theme->draft_settings = array_intersect_key(
                $defaults,
                array_flip($editableFields)
            );

            $theme->save();

            return $theme->refresh();

        }, 3);

        return response()->json([

            'message' =>
                'Template defaults restored to your draft. Publish to apply the changes.',

            'theme' => $theme->toArray(),

            'draft' => $theme->draft_settings,

            'has_draft' => true,

            'published_at' => $theme->published_at,
        ]);
    }

    /**
     * Ensure navigation categories belong to the
     * seller's registered business category.
     */
    private function normaliseNavigation(
        Store $store,
        array $items
    ): array
    {
        $categories = Category::query()
            ->whereNotNull('business_category_id')
            ->where(
                'business_category_id',
                $store->business_category_id
            )
            ->get([
                'id',
                'name',
                'slug',
            ])
            ->keyBy('id');

        $normalised = [];

        foreach ($items as $index => $item) {

            $label = trim($item['label']);

            if ($label === '') {

                throw ValidationException::withMessages([
                    "navigation_items.{$index}.label" =>
                        'The navigation label cannot be empty.',
                ]);
            }

            $entry = [
                'label' => $label,
                'type' => $item['type'],
                'enabled' => (bool) $item['enabled'],
            ];

            if ($item['type'] === 'category') {

                $category = $categories->get(
                    $item['category_id'] ?? 0
                );

                if (!$category) {

                    throw ValidationException::withMessages([
                        "navigation_items.{$index}.category_id" =>
                            'The selected category is unavailable for this business.',
                    ]);
                }

                $entry['category_id'] = $category->id;

                $entry['category_slug'] = $category->slug;
            }

            if ($item['type'] === 'shop') {

                $entry['query'] = [];

                if (!empty($item['query']['featured'])) {

                    $entry['query']['featured'] = 1;
                }
            }

            $normalised[] = $entry;
        }

        return $normalised;
    }
}