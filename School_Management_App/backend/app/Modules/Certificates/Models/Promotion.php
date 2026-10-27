<?php

namespace App\Modules\Certificates\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\Lockable;
use App\Modules\Academics\Models\AcademicYear;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Students\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promotion extends Model
{
    use Auditable, Lockable;

    protected $table = 'promotions';

    protected $guarded = ['id'];

    protected $casts = [
    ];

    public function student(): BelongsTo { return $this->belongsTo(Student::class, 'student_id'); }
    public function fromClass(): BelongsTo { return $this->belongsTo(SchoolClass::class, 'from_class_id'); }
    public function toClass(): BelongsTo { return $this->belongsTo(SchoolClass::class, 'to_class_id'); }
    public function fromSection(): BelongsTo { return $this->belongsTo(Section::class, 'from_section_id'); }
    public function toSection(): BelongsTo { return $this->belongsTo(Section::class, 'to_section_id'); }
    public function fromYear(): BelongsTo { return $this->belongsTo(AcademicYear::class, 'from_academic_year_id'); }
    public function toYear(): BelongsTo { return $this->belongsTo(AcademicYear::class, 'to_academic_year_id'); }

    public function lockReason(): ?string { return 'Promotion / roll history is permanent.'; }
}
