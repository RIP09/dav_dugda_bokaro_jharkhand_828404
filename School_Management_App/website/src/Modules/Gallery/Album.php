<?php

namespace Website\Modules\Gallery;

use Website\Support\SiteModel;

class Album extends SiteModel
{
    protected $table = 'site_albums';
    protected $casts = ['is_published' => 'boolean', 'event_date' => 'date'];

    public function photos()
    {
        return $this->hasMany(Photo::class, 'album_id')->orderBy('sort')->orderBy('id');
    }

    public function coverUrl(): ?string
    {
        $path = $this->cover ?: optional($this->photos->first())->image;

        return \Website\Support\Media::url($path, 'img/placeholder.svg');
    }
}
