<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $fillable = ['user_id', 'student_id', 'type', 'from_date', 'to_date', 'reason', 'status', 'reviewed_by', 'review_note'];
    protected $casts = ['from_date' => 'date:Y-m-d', 'to_date' => 'date:Y-m-d'];
    protected $appends = ['applicant_name', 'student_name', 'reviewer_name', 'days'];
    protected $hidden = ['applicant', 'student', 'reviewer'];

    public function applicant() { return $this->belongsTo(User::class, 'user_id'); }
    public function student() { return $this->belongsTo(Student::class); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function getApplicantNameAttribute() { return $this->applicant?->name; }
    public function getStudentNameAttribute() { return $this->student?->name; }
    public function getReviewerNameAttribute() { return $this->reviewer?->name; }
    public function getDaysAttribute(): int { return $this->from_date && $this->to_date ? $this->from_date->diffInDays($this->to_date) + 1 : 1; }
}
