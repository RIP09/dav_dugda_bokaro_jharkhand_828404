<?php

namespace App\Modules\Fees\Models;

use App\Modules\Students\Models\Student;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use \App\Core\Concerns\Auditable, \App\Core\Concerns\Lockable;

    protected $guarded = ['id'];
    protected $casts = ['amount' => 'float', 'discount' => 'float', 'paid' => 'float', 'due_date' => 'date:Y-m-d'];
    protected $appends = ['balance'];
    protected array $immutable = ['invoice_no'];

    public function student() { return $this->belongsTo(Student::class); }
    public function feeType() { return $this->belongsTo(FeeType::class); }
    public function payments() { return $this->hasMany(Payment::class)->latest('paid_on'); }
    public function getBalanceAttribute(): float { return round(max(0, $this->amount + ($this->fine ?? 0) - $this->discount - $this->paid), 2); }
    public function refreshStatus(): void
    {
        $this->paid = (float) $this->payments()->where('status', '!=', 'cancelled')->sum('amount');
        $net = $this->amount + ($this->fine ?? 0) - $this->discount;
        $this->status = $this->paid <= 0 ? 'unpaid' : ($this->paid + 0.001 >= $net ? 'paid' : 'partial');
        $this->save();
    }
}
