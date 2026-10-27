<?php

namespace App\Modules\Fees\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use \App\Core\Concerns\Auditable, \App\Core\Concerns\Lockable;

    protected $guarded = ['id'];
    protected $casts = ['amount' => 'float', 'paid_on' => 'date:Y-m-d'];

    protected array $immutable = ['receipt_no', 'invoice_id', 'amount'];

    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function receiver() { return $this->belongsTo(\App\Models\User::class, 'received_by'); }

    public function lockReason(): ?string
    {
        return $this->status === 'cancelled'
            ? 'This receipt was cancelled and is kept for the record.'
            : 'Issued fee receipts are permanent. Use “Cancel receipt” (with a reason) to reverse it, then collect again.';
    }
}
