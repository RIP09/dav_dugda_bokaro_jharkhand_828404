<?php

namespace App\Modules\Students\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\Lockable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDocument extends Model
{
    use Auditable, Lockable;

    protected $table = 'student_documents';

    protected $guarded = ['id'];

    protected $casts = [
    ];

    public function student(): BelongsTo { return $this->belongsTo(Student::class, 'student_id'); }
}
