<?php

namespace Website\Modules\Achievements;

use Website\Support\SiteModel;

class Achievement extends SiteModel
{
    protected $table = 'site_achievements';
    protected $casts = ['is_published' => 'boolean'];
}
