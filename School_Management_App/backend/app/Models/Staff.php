<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $table = 'staff';
    protected $fillable = ['user_id', 'employee_id', 'designation', 'department', 'joining_date', 'qualification', 'salary'];
    protected $casts = ['joining_date' => 'date:Y-m-d', 'salary' => 'float'];

    public function user() { return $this->belongsTo(User::class); }
}
