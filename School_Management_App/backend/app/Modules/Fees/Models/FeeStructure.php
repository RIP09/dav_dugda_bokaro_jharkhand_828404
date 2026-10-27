<?php

namespace App\Modules\Fees\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\Lockable;
use App\Modules\Academics\Models\AcademicYear;
use App\Modules\Academics\Models\SchoolClass;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeStructure extends Model
{
    use Auditable, Lockable;

    protected $table = 'fee_structures';

    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function feeType(): BelongsTo { return $this->belongsTo(FeeType::class, 'fee_type_id'); }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class, 'academic_year_id'); }
}
