<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AuditService
{
    public function record(
        string $action,
        ?int $storeId = null,
        ?int $userId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        array $metadata = []
    ): void {
        DB::table('audit_logs')->insert([
            'store_id' => $storeId,
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => json_encode(
                $metadata,
                JSON_THROW_ON_ERROR
            ),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
