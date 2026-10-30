<?php

namespace Website\Modules\Contact;

use Website\Support\SiteModel;

class ContactMessage extends SiteModel
{
    protected $table = 'site_messages';
    protected $casts = ['handled_at' => 'datetime'];
}
