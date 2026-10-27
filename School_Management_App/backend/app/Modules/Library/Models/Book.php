<?php

namespace App\Modules\Library\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use \App\Core\Concerns\Auditable, \App\Core\Concerns\Lockable;

    protected $guarded = ['id'];

    public function issues() { return $this->hasMany(BookIssue::class); }
}
