<?php

namespace App\Modules\Homework\Models;

use App\Modules\Students\Models\Student;
use Illuminate\Database\Eloquent\Model;

class HomeworkSubmission extends Model
{
    protected $fillable = ['homework_id', 'student_id', 'content', 'submitted_at', 'status', 'grade', 'feedback'];
    protected $casts = ['submitted_at' => 'datetime'];
    protected $appends = ['student_name'];
    protected $hidden = ['student'];

    public function student() { return $this->belongsTo(Student::class); }
    public function homework() { return $this->belongsTo(Homework::class); }
    public function getStudentNameAttribute() { return $this->student?->name; }
}
