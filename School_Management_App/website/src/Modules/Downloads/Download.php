<?php

namespace Website\Modules\Downloads;

use Website\Support\SiteModel;

class Download extends SiteModel
{
    protected $table = 'site_downloads';
    protected $casts = ['is_published' => 'boolean'];
}
