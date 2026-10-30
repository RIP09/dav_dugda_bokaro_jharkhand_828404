<?php

namespace App\Modules\Academics\Models;

use App\Modules\Students\Models\Student;
use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    use \App\Core\Concerns\Auditable, \App\Core\Concerns\Lockable;

    protected $table = 'school_classes';
    protected $guarded = ['id'];

    /** "6" / "VI" → "Class 6" / "Class VI"; "Class 6", "LKG", "Nursery" stay as they are. */
    public static function displayName(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') return '';
        return preg_match('/^(\d{1,2}|[IVX]{1,4}|[ivx]{1,4})(st|nd|rd|th)?$/', $name) ? 'Class '.$name : $name;
    }

    /** Class XI / XII (the only classes with a Science / Commerce stream). */
    public static function isSeniorSecondary(?self $class): bool
    {
        if (! $class) return false;
        if ($class->group === 'Senior Secondary') return true;
        return (bool) preg_match('/(^|\D)(11|12)(\D|$)|\bXII?\b/i', (string) $class->name);
    }

    public function getDisplayNameAttribute(): string { return self::displayName($this->name); }

    public function sections() { return $this->hasMany(Section::class, 'class_id')->orderBy('name'); }
    public function subjects() { return $this->hasMany(Subject::class, 'class_id')->orderBy('name'); }
    public function students() { return $this->hasMany(Student::class, 'class_id'); }
}
