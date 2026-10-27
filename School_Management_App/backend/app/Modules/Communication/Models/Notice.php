<?php

namespace App\Modules\Communication\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    use \App\Core\Concerns\Auditable, \App\Core\Concerns\Lockable;

    protected $guarded = ['id'];
    protected $casts = ['publish_date' => 'date:Y-m-d', 'is_pinned' => 'boolean'];
    protected $appends = ['author_name'];
    protected $hidden = ['author'];

    public function author() { return $this->belongsTo(User::class, 'posted_by'); }
    public function getAuthorNameAttribute() { return $this->author?->name; }

    /** Notices visible to a given user. */
    public function scopeVisibleTo($q, User $user)
    {
        if ($user->isAdmin()) return $q;
        $aud = match (true) {
            $user->isStaff() => ['all', 'staff'],
            $user->role === User::PARENT => ['all', 'parents'],
            default => ['all', 'students'],
        };
        return $q->whereIn('audience', $aud);
    }
}
