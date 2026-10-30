<?php

namespace Website\Modules\Downloads;

use Illuminate\Database\Eloquent\Model;
use Website\Support\F;
use Website\Support\Resource;

class DownloadResource extends Resource
{

    public static string $key = 'downloads';
    public static string $label = 'Downloads';
    public static string $singular = 'download';
    public static string $model = Download::class;
    public static string $icon = 'download';
    public static ?string $help = 'Prospectus, admission forms, fee structure, book lists… Upload a PDF and it appears on the Downloads page.';

    public static function fields(): array
    {
        return [
            F::text('title', 'Title', ['required' => true]),
            F::select('category', 'Section', ['apps' => 'Apps', 'guides' => 'Guides', 'prospectus' => 'Prospectus', 'forms' => 'Forms', 'fees' => 'Fee structure', 'circulars' => 'Circulars', 'books' => 'Book lists', 'other' => 'Other']),
            F::file('file', 'File (PDF, Word, Excel or image)'),
            F::text('description', 'Short note', ['wide' => true]),
            F::sort(),
            F::toggle('is_published', 'Show on website', ['default' => true]),
        ];
    }

    public static function columns(): array
    {
        return ['title' => 'Title', 'category' => 'Section', 'file' => 'File', 'is_published' => 'Live'];
    }

}
