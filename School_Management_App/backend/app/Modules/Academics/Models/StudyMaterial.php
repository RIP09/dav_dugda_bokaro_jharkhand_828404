<?php

namespace App\Modules\Academics\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\Lockable;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyMaterial extends Model
{
    use Auditable, Lockable;

    protected $table = 'study_materials';

    protected $guarded = ['id'];

    protected $casts = [
    ];

    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class, 'subject_id'); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
}
