<?php

namespace App\Modules\Exams\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use \App\Core\Concerns\Auditable, \App\Core\Concerns\Lockable;

    protected $guarded = ['id'];
    protected $casts = ['start_date' => 'date:Y-m-d', 'end_date' => 'date:Y-m-d', 'is_published' => 'boolean', 'results_locked' => 'boolean', 'locked_at' => 'datetime'];

    public function schedules() { return $this->hasMany(ExamSchedule::class); }
    public function marks() { return $this->hasMany(Mark::class); }

    public function lockReason(): ?string
    {
        return $this->results_locked ? 'Results of this exam are locked (final) and cannot be changed.' : null;
    }
}
