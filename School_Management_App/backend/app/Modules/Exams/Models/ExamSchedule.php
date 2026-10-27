<?php

namespace App\Modules\Exams\Models;

use App\Modules\Academics\Models\Subject;
use Illuminate\Database\Eloquent\Model;

class ExamSchedule extends Model
{
    use \App\Core\Concerns\Auditable, \App\Core\Concerns\Lockable;

    protected $guarded = ['id'];
    protected $casts = ['exam_date' => 'date:Y-m-d'];
    protected $appends = ['subject_name'];
    protected $hidden = ['subject'];

    public function subject() { return $this->belongsTo(Subject::class); }
    public function getSubjectNameAttribute() { return $this->subject?->name; }
}
