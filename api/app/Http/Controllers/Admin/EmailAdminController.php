<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmailAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => [
                'nullable',
                Rule::in([
                    'pending',
                    'queued',
                    'processing',
                    'retrying',
                    'sent',
                    'delivered',
                    'failed',
                    'logged',
                ]),
            ],
            'email_type' => [
                'nullable',
                'string',
                'max:100',
            ],
            'q' => [
                'nullable',
                'string',
                'max:255',
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $logs = EmailLog::query()
            ->select([
                'id',
                'user_id',
                'order_id',
                'store_id',
                'recipient_email',
                'recipient_name',
                'email_type',
                'subject',
                'status',
                'attempts',
                'provider',
                'queued_at',
                'sent_at',
                'delivered_at',
                'failed_at',
                'created_at',
                'updated_at',
            ])
            ->when(
                $data['status'] ?? null,
                fn ($query, $status) =>
                    $query->where('status', $status)
            )
            ->when(
                $data['email_type'] ?? null,
                fn ($query, $type) =>
                    $query->where('email_type', $type)
            )
            ->when(
                $data['q'] ?? null,
                function ($query, $term) {
                    $query->where(function ($search) use ($term) {
                        $search
                            ->where(
                                'recipient_email',
                                'like',
                                "%{$term}%"
                            )
                            ->orWhere(
                                'recipient_name',
                                'like',
                                "%{$term}%"
                            )
                            ->orWhere(
                                'subject',
                                'like',
                                "%{$term}%"
                            );
                    });
                }
            )
            ->latest('id')
            ->paginate($data['per_page'] ?? 30);

        return response()->json($logs);
    }

    public function statistics(): JsonResponse
    {
        $counts = EmailLog::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'total' => (int) $counts->sum(),
            'pending' => (int) ($counts['pending'] ?? 0),
            'queued' => (int) ($counts['queued'] ?? 0),
            'processing' => (int) ($counts['processing'] ?? 0),
            'retrying' => (int) ($counts['retrying'] ?? 0),
            'sent' => (int) ($counts['sent'] ?? 0),
            'delivered' => (int) ($counts['delivered'] ?? 0),
            'failed' => (int) ($counts['failed'] ?? 0),
            'logged' => (int) ($counts['logged'] ?? 0),
            'email_types' => EmailLog::query()
                ->distinct()
                ->orderBy('email_type')
                ->pluck('email_type'),
        ]);
    }
}
