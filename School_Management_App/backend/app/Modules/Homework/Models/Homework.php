<?php

namespace App\Modules\Homework\Models;

use App\Models\User;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Academics\Models\Subject;
use Illuminate\Database\Eloquent\Model;

class Homework extends Model
{
    use \App\Core\Concerns\Auditable, \App\Core\Concerns\Lockable;

    protected $table = 'homework';
    protected $guarded = ['id'];
    protected $casts = ['assigned_on' => 'date:Y-m-d', 'due_date' => 'date:Y-m-d'];
    protected $appends = ['subject_name', 'teacher_name', 'class_label'];
    protected $hidden = ['subject', 'teacher', 'schoolClass', 'section'];

    public function subject() { return $this->belongsTo(Subject::class); }
    public function teacher() { return $this->belongsTo(User::class, 'teacher_id'); }
    public function schoolClass() { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function section() { return $this->belongsTo(Section::class); }
    public function submissions() { return $this->hasMany(HomeworkSubmission::class); }
    public function getSubjectNameAttribute() { return $this->subject?->name; }
    public function getTeacherNameAttribute() { return $this->teacher?->name; }
    public function getClassLabelAttribute() { return \App\Modules\Academics\Models\Section::labelFor($this->schoolClass?->name, $this->section?->name ?? '', $this->section?->stream).($this->section ? '' : ' (all sections)'); }
}
