<?php

namespace App\Modules\Admissions\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\Lockable;
use App\Models\User;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Students\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enquiry extends Model
{
    use Auditable, Lockable;

    protected $table = 'enquiries';

    protected $guarded = ['id'];

    protected $casts = [
        'dob' => 'date:Y-m-d',
        'follow_up_date' => 'date:Y-m-d',
        'test_date' => 'date:Y-m-d',
    ];

    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class, 'student_id'); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
}
