<?php

namespace App\Modules\Academics\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use \App\Core\Concerns\Auditable, \App\Core\Concerns\Lockable;

    protected $guarded = ['id'];

    public function schoolClass() { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function teacher() { return $this->belongsTo(User::class, 'teacher_id')->select('id', 'name', 'phone', 'role'); }
}
