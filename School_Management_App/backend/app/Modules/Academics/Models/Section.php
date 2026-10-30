<?php

namespace App\Modules\Academics\Models;

use App\Models\User;
use App\Modules\Students\Models\Student;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use \App\Core\Concerns\Auditable, \App\Core\Concerns\Lockable;

    protected $guarded = ['id'];

    /** Every API response that carries a section also says which class it belongs to (e.g. "Class 6 – A"). */
    protected $appends = ['label', 'class_name'];

    /** The XI–XII streams this school offers. (No Humanities / Arts stream at DAV Dugda.) */
    public const STREAMS = ['Science', 'Commerce'];

    public static function labelFor(?string $className, ?string $section, ?string $stream = null): string
    {
        $c = SchoolClass::displayName($className);
        $s = trim((string) $section);
        $label = $c !== '' ? ($s !== '' ? $c.' – '.$s : $c) : ($s !== '' ? 'Section '.$s : '');
        if ($stream) $label .= ' ('.$stream.')';
        return $label;
    }

    public function getClassNameAttribute(): ?string
    {
        $c = $this->relationLoaded('schoolClass') ? $this->getRelation('schoolClass') : ($this->class_id ? $this->schoolClass()->first() : null);
        return $c ? SchoolClass::displayName($c->name) : null;
    }

    public function getLabelAttribute(): string
    {
        return self::labelFor($this->class_name, $this->name, $this->stream ?? null);
    }

    public function schoolClass() { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function teacher() { return $this->belongsTo(User::class, 'teacher_id')->select('id', 'name', 'phone', 'role'); }
    public function students() { return $this->hasMany(Student::class); }
}
