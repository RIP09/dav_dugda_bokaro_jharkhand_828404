<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = ['user_id', 'admission_no', 'roll_no', 'class_id', 'section_id', 'academic_year_id', 'parent_id', 'transport_route_id', 'admission_date', 'blood_group', 'religion', 'category', 'aadhaar_no', 'father_name', 'mother_name', 'guardian_phone', 'house', 'status'];
    protected $casts = ['admission_date' => 'date:Y-m-d'];
    protected $appends = ['name', 'class_label'];

    public function user() { return $this->belongsTo(User::class); }
    public function schoolClass() { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function section() { return $this->belongsTo(Section::class); }
    public function parent() { return $this->belongsTo(User::class, 'parent_id')->select('id', 'name', 'phone', 'email', 'role'); }
    public function transportRoute() { return $this->belongsTo(TransportRoute::class); }
    public function academicYear() { return $this->belongsTo(AcademicYear::class); }
    public function attendances() { return $this->hasMany(StudentAttendance::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }
    public function marks() { return $this->hasMany(Mark::class); }

    public function getNameAttribute(): ?string { return $this->user?->name; }
    public function getClassLabelAttribute(): ?string
    {
        $c = $this->schoolClass?->name; $s = $this->section?->name;
        return $c ? trim($c.($s ? ' - '.$s : '')) : null;
    }
}
