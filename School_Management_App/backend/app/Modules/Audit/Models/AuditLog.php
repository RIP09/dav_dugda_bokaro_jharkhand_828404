<?php

namespace App\Modules\Audit\Models;

use App\Core\Exceptions\RecordLockedException;
use App\Core\Modules\ModuleRegistry;
use Illuminate\Database\Eloquent\Model;

/** The audit trail itself is permanent: it can never be edited or deleted. */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = ['changes' => 'array', 'created_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new RecordLockedException('The audit log is permanent.'));
        static::deleting(fn () => throw new RecordLockedException('The audit log is permanent.'));
    }

    public static bool $enabled = true;

    public function lockReason(): ?string { return 'The audit log is permanent and can never be edited.'; }
    public function originalLockReason(): ?string { return $this->lockReason(); }

    public static function record(string $action, Model $m, array $changes = []): void
    {
        if (! static::$enabled) return;
        try {
            $user = request()?->user();
        } catch (\Throwable) { $user = null; }
        $module = ModuleRegistry::keyForModel($m);
        static::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'action' => $action,
            'module' => $module ?? $m->getTable(),
            'table_name' => $m->getTable(),
            'record_id' => $m->getKey(),
            'record_title' => mb_substr((string) ModuleRegistry::titleFor($module, $m), 0, 190),
            'changes' => $changes,
            'ip' => request()?->ip(),
        ]);
    }
}
