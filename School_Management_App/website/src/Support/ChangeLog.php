<?php

namespace Website\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** A simple, append-only "who changed what" log for the website CMS. */
class ChangeLog
{
    const SKIP = ['updated_at', 'created_at', 'updated_by', 'password', 'remember_token'];

    public static function record(string $action, string $module, ?Model $model = null, array $changes = [], ?string $title = null): void
    {
        $admin = Auth::guard('webadmin')->user();
        $clean = [];
        foreach ($changes as $field => [$old, $new]) {
            if (in_array($field, self::SKIP, true)) {
                continue;
            }
            $clean[$field] = [self::short($old), self::short($new)];
        }
        DB::table('site_change_log')->insert([
            'admin_id' => $admin?->id,
            'admin_name' => $admin?->name,
            'action' => $action,
            'module' => $module,
            'record_id' => $model?->getKey(),
            'record_title' => Str::limit($title ?? ($model ? (string) ($model->title ?? $model->name ?? $model->label ?? $model->key ?? '#'.$model->getKey()) : ''), 240),
            'changes' => $clean ? json_encode($clean, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => now(),
        ]);
    }

    /** Field-by-field differences between the original and the saved model. */
    public static function diff(Model $model): array
    {
        $out = [];
        foreach ($model->getChanges() as $field => $new) {
            $out[$field] = [$model->getOriginal($field) ?? null, $new];
        }

        return $out;
    }

    protected static function short($v): ?string
    {
        if ($v === null) {
            return null;
        }
        if (is_bool($v)) {
            return $v ? 'Yes' : 'No';
        }
        if ($v instanceof \DateTimeInterface) {
            return $v->format('Y-m-d H:i');
        }
        if (is_array($v)) {
            $v = json_encode($v);
        }

        return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $v))), 160);
    }
}
