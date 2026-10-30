<?php

namespace Website\Modules\Events;

use Website\Support\SiteModel;

class Event extends SiteModel
{
    protected $table = 'site_events';
    protected $casts = ['is_published' => 'boolean', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];
}
