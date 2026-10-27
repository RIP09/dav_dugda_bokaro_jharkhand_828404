<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = ['invoice_no', 'student_id', 'fee_type_id', 'academic_year_id', 'title', 'amount', 'discount', 'paid', 'due_date', 'status'];
    protected $casts = ['amount' => 'float', 'discount' => 'float', 'paid' => 'float', 'due_date' => 'date:Y-m-d'];
    protected $appends = ['balance'];

    public function student() { return $this->belongsTo(Student::class); }
    public function feeType() { return $this->belongsTo(FeeType::class); }
    public function payments() { return $this->hasMany(Payment::class)->latest('paid_on'); }
    public function getBalanceAttribute(): float { return round(max(0, $this->amount - $this->discount - $this->paid), 2); }
    public function refreshStatus(): void
    {
        $this->paid = (float) $this->payments()->sum('amount');
        $net = $this->amount - $this->discount;
        $this->status = $this->paid <= 0 ? 'unpaid' : ($this->paid + 0.001 >= $net ? 'paid' : 'partial');
        $this->save();
    }
}
