<?php

namespace Website\Modules\Pages;

use Website\Support\SiteModel;

class Page extends SiteModel
{
    protected $table = 'site_pages';
    protected $casts = ['is_published' => 'boolean', 'is_system' => 'boolean'];
}
