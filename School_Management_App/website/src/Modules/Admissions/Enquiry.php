<?php

namespace Website\Modules\Admissions;

use Website\Support\SiteModel;

class Enquiry extends SiteModel
{
    protected $table = 'site_enquiries';
    protected $casts = ['dob' => 'date', 'handled_at' => 'datetime'];
}
