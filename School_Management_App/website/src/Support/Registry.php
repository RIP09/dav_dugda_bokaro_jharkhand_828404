<?php

namespace Website\Support;

/** Every content type the CMS can edit, in menu order. Add a new Resource class here to get a full editor for it. */
class Registry
{
    public static function resources(): array
    {
        return [
            \Website\Modules\Pages\PageResource::class,
            \Website\Modules\Notices\NoticeResource::class,
            \Website\Modules\Events\EventResource::class,
            \Website\Modules\Gallery\AlbumResource::class,
            \Website\Modules\Faculty\FacultyResource::class,
            \Website\Modules\Achievements\AchievementResource::class,
            \Website\Modules\Facilities\FacilityResource::class,
            \Website\Modules\Downloads\DownloadResource::class,
            \Website\Modules\Careers\VacancyResource::class,
            \Website\Modules\Disclosure\DisclosureResource::class,
            \Website\Modules\Banners\BannerResource::class,
            \Website\Modules\Home\StatResource::class,
            \Website\Modules\Home\HighlightResource::class,
            \Website\Modules\Testimonials\TestimonialResource::class,
            \Website\Modules\Menus\MenuResource::class,
        ];
    }

    /** @return class-string<Resource>|null */
    public static function find(string $key): ?string
    {
        foreach (self::resources() as $r) {
            if ($r::$key === $key) {
                return $r;
            }
        }

        return null;
    }

    public static function grouped(): array
    {
        $out = [];
        foreach (self::resources() as $r) {
            $out[$r::$group][] = $r;
        }

        return $out;
    }
}
