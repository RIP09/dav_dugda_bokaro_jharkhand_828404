<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ADMIN = 'admin';
    public const TEACHER = 'teacher';
    public const ACCOUNTANT = 'accountant';
    public const LIBRARIAN = 'librarian';
    public const STAFF = 'staff';
    public const PARENT = 'parent';
    public const STUDENT = 'student';

    /** Roles that sign in to the Staff app. */
    public const STAFF_ROLES = [self::TEACHER, self::ACCOUNTANT, self::LIBRARIAN, self::STAFF];

    /** Which app each role may sign in to. */
    public const APP_ROLES = [
        'school' => [self::ADMIN],
        'staff' => self::STAFF_ROLES,
        'parent' => [self::PARENT],
        'student' => [self::STUDENT],
    ];

    protected $fillable = [
        'name', 'email', 'phone', 'role', 'gender', 'dob', 'address', 'photo', 'is_active', 'password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $appends = ['photo_url'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'dob' => 'date:Y-m-d',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? url('storage/'.$this->photo) : null;
    }

    public function isAdmin(): bool { return $this->role === self::ADMIN; }
    public function isStaff(): bool { return in_array($this->role, self::STAFF_ROLES, true); }
    public function hasRole(string ...$roles): bool { return in_array($this->role, $roles, true); }

    public function student(): HasOne { return $this->hasOne(Student::class); }
    public function staffProfile(): HasOne { return $this->hasOne(Staff::class); }
    public function children(): HasMany { return $this->hasMany(Student::class, 'parent_id'); }

    /** Student ids this user may see (self for student, children for parent). */
    public function ownStudentIds(): array
    {
        return match ($this->role) {
            self::STUDENT => $this->student ? [$this->student->id] : [],
            self::PARENT => $this->children()->pluck('id')->all(),
            default => [],
        };
    }

    /** Section ids a teacher is connected to (class teacher or subject period). */
    public function teachingSectionIds(): array
    {
        $a = Section::where('teacher_id', $this->id)->pluck('id')->all();
        $b = TimetableEntry::where('teacher_id', $this->id)->pluck('section_id')->all();
        return array_values(array_unique(array_merge($a, $b)));
    }
}
