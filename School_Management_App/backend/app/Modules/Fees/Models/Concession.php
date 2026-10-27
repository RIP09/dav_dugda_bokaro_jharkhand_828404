<?php

namespace App\Modules\Fees\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\Lockable;
use App\Modules\Students\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Concession extends Model
{
    use Auditable, Lockable;

    protected $table = 'concessions';

    protected $guarded = ['id'];

    protected $casts = [
        'value' => 'decimal:2',
        'valid_till' => 'date:Y-m-d',
    ];

    public function student(): BelongsTo { return $this->belongsTo(Student::class, 'student_id'); }
    public function feeType(): BelongsTo { return $this->belongsTo(FeeType::class, 'fee_type_id'); }
}
