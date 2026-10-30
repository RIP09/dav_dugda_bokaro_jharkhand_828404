<?php

namespace Website\Modules\Testimonials;

use Website\Support\SiteModel;

class Testimonial extends SiteModel
{
    protected $table = 'site_testimonials';
    protected $casts = ['is_published' => 'boolean'];
}
