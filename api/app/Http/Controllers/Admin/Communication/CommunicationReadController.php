<?php

namespace App\Http\Controllers\Admin\Communication;

use App\Http\Controllers\Controller;

use App\Models\PlatformCommunication;
use App\Models\PlatformEmailTemplate;
use App\Models\PlatformAnnouncement;
use App\Models\PlatformNotification;
use App\Models\PlatformCommunicationRecipient;
use App\Models\PlatformAutomationRule;
use App\Models\PlatformCommunicationPreference;

use App\Services\Communication\CommunicationPermissionService;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommunicationReadController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Communications
    |--------------------------------------------------------------------------
    */

    public function messages(
        Request $request
    ): JsonResponse {

        $validated = $request->validate([

            'status' => [
                'sometimes',
                Rule::in(PlatformCommunication::STATUSES),
            ],

            'channel' => [
                'sometimes',
                Rule::in(PlatformCommunication::CHANNELS),
            ],

            'type' => [
                'sometimes',
                Rule::in(PlatformCommunication::TYPES),
            ],

            'search' => [
                'nullable',
                'string',
                'max:150',
            ],
        ]);

        $query = PlatformCommunication::query()
            ->select([
                'id',
                'reference',
                'name',
                'channel',
                'type',
                'subject',
                'audience_type',
                'status',
                'total_recipients',
                'sent_count',
                'delivered_count',
                'failed_count',
                'skipped_count',
                'scheduled_at',
                'completed_at',
                'created_at',
                'updated_at',
            ]);

        foreach (['status', 'channel', 'type'] as $field) {

            if (isset($validated[$field])) {

                $query->where(
                    $field,
                    $validated[$field]
                );
            }
        }

        if (!empty($validated['search'])) {

            $search = $validated['search'];

            $query->where(function ($query) use ($search) {

                $query->where(
                    'name',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'subject',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'reference',
                    'like',
                    "%{$search}%"
                );
            });
        }

        return $this->paginated(
            $request,
            $query
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Email Templates
    |--------------------------------------------------------------------------
    */

    public function templates(
        Request $request
    ): JsonResponse {

        $validated = $request->validate([

            'category' => [
                'sometimes',
                Rule::in(PlatformEmailTemplate::CATEGORIES),
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            'search' => [
                'nullable',
                'string',
                'max:150',
            ],
        ]);

        $query = PlatformEmailTemplate::query()
            ->select([
                'id',
                'name',
                'slug',
                'category',
                'subject',
                'variables',
                'language',
                'is_active',
                'is_system',
                'created_at',
                'updated_at',
            ]);

        if (isset($validated['category'])) {

            $query->where(
                'category',
                $validated['category']
            );
        }

        if (array_key_exists('is_active', $validated)) {

            $query->where(
                'is_active',
                $request->boolean('is_active')
            );
        }

        if (!empty($validated['search'])) {

            $search = $validated['search'];

            $query->where(function ($query) use ($search) {

                $query->where(
                    'name',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'subject',
                    'like',
                    "%{$search}%"
                );
            });
        }

        return $this->paginated(
            $request,
            $query
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Announcements
    |--------------------------------------------------------------------------
    */

    public function announcements(
        Request $request
    ): JsonResponse {

        $validated = $request->validate([

            'status' => [
                'sometimes',
                Rule::in(PlatformAnnouncement::STATUSES),
            ],

            'type' => [
                'sometimes',
                Rule::in(PlatformAnnouncement::TYPES),
            ],

            'search' => [
                'nullable',
                'string',
                'max:150',
            ],
        ]);

        $query = PlatformAnnouncement::query()
            ->select([
                'id',
                'title',
                'content',
                'type',
                'audience_type',
                'status',
                'is_pinned',
                'is_dismissible',
                'action_label',
                'action_url',
                'starts_at',
                'ends_at',
                'published_at',
                'created_at',
                'updated_at',
            ]);

        foreach (['status', 'type'] as $field) {

            if (isset($validated[$field])) {

                $query->where(
                    $field,
                    $validated[$field]
                );
            }
        }

        if (!empty($validated['search'])) {

            $query->where(
                'title',
                'like',
                '%' . $validated['search'] . '%'
            );
        }

        return $this->paginated(
            $request,
            $query
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    public function notifications(
        Request $request
    ): JsonResponse {

        $validated = $request->validate([

            'type' => [
                'sometimes',
                Rule::in(PlatformNotification::TYPES),
            ],

            'unread' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $query = PlatformNotification::query()
            ->select([
                'id',
                'user_id',
                'store_id',
                'platform_communication_id',
                'type',
                'title',
                'read_at',
                'dismissed_at',
                'created_at',
            ]);

        if (isset($validated['type'])) {

            $query->where(
                'type',
                $validated['type']
            );
        }

        if ($request->boolean('unread')) {

            $query->unread();
        }

        return $this->paginated(
            $request,
            $query
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Communication Recipients
    |--------------------------------------------------------------------------
    */

    public function recipients(
        Request $request
    ): JsonResponse {

        $validated = $request->validate([

            'communication_id' => [
                'sometimes',
                'integer',
                'min:1',
            ],

            'status' => [
                'sometimes',
                Rule::in(
                    PlatformCommunicationRecipient::STATUSES
                ),
            ],
        ]);

        $query = PlatformCommunicationRecipient::query()
            ->select([
                'id',
                'platform_communication_id',
                'user_id',
                'store_id',
                'recipient_name',
                'status',
                'attempts',
                'sent_at',
                'delivered_at',
                'failed_at',
                'created_at',
            ]);

        if (isset($validated['communication_id'])) {

            $query->where(
                'platform_communication_id',
                $validated['communication_id']
            );
        }

        if (isset($validated['status'])) {

            $query->where(
                'status',
                $validated['status']
            );
        }

        return $this->paginated(
            $request,
            $query
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Delivery Logs
    |--------------------------------------------------------------------------
    */

    public function delivery(
        Request $request
    ): JsonResponse {

        $validated = $request->validate([

            'status' => [
                'sometimes',
                Rule::in(
                    PlatformCommunicationRecipient::STATUSES
                ),
            ],

            'communication_id' => [
                'sometimes',
                'integer',
                'min:1',
            ],
        ]);

        $query = PlatformCommunicationRecipient::query()
            ->select([
                'id',
                'platform_communication_id',
                'status',
                'attempts',
                'queued_at',
                'sent_at',
                'delivered_at',
                'failed_at',
                'skipped_at',
                'created_at',
                'updated_at',
            ])
            ->with([
                'communication:id,reference,name,channel',
            ]);

        if (isset($validated['status'])) {

            $query->where(
                'status',
                $validated['status']
            );
        }

        if (isset($validated['communication_id'])) {

            $query->where(
                'platform_communication_id',
                $validated['communication_id']
            );
        }

        return $this->paginated(
            $request,
            $query
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Automation Rules
    |--------------------------------------------------------------------------
    */

    public function automation(
        Request $request
    ): JsonResponse {

        $validated = $request->validate([

            'event' => [
                'sometimes',
                Rule::in(PlatformAutomationRule::EVENTS),
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            'channel' => [
                'sometimes',
                Rule::in(PlatformAutomationRule::CHANNELS),
            ],
        ]);

        $query = PlatformAutomationRule::query()
            ->select([
                'id',
                'platform_email_template_id',
                'name',
                'slug',
                'event',
                'channel',
                'delay_minutes',
                'is_active',
                'last_triggered_at',
                'created_at',
                'updated_at',
            ])
            ->with([
                'template:id,name,slug',
            ]);

        foreach (['event', 'channel'] as $field) {

            if (isset($validated[$field])) {

                $query->where(
                    $field,
                    $validated[$field]
                );
            }
        }

        if (array_key_exists('is_active', $validated)) {

            $query->where(
                'is_active',
                $request->boolean('is_active')
            );
        }

        return $this->paginated(
            $request,
            $query
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Communication Preferences
    |--------------------------------------------------------------------------
    */

    public function preferences(
        Request $request
    ): JsonResponse {

        $validated = $request->validate([

            'marketing_email' => [
                'sometimes',
                'boolean',
            ],

            'marketing_sms' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $query = PlatformCommunicationPreference::query()
            ->select([
                'id',
                'user_id',
                'marketing_email',
                'marketing_sms',
                'product_updates',
                'in_app_notifications',
                'consent_version',
                'email_consented_at',
                'sms_consented_at',
                'email_unsubscribed_at',
                'sms_unsubscribed_at',
                'updated_at',
            ]);

        foreach (
            ['marketing_email', 'marketing_sms']
            as $field
        ) {

            if (array_key_exists($field, $validated)) {

                $query->where(
                    $field,
                    $request->boolean($field)
                );
            }
        }

        return $this->paginated(
            $request,
            $query
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Communication Settings
    |--------------------------------------------------------------------------
    */

    public function settings(): JsonResponse
    {
        return response()->json([

            'success' => true,

            'data' => [

                'enabled' =>
                    config('communication.enabled'),

                'channels' =>
                    config('communication.channels'),

                'types' =>
                    config('communication.types'),

                'audiences' =>
                    config('communication.audiences'),

                'pagination' =>
                    config('communication.pagination'),

                'consent' =>
                    config('communication.consent'),

                'template_categories' =>
                    PlatformEmailTemplate::CATEGORIES,

                'communication_statuses' =>
                    PlatformCommunication::STATUSES,

                'announcement_types' =>
                    PlatformAnnouncement::TYPES,

                'announcement_statuses' =>
                    PlatformAnnouncement::STATUSES,

                'automation_events' =>
                    PlatformAutomationRule::EVENTS,

                'recipient_statuses' =>
                    PlatformCommunicationRecipient::STATUSES,

            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Current Administrator Permissions
    |--------------------------------------------------------------------------
    */

    public function permissions(
        Request $request,
        CommunicationPermissionService $permissions
    ): JsonResponse {

        return response()->json([

            'success' => true,

            'data' => [

                'permissions' =>
                    $permissions->permissionsFor(
                        $request->user()
                    ),

            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Standard Paginated Response
    |--------------------------------------------------------------------------
    */

    private function paginated(
        Request $request,
        Builder $query
    ): JsonResponse {

        $validated = $request->validate([

            'page' => [
                'sometimes',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:' . config(
                    'communication.pagination.maximum',
                    100
                ),
            ],
        ]);

        $perPage = (int) (
            $validated['per_page']
            ?? config(
                'communication.pagination.default',
                20
            )
        );

        $result = $query
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([

            'success' => true,

            'data' => $result->items(),

            'meta' => [

                'current_page' =>
                    $result->currentPage(),

                'last_page' =>
                    $result->lastPage(),

                'per_page' =>
                    $result->perPage(),

                'total' =>
                    $result->total(),

                'from' =>
                    $result->firstItem(),

                'to' =>
                    $result->lastItem(),

            ],

        ]);
    }
}