<?php

namespace Website\Modules\Downloads;

use Illuminate\Routing\Controller;

class DownloadController extends Controller
{
    public const SECTIONS = ['apps' => 'School Management Apps — version 2.0.0', 'guides' => 'Guides', 'prospectus' => 'Prospectus', 'forms' => 'Forms', 'fees' => 'Fee Structure', 'circulars' => 'Circulars', 'books' => 'Book Lists', 'other' => 'Other'];

    public function index()
    {
        return view('website::site.downloads.index', [
            'groups' => Download::published()->whereNotNull('file')->orderBy('sort')->get()->groupBy('category'),
            'sections' => self::SECTIONS,
        ]);
    }
}
