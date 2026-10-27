<?php

namespace App\Modules\Academics\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = ['class_id', 'name', 'code', 'type', 'teacher_id'];

    public function schoolClass() { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function teacher() { return $this->belongsTo(User::class, 'teacher_id')->select('id', 'name', 'phone', 'role'); }
}
