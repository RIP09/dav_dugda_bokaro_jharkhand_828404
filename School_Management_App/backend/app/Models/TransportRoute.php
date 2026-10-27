<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportRoute extends Model
{
    protected $fillable = ['name', 'vehicle_no', 'driver_name', 'driver_phone', 'fare', 'stops', 'pickup_time', 'drop_time'];
    protected $casts = ['stops' => 'array', 'fare' => 'float'];

    public function students() { return $this->hasMany(Student::class); }
}
