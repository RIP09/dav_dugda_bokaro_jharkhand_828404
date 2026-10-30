<?php

namespace Website\Modules\Faculty;

use Website\Support\SiteModel;

class Person extends SiteModel
{
    protected $table = 'site_people';
    protected $casts = ['is_published' => 'boolean'];
}
