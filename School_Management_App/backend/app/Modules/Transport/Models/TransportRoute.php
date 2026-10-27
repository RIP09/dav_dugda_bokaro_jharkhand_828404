<?php

namespace App\Modules\Transport\Models;

use App\Modules\Students\Models\Student;
use Illuminate\Database\Eloquent\Model;

class TransportRoute extends Model
{
    use \App\Core\Concerns\Auditable, \App\Core\Concerns\Lockable;

    protected $guarded = ['id'];
    protected $casts = ['stops' => 'array', 'fare' => 'float'];

    public function students() { return $this->hasMany(Student::class); }
}
