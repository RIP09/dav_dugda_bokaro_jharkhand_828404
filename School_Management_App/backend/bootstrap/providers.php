<?php

use App\Providers\AppServiceProvider;

return array_values(array_filter([
    AppServiceProvider::class,
    // Public school website + its own CMS (School_Management_App/website), loaded when present.
    class_exists(\Website\WebsiteServiceProvider::class) ? \Website\WebsiteServiceProvider::class : null,
]));
