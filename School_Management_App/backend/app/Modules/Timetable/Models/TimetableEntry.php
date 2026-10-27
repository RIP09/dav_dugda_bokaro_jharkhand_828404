<?php

namespace App\Modules\Timetable\Models;

use App\Models\User;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Academics\Models\Subject;
use Illuminate\Database\Eloquent\Model;

class TimetableEntry extends Model
{
    use \App\Core\Concerns\Auditable, \App\Core\Concerns\Lockable;

    protected $guarded = ['id'];
    protected $appends = ['subject_name', 'teacher_name', 'class_label', 'day_name'];
    protected $hidden = ['subject', 'teacher', 'schoolClass', 'section'];

    public const DAYS = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
    public function subject() { return $this->belongsTo(Subject::class); }
    public function teacher() { return $this->belongsTo(User::class, 'teacher_id'); }
    public function schoolClass() { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function section() { return $this->belongsTo(Section::class); }
    public function getSubjectNameAttribute() { return $this->subject?->name; }
    public function getTeacherNameAttribute() { return $this->teacher?->name; }
    public function getDayNameAttribute() { return self::DAYS[$this->day] ?? null; }
    public function getClassLabelAttribute() { return trim(($this->schoolClass?->name ?? '').' - '.($this->section?->name ?? ''), ' -'); }
}
