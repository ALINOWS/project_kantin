<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogService
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function record(
        Request $request,
        string $action,
        string $entityType,
        ?int $entityId = null,
        array $details = [],
        ?User $user = null,
    ): void {
        AuditLog::query()->create([
            'user_id' => $user?->id ?? $request->user()?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => $details ?: null,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500) ?: null,
        ]);
    }
}
