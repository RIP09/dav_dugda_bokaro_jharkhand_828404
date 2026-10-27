<?php

namespace App\Modules\Academics\Models;

use App\Modules\Students\Models\Student;
use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    use \App\Core\Concerns\Auditable, \App\Core\Concerns\Lockable;

    protected $table = 'school_classes';
    protected $guarded = ['id'];

    public function sections() { return $this->hasMany(Section::class, 'class_id')->orderBy('name'); }
    public function subjects() { return $this->hasMany(Subject::class, 'class_id')->orderBy('name'); }
    public function students() { return $this->hasMany(Student::class, 'class_id'); }
}
