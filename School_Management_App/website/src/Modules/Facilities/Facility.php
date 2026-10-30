<?php

namespace Website\Modules\Facilities;

use Website\Support\SiteModel;

class Facility extends SiteModel
{
    protected $table = 'site_facilities';
    protected $casts = ['is_published' => 'boolean'];
}
