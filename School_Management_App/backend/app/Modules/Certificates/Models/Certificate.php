<?php

namespace App\Modules\Certificates\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\Lockable;
use App\Models\User;
use App\Modules\Students\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    use Auditable, Lockable;

    protected $table = 'certificates';

    protected $guarded = ['id'];

    protected array $immutable = ['certificate_no', 'type', 'student_id'];

    protected $casts = [
        'issue_date' => 'date:Y-m-d',
        'leaving_date' => 'date:Y-m-d',
    ];

    public function student(): BelongsTo { return $this->belongsTo(Student::class, 'student_id'); }
    public function issuer(): BelongsTo { return $this->belongsTo(User::class, 'issued_by'); }

    public const TYPES = ['tc' => 'Transfer Certificate', 'character' => 'Character Certificate', 'bonafide' => 'Bonafide Certificate'];

    public function lockReason(): ?string
    {
        return match ($this->status) {
            'issued' => 'Issued certificates are permanent. Cancel it (with a reason) and issue a new one.',
            'cancelled' => 'This certificate was cancelled and is kept for the record.',
            default => null,
        };
    }
}
