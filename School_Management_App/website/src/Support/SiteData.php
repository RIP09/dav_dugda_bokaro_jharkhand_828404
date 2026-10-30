<?php

namespace Website\Support;

use Illuminate\Support\Facades\Schema;
use Website\Modules\Admissions\Enquiry;
use Website\Modules\Contact\ContactMessage;
use Website\Modules\Menus\MenuItem;

class SiteData
{
    public static function menu(string $location)
    {
        try {
            return MenuItem::query()->where('location', $location)->whereNull('parent_id')->where('is_visible', true)
                ->with('children')->orderBy('sort')->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    public static function inboxCounts(): array
    {
        try {
            return ['enquiries' => Enquiry::where('status', 'new')->count(), 'messages' => ContactMessage::where('status', 'new')->count()];
        } catch (\Throwable $e) {
            return ['enquiries' => 0, 'messages' => 0];
        }
    }

    /** Is this menu link the page we are on? */
    public static function active(string $url): bool
    {
        $path = trim(parse_url($url, PHP_URL_PATH) ?? '', '/');
        $cur = trim(request()->path(), '/');

        return $path === '' ? $cur === '' : ($cur === $path || str_starts_with($cur.'/', $path.'/'));
    }
}
