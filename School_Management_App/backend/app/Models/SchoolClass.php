<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    protected $table = 'school_classes';
    protected $fillable = ['name', 'numeric_order', 'group'];

    public function sections() { return $this->hasMany(Section::class, 'class_id')->orderBy('name'); }
    public function subjects() { return $this->hasMany(Subject::class, 'class_id')->orderBy('name'); }
    public function students() { return $this->hasMany(Student::class, 'class_id'); }
}
