<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mark extends Model
{
    protected $fillable = ['exam_id', 'student_id', 'subject_id', 'class_id', 'section_id', 'marks_obtained', 'max_marks', 'grade', 'point', 'is_absent', 'remark', 'entered_by'];
    protected $casts = ['marks_obtained' => 'float', 'point' => 'float', 'is_absent' => 'boolean'];

    public function subject() { return $this->belongsTo(Subject::class); }
    public function student() { return $this->belongsTo(Student::class); }
    public function exam() { return $this->belongsTo(Exam::class); }
}
