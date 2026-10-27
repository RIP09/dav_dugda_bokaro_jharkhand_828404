<?php

namespace App\Modules\Exams\Models;

use App\Modules\Academics\Models\Subject;
use App\Modules\Students\Models\Student;
use Illuminate\Database\Eloquent\Model;

class Mark extends Model
{
    use \App\Core\Concerns\Auditable, \App\Core\Concerns\Lockable;

    protected $guarded = ['id'];
    protected $casts = ['marks_obtained' => 'float', 'point' => 'float', 'is_absent' => 'boolean'];

    public function subject() { return $this->belongsTo(Subject::class); }
    public function student() { return $this->belongsTo(Student::class); }
    public function exam() { return $this->belongsTo(Exam::class); }

    public function lockReason(): ?string
    {
        return Exam::whereKey($this->exam_id)->value('results_locked') ? 'Results of this exam are locked (final). Marks can no longer be changed.' : null;
    }
}
