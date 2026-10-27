<?php

namespace App\Modules\Attendance\Models;

use App\Modules\Students\Models\Student;
use Illuminate\Database\Eloquent\Model;

class StudentAttendance extends Model
{
    use \App\Core\Concerns\Auditable, \App\Core\Concerns\Lockable;

    protected $guarded = ['id'];
    // 'date' is stored as a plain Y-m-d string so lookups by date match on every database engine

    public function student() { return $this->belongsTo(Student::class); }
}
