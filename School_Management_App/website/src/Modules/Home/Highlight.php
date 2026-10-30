<?php

namespace Website\Modules\Home;

use Website\Support\SiteModel;

class Highlight extends SiteModel
{
    protected $table = 'site_highlights';
    protected $casts = ['is_published' => 'boolean'];
}
