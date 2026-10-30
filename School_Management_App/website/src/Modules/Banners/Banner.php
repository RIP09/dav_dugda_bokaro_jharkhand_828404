<?php

namespace Website\Modules\Banners;

use Website\Support\SiteModel;

class Banner extends SiteModel
{
    protected $table = 'site_banners';
    protected $casts = ['is_active' => 'boolean'];
}
