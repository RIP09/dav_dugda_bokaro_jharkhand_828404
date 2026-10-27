<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = ['academic_year_id', 'name', 'term', 'start_date', 'end_date', 'is_published'];
    protected $casts = ['start_date' => 'date:Y-m-d', 'end_date' => 'date:Y-m-d', 'is_published' => 'boolean'];

    public function schedules() { return $this->hasMany(ExamSchedule::class); }
    public function marks() { return $this->hasMany(Mark::class); }
}
