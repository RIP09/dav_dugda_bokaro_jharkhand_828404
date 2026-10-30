<?php

namespace Website\Modules\Notices;

use Website\Support\SiteModel;

class Notice extends SiteModel
{
    protected $table = 'site_notices';
    protected $casts = ['is_published' => 'boolean', 'is_pinned' => 'boolean', 'in_ticker' => 'boolean', 'published_at' => 'date'];
}
