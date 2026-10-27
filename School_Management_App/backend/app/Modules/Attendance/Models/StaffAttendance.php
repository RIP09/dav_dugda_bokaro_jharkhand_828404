<?php

namespace App\Modules\Attendance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class StaffAttendance extends Model
{
    protected $fillable = ['user_id', 'date', 'status', 'in_time', 'out_time', 'remark', 'marked_by'];
    // 'date' is stored as a plain Y-m-d string so lookups by date match on every database engine

    public function user() { return $this->belongsTo(User::class); }
}
