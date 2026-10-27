<?php

namespace App\Core\Concerns;

use App\Core\Exceptions\RecordLockedException;
use App\Core\Locks;
use Illuminate\Validation\ValidationException;

/**
 * Permanent records. A model overrides lockReason() (null = editable) and may list $immutable fields
 * that can be set once (on create) and never changed. Enforced on every save/delete, whatever the caller.
 */
trait Lockable
{
    public static function bootLockable(): void
    {
        static::updating(function ($model) {
            if (! Locks::active()) return;
            if ($reason = $model->originalLockReason()) throw new RecordLockedException($reason);
            foreach ($model->immutableFields() as $f) {
                if ($model->isDirty($f) && $model->getRawOriginal($f) !== null && $model->getRawOriginal($f) !== '') {
                    throw ValidationException::withMessages([$f => "“{$f}” is permanent and cannot be changed once set."]);
                }
            }
        });
        static::deleting(function ($model) {
            if (! Locks::active()) return;
            if ($reason = $model->originalLockReason()) throw new RecordLockedException($reason);
        });
    }

    /** Reason this record is locked (null = editable). Override per model. */
    public function lockReason(): ?string { return null; }

    public function immutableFields(): array { return property_exists($this, 'immutable') ? $this->immutable : []; }

    /** Lock state of the record as stored (before any pending change). */
    public function originalLockReason(): ?string
    {
        if (! $this->exists) return null;
        $o = (clone $this)->setRawAttributes($this->getRawOriginal(), true);
        return $o->lockReason();
    }
}
