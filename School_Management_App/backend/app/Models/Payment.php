<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['receipt_no', 'invoice_id', 'amount', 'method', 'reference', 'paid_on', 'received_by'];
    protected $casts = ['amount' => 'float', 'paid_on' => 'date:Y-m-d'];

    public function invoice() { return $this->belongsTo(Invoice::class); }
}
