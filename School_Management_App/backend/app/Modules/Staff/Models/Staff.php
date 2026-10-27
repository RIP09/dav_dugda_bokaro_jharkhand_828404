<?php

namespace App\Modules\Staff\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use \App\Core\Concerns\Auditable, \App\Core\Concerns\Lockable;

    protected $table = 'staff';
    protected $guarded = ['id'];
    protected array $immutable = ['employee_id'];
    protected $casts = ['joining_date' => 'date:Y-m-d', 'salary' => 'float'];

    public function user() { return $this->belongsTo(User::class); }
}
