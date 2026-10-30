<?php

namespace Website\Modules\Gallery;

use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    protected $table = 'site_photos';
    protected $guarded = ['id'];

    public function album()
    {
        return $this->belongsTo(Album::class, 'album_id');
    }
}
