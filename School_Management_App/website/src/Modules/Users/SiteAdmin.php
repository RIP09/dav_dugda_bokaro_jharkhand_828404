<?php

namespace Website\Modules\Users;

use Illuminate\Foundation\Auth\User as Authenticatable;

/** A website-CMS user. Completely separate from the school-management `users` table. */
class SiteAdmin extends Authenticatable
{
    protected $table = 'site_admins';
    protected $guarded = ['id'];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = ['is_active' => 'boolean', 'last_login_at' => 'datetime', 'password' => 'hashed'];

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }
}
