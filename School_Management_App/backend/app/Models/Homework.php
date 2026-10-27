<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Homework extends Model
{
    protected $table = 'homework';
    protected $fillable = ['class_id', 'section_id', 'subject_id', 'teacher_id', 'title', 'description', 'assigned_on', 'due_date'];
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
    public function getClassLabelAttribute() { return trim(($this->schoolClass?->name ?? '').($this->section ? ' - '.$this->section->name : '')); }
}
