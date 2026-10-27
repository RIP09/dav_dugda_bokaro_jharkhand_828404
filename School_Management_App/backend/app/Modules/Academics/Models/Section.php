<?php

namespace App\Modules\Academics\Models;

use App\Models\User;
use App\Modules\Students\Models\Student;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = ['class_id', 'name', 'capacity', 'teacher_id', 'room'];

    public function schoolClass() { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function teacher() { return $this->belongsTo(User::class, 'teacher_id')->select('id', 'name', 'phone', 'role'); }
    public function students() { return $this->hasMany(Student::class); }
}
