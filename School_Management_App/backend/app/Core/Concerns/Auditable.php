<?php

namespace App\Core\Concerns;

use App\Modules\Audit\Models\AuditLog;

/** Writes who/when/old→new for every create, update and delete into audit_logs. */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn ($m) => AuditLog::record('create', $m, $m->auditable(array_keys($m->getAttributes()), fn ($k) => [null, $m->getAttribute($k)])));
        static::updated(function ($m) {
            $changes = $m->auditable(array_keys($m->getChanges()), fn ($k) => [$m->getRawOriginal($k), $m->getAttributes()[$k] ?? null]);
            if ($changes) AuditLog::record('update', $m, $changes);
        });
        static::deleted(fn ($m) => AuditLog::record('delete', $m, $m->auditable(array_keys($m->getRawOriginal()), fn ($k) => [$m->getRawOriginal($k), null])));
    }

    protected function auditable(array $keys, callable $pair): array
    {
        $skip = ['created_at', 'updated_at', 'remember_token', 'id', 'last_login_at'];
        $out = [];
        foreach ($keys as $k) {
            if (in_array($k, $skip, true)) continue;
            [$old, $new] = $pair($k);
            if ($k === 'password') { $out[$k] = ['old' => $old === null ? null : '••••', 'new' => $new === null ? null : '(changed)']; continue; }
            if (is_array($old) || is_object($old)) $old = json_encode($old);
            if (is_array($new) || is_object($new)) $new = json_encode($new);
            if ((string) $old === (string) $new) continue;
            $out[$k] = ['old' => $old, 'new' => $new];
        }
        return $out;
    }
}
