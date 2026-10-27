<?php

namespace App\Modules\Academics\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\Lockable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Syllabus extends Model
{
    use Auditable, Lockable;

    protected $table = 'syllabus';

    protected $guarded = ['id'];

    protected $casts = [
        'completed_on' => 'date:Y-m-d',
    ];

    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class, 'subject_id'); }
}
