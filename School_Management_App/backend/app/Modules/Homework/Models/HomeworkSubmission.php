<?php

namespace App\Modules\Homework\Models;

use App\Modules\Students\Models\Student;
use Illuminate\Database\Eloquent\Model;

class HomeworkSubmission extends Model
{
    use \App\Core\Concerns\Auditable, \App\Core\Concerns\Lockable;

    protected $guarded = ['id'];
    protected $casts = ['submitted_at' => 'datetime'];
    protected $appends = ['student_name'];
    protected $hidden = ['student'];

    public function student() { return $this->belongsTo(Student::class); }
    public function homework() { return $this->belongsTo(Homework::class); }
    public function getStudentNameAttribute() { return $this->student?->name; }
}
