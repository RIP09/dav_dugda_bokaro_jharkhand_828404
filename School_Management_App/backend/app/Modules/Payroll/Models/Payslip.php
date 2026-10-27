<?php

namespace App\Modules\Payroll\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\Lockable;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    use Auditable, Lockable;

    protected $table = 'payslips';

    protected $guarded = ['id'];

    protected array $immutable = ['payslip_no', 'user_id', 'month'];

    protected $casts = [
        'breakdown' => 'array',
        'paid_on' => 'date:Y-m-d',
        'gross' => 'decimal:2',
        'deductions' => 'decimal:2',
        'net' => 'decimal:2',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }

    public function lockReason(): ?string
    {
        return $this->status === 'paid' ? 'Paid salary slips are permanent.' : null;
    }
}
