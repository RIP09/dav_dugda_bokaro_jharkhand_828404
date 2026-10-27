<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookIssue extends Model
{
    protected $fillable = ['book_id', 'user_id', 'issued_on', 'due_on', 'returned_on', 'fine', 'issued_by'];
    protected $casts = ['issued_on' => 'date:Y-m-d', 'due_on' => 'date:Y-m-d', 'returned_on' => 'date:Y-m-d', 'fine' => 'float'];
    protected $appends = ['status', 'book_title', 'member_name'];
    protected $hidden = ['book', 'user'];

    public function book() { return $this->belongsTo(Book::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function getBookTitleAttribute() { return $this->book?->title; }
    public function getMemberNameAttribute() { return $this->user?->name; }
    public function getStatusAttribute(): string
    {
        if ($this->returned_on) return 'returned';
        return $this->due_on && $this->due_on->isPast() && ! $this->due_on->isToday() ? 'overdue' : 'issued';
    }
}
