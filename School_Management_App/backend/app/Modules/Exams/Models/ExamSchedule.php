<?php

namespace App\Modules\Exams\Models;

use App\Modules\Academics\Models\Subject;
use Illuminate\Database\Eloquent\Model;

class ExamSchedule extends Model
{
    protected $fillable = ['exam_id', 'class_id', 'subject_id', 'exam_date', 'start_time', 'end_time', 'max_marks', 'pass_marks'];
    protected $casts = ['exam_date' => 'date:Y-m-d'];
    protected $appends = ['subject_name'];
    protected $hidden = ['subject'];

    public function subject() { return $this->belongsTo(Subject::class); }
    public function getSubjectNameAttribute() { return $this->subject?->name; }
}
