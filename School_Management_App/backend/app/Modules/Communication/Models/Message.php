<?php

namespace App\Modules\Communication\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['sender_id', 'receiver_id', 'subject', 'body', 'read_at'];
    protected $casts = ['read_at' => 'datetime'];
    protected $appends = ['sender_name', 'receiver_name'];
    protected $hidden = ['sender', 'receiver'];

    public function sender() { return $this->belongsTo(User::class, 'sender_id'); }
    public function receiver() { return $this->belongsTo(User::class, 'receiver_id'); }
    public function getSenderNameAttribute() { return $this->sender?->name; }
    public function getReceiverNameAttribute() { return $this->receiver?->name; }
}
