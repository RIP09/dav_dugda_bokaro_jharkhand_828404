<?php

return [
    // Public app downloads (APKs, Windows installer/zip) at /downloads/{file} and on the Downloads page.
    // Off until the apps are ready for public use; set DOWNLOADS_ENABLED=true in .env to turn them back on.
    'app_downloads' => (bool) env('DOWNLOADS_ENABLED', false),
];
