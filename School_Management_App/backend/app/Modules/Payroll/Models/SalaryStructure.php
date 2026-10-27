<?php

namespace App\Modules\Payroll\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\Lockable;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryStructure extends Model
{
    use Auditable, Lockable;

    protected $table = 'salary_structures';

    protected $guarded = ['id'];

    protected $casts = [
        'basic' => 'decimal:2',
        'hra' => 'decimal:2',
        'da' => 'decimal:2',
        'ta' => 'decimal:2',
        'other_allowance' => 'decimal:2',
        'pf' => 'decimal:2',
        'esi' => 'decimal:2',
        'tds' => 'decimal:2',
        'other_deduction' => 'decimal:2',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }

    public function grossAmount(): float { return (float) ($this->basic + $this->hra + $this->da + $this->ta + $this->other_allowance); }
    public function deductionAmount(): float { return (float) ($this->pf + $this->esi + $this->tds + $this->other_deduction); }
}
