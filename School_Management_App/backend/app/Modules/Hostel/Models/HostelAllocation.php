<?php

namespace App\Modules\Hostel\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\Lockable;
use App\Modules\Students\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostelAllocation extends Model
{
    use Auditable, Lockable;

    protected $table = 'hostel_allocations';

    protected $guarded = ['id'];

    protected $casts = [
        'from_date' => 'date:Y-m-d',
        'to_date' => 'date:Y-m-d',
    ];

    public function student(): BelongsTo { return $this->belongsTo(Student::class, 'student_id'); }
    public function room(): BelongsTo { return $this->belongsTo(HostelRoom::class, 'hostel_room_id'); }
}
