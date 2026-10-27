<?php

use Illuminate\Support\Facades\Route;

// The public school website (website/ package) owns "/" when it is installed.
if (! class_exists(\Website\WebsiteServiceProvider::class)) {
    Route::get('/', fn () => view('welcome'));
}
