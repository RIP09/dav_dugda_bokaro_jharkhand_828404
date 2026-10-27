<?php

namespace App\Modules\Communication\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\Lockable;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppNotification extends Model
{
    use Auditable, Lockable;

    protected $table = 'app_notifications';

    protected $guarded = ['id'];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }

    /** Create an in-app notification for each user id (free alternative to SMS/push). */
    public static function send(iterable $userIds, string $title, ?string $body = null, ?string $module = null, ?int $recordId = null): int
    {
        $now = now(); $rows = [];
        foreach (collect($userIds)->filter()->unique() as $uid) {
            $rows[] = ['user_id' => $uid, 'title' => mb_substr($title, 0, 150), 'body' => $body ? mb_substr($body, 0, 500) : null,
                'module' => $module, 'record_id' => $recordId, 'created_at' => $now, 'updated_at' => $now];
        }
        foreach (array_chunk($rows, 200) as $chunk) static::insert($chunk);
        return count($rows);
    }
}
