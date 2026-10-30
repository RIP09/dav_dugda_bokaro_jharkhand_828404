<?php

namespace Website\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Website\Modules\Settings\Setting;

/** Key/value site settings with a request-level cache. */
class Settings
{
    protected static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache === null) {
            try {
                self::$cache = Schema::hasTable('site_settings')
                    ? Setting::query()->pluck('value', 'key')->all()
                    : [];
            } catch (\Throwable $e) {
                self::$cache = [];
            }
        }

        return self::$cache;
    }

    public static function get(string $key, $default = null)
    {
        $v = self::all()[$key] ?? null;

        return ($v === null || $v === '') ? $default : $v;
    }

    public static function set(string $key, $value, ?int $adminId = null): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value, 'updated_by' => $adminId]);
        self::$cache = null;
    }

    public static function flush(): void
    {
        self::$cache = null;
    }

    /** The groups and fields shown on the CMS "Site settings" screen. */
    public static function schema(): array
    {
        return [
            'School identity' => [
                ['site_name', 'School name', 'text'],
                ['site_short_name', 'Short name (shown on small screens)', 'text'],
                ['tagline', 'Tagline / motto', 'text'],
                ['logo', 'Logo (square PNG works best)', 'image'],
                ['favicon', 'Browser tab icon (favicon)', 'image'],
                ['affiliation_text', 'Affiliation line (e.g. Affiliated to CBSE, New Delhi)', 'text'],
                ['affiliation_no', 'CBSE affiliation number', 'text'],
                ['school_code', 'School code', 'text'],
                ['established_year', 'Year established', 'text'],
            ],
            'Principal' => [
                ['principal_name', 'Principal name', 'text'],
                ['principal_title', 'Principal title', 'text'],
                ['principal_qualifications', 'Qualifications (e.g. M.Sc. (Zoology), B.Ed.)', 'text'],
                ['principal_experience', 'Experience line', 'text'],
                ['principal_photo', 'Principal photo', 'image'],
                ['principal_excerpt', 'Short welcome shown on the home page', 'textarea'],
            ],
            'Contact details' => [
                ['address', 'Full address', 'textarea'],
                ['phone', 'Phone', 'text'],
                ['phone_alt', 'Second phone (optional)', 'text'],
                ['email', 'Email', 'text'],
                ['office_hours', 'Office hours', 'text'],
                ['whatsapp', 'WhatsApp number (digits with country code, e.g. 919431000000)', 'text'],
                ['map_embed_url', 'Google Maps embed link (from Share → Embed a map → the src="…" link)', 'text'],
            ],
            'Social media links' => [
                ['facebook', 'Facebook page link', 'text'],
                ['instagram', 'Instagram link', 'text'],
                ['youtube', 'YouTube channel link', 'text'],
                ['x', 'X (Twitter) link', 'text'],
            ],
            'Home page' => [
                ['admission_banner', 'Admissions banner text (top strip & home)', 'text'],
                ['admissions_open', 'Show "Admissions open" strip', 'toggle'],
                ['home_intro_title', 'Intro heading', 'text'],
                ['home_intro_text', 'Intro text', 'textarea'],
            ],
            'Look & colours' => [
                ['color_primary', 'Main colour', 'color'],
                ['color_accent', 'Accent colour', 'color'],
                ['footer_text', 'Footer about text', 'textarea'],
                ['copyright', 'Copyright line', 'text'],
            ],
            'Search engines (SEO)' => [
                ['meta_description', 'Site description for Google (1–2 sentences)', 'textarea'],
                ['meta_keywords', 'Keywords (comma separated)', 'text'],
                ['og_image', 'Share image (shown when the link is shared on WhatsApp/Facebook)', 'image'],
            ],
        ];
    }
}
