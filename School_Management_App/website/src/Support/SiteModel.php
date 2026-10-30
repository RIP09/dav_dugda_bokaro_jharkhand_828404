<?php

namespace Website\Support;

use Illuminate\Database\Eloquent\Model;
use Website\Modules\Users\SiteAdmin;

/** Base for every website content model: mass-assignable, remembers who last edited it. */
abstract class SiteModel extends Model
{
    protected $guarded = ['id'];

    public function editor()
    {
        return $this->belongsTo(SiteAdmin::class, 'updated_by');
    }

    public function scopePublished($q)
    {
        return $q->where($this->getTable().'.is_published', true);
    }
}
