<?php

namespace Website\Modules\Careers;

use Website\Support\SiteModel;

class Vacancy extends SiteModel
{
    protected $table = 'site_vacancies';
    protected $casts = ['is_open' => 'boolean', 'last_date' => 'date'];
}
