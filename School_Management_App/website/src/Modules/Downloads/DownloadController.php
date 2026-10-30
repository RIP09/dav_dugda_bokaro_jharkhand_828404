<?php

namespace Website\Modules\Downloads;

use Illuminate\Routing\Controller;

class DownloadController extends Controller
{
    public const SECTIONS = ['apps' => 'School Management Apps — version 2.0.1 (latest)', 'guides' => 'Guides', 'prospectus' => 'Prospectus', 'forms' => 'Forms', 'fees' => 'Fee Structure', 'circulars' => 'Circulars', 'books' => 'Book Lists', 'other' => 'Other'];

    public function index()
    {
        $appsEnabled = (bool) config('website.app_downloads');
        $items = Download::published()->whereNotNull('file')->orderBy('sort')->get();
        if (! $appsEnabled) {
            // While app downloads are paused, nothing from the apps section or the /downloads/ release folder is listed.
            $items = $items->reject(fn ($d) => $d->category === 'apps' || str_starts_with((string) $d->file, '/downloads/'));
        }

        return view('website::site.downloads.index', [
            'groups' => $items->groupBy('category'),
            'sections' => self::SECTIONS,
            'appsComingSoon' => ! $appsEnabled,
        ]);
    }
}
