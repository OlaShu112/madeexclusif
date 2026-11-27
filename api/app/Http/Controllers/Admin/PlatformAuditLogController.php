<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Finance\FinancePermissionService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlatformAuditLogController extends Controller
{
    public function __construct(
        private FinancePermissionService $permissions
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Audit Log Listing
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        abort_unless(
            $request->user()?->isAdmin(),
            403
        );

        $filters = $request->validate([

            'q' => 'nullable|string|max:255',

            'action' => 'nullable|string|max:100',

            'store_id' =>
                'nullable|integer|exists:stores,id',

            'from' => 'nullable|date_format:Y-m-d',

            'to' =>
                'nullable|date_format:Y-m-d|after_or_equal:from',

            'per_page' =>
                'nullable|integer|min:1|max:100',

            'module' =>
                'nullable|in:finance,platform',
        ]);

        $base = $this->visibleAuditQuery($request);

        $query = (clone $base)

            ->leftJoin(
                'users',
                'users.id',
                '=',
                'audit_logs.user_id'
            )

            ->leftJoin(
                'stores',
                'stores.id',
                '=',
                'audit_logs.store_id'
            )

            ->select([

                'audit_logs.id',

                'audit_logs.action',

                'audit_logs.entity_type',

                'audit_logs.entity_id',

                'audit_logs.created_at',

                'audit_logs.user_id',

                'audit_logs.store_id',

                'users.name as actor_name',

                'users.role as actor_role',

                'stores.name as business_name',
            ]);

        if (!empty($filters['q'])) {

            $search = '%' . $filters['q'] . '%';

            $query->where(
                function ($query) use ($search) {

                    $query->where(
                        'audit_logs.action',
                        'like',
                        $search
                    )

                    ->orWhere(
                        'audit_logs.entity_type',
                        'like',
                        $search
                    )

                    ->orWhere(
                        'users.name',
                        'like',
                        $search
                    )

                    ->orWhere(
                        'stores.name',
                        'like',
                        $search
                    );
                }
            );
        }

        if (!empty($filters['action'])) {

            $query->where(

                'audit_logs.action',

                $filters['action']
            );
        }

        if (!empty($filters['store_id'])) {

            $query->where(

                'audit_logs.store_id',

                $filters['store_id']
            );
        }

        if (!empty($filters['from'])) {

            $query->whereDate(

                'audit_logs.created_at',

                '>=',

                $filters['from']
            );
        }

        if (!empty($filters['to'])) {

            $query->whereDate(

                'audit_logs.created_at',

                '<=',

                $filters['to']
            );
        }

        if (($filters['module'] ?? null) === 'finance') {

            abort_unless(
                $this->canViewFinance($request),
                403
            );

            $query->where(

                'audit_logs.action',

                'like',

                'finance.%'
            );
        }

        if (($filters['module'] ?? null) === 'platform') {

            $query->where(

                'audit_logs.action',

                'not like',

                'finance.%'
            );
        }

        return response()->json([

            'statistics' => [

                'total_events' =>
                    (clone $base)->count(),

                'events_today' =>
                    (clone $base)
                        ->whereDate(
                            'audit_logs.created_at',
                            today()
                        )
                        ->count(),

                'administrator_events' =>
                    (clone $base)
                        ->join(

                            'users',

                            'users.id',

                            '=',

                            'audit_logs.user_id'
                        )
                        ->where(
                            'users.role',
                            'admin'
                        )
                        ->count(),

                'business_events' =>
                    (clone $base)
                        ->whereNotNull(
                            'audit_logs.store_id'
                        )
                        ->count(),

                'finance_events' =>
                    $this->canViewFinance($request)

                        ? (clone $base)
                            ->where(
                                'audit_logs.action',
                                'like',
                                'finance.%'
                            )
                            ->count()

                        : 0,
            ],

            'actions' =>
                (clone $base)
                    ->distinct()
                    ->orderBy('action')
                    ->pluck('action'),

            'stores' =>
                DB::table('stores')
                    ->select('id', 'name')
                    ->orderBy('name')
                    ->get(),

            'logs' =>
                $query
                    ->orderByDesc('audit_logs.id')
                    ->paginate(
                        $filters['per_page'] ?? 20
                    ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Individual Audit Event
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        int $log
    ) {

        abort_unless(
            $request->user()?->isAdmin(),
            403
        );

        $record = $this->visibleAuditQuery($request)

            ->leftJoin(
                'users',
                'users.id',
                '=',
                'audit_logs.user_id'
            )

            ->leftJoin(
                'stores',
                'stores.id',
                '=',
                'audit_logs.store_id'
            )

            ->where(
                'audit_logs.id',
                $log
            )

            ->select([

                'audit_logs.id',

                'audit_logs.action',

                'audit_logs.entity_type',

                'audit_logs.entity_id',

                'audit_logs.created_at',

                'audit_logs.metadata',

                'audit_logs.user_id',

                'audit_logs.store_id',

                'users.name as actor_name',

                'users.role as actor_role',

                'stores.name as business_name',
            ])

            ->first();

        abort_unless(

            $record,

            404,

            'Audit event not found.'
        );

        $metadata = json_decode(

            $record->metadata ?? '{}',

            true
        );

        if (!is_array($metadata)) {
            $metadata = [];
        }

        $allowed = [

            'customer_id',

            'previous_status',

            'new_status',

            'reason',

            'status',

            'page_id',

            'store_id',

            'slug',
        ];

        $isFinance = str_starts_with(
            $record->action,
            'finance.'
        );

        if ($isFinance) {

            $allowed = array_merge(
                $allowed,
                [

                    'request_id',

                    'outcome',

                    'http_status',

                    'method',

                    'livemode',

                    'permission',

                    'source',

                    'ip_address',
                ]
            );
        }

        $record->metadata = [];

        foreach ($allowed as $key) {

            $value = $metadata[$key] ?? null;

            if (is_scalar($value)) {

                $record->metadata[$key] = $value;
            }
        }

        if ($isFinance) {

            foreach (['before', 'after'] as $key) {

                if (
                    isset($metadata[$key])
                    && is_array($metadata[$key])
                ) {

                    $record->metadata[$key] =
                        $this->safeFinancialSnapshot(
                            $metadata[$key]
                        );
                }
            }
        }

        return response()->json([

            'log' => $record,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Finance Audit Access
    |--------------------------------------------------------------------------
    */

    private function canViewFinance(
        Request $request
    ): bool {

        return $request->user() !== null

            && $this->permissions->has(

                $request->user(),

                'finance.audit.view'
            );
    }

    private function visibleAuditQuery(
        Request $request
    ) {

        $query = DB::table('audit_logs');

        if (!$this->canViewFinance($request)) {

            $query->where(

                'audit_logs.action',

                'not like',

                'finance.%'
            );
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Reviewed Financial Metadata
    |--------------------------------------------------------------------------
    */

    private function safeFinancialSnapshot(
        array $snapshot,
        int $depth = 0
    ): array {

        if ($depth > 2) {
            return [];
        }

        $allowed = [

            'id',

            'store_id',

            'store_subscription_id',

            'platform_invoice_id',

            'platform_expense_category_id',

            'invoice_number',

            'credit_note_number',

            'reference',

            'type',

            'status',

            'currency',

            'livemode',

            'subtotal_minor',

            'discount_minor',

            'tax_minor',

            'total_minor',

            'amount_minor',

            'amount_paid_minor',

            'amount_due_minor',

            'quantity',

            'unit_price_minor',

            'tax_percentage',

            'created_by',

            'approved_by',

            'payment_method',

            'is_recurring',

            'recurring_interval',

            'expense_date',

            'issued_at',

            'due_at',

            'paid_at',

            'approved_at',

            'voided_at',
        ];

        $result = [];

        foreach ($allowed as $key) {

            if (
                array_key_exists($key, $snapshot)

                && (
                    is_scalar($snapshot[$key])
                    || $snapshot[$key] === null
                )
            ) {

                $result[$key] = $snapshot[$key];
            }
        }

        if (
            isset($snapshot['items'])
            && is_array($snapshot['items'])
        ) {

            $result['items'] = [];

            foreach ($snapshot['items'] as $item) {

                if (is_array($item)) {

                    $result['items'][] =
                        $this->safeFinancialSnapshot(
                            $item,
                            $depth + 1
                        );
                }
            }
        }

        if (
            isset($snapshot['invoice'])
            && is_array($snapshot['invoice'])
        ) {

            $result['invoice'] =
                $this->safeFinancialSnapshot(

                    $snapshot['invoice'],

                    $depth + 1
                );
        }

        return $result;
    }
}