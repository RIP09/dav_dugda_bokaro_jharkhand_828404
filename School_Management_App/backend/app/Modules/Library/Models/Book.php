<?php

namespace App\Modules\Library\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = ['title', 'author', 'isbn', 'category', 'publisher', 'total_copies', 'available_copies', 'rack'];

    public function issues() { return $this->hasMany(BookIssue::class); }
}
