<?php

namespace Website\Http;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Website\Support\Media;

/**
 * Serves the website's own CSS/JS/images straight from website/public-assets (so no "publish" step is
 * needed on a free host) and CMS uploads from storage (so no storage:link symlink is needed either).
 */
class AssetController extends Controller
{
    const TYPES = ['css' => 'text/css', 'js' => 'application/javascript', 'svg' => 'image/svg+xml', 'png' => 'image/png',
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp', 'gif' => 'image/gif', 'woff2' => 'font/woff2',
        'ico' => 'image/x-icon', 'pdf' => 'application/pdf', 'txt' => 'text/plain', 'json' => 'application/json', 'webmanifest' => 'application/manifest+json'];

    public function asset(string $path)
    {
        abort_if(Str::contains($path, '..'), 404);
        $file = Media::assetPath($path);
        abort_unless(is_file($file), 404);
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        return response()->file($file, [
            'Content-Type' => self::TYPES[$ext] ?? 'application/octet-stream',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    public function media(string $path)
    {
        abort_if(Str::contains($path, '..') || ! Str::startsWith($path, 'site/'), 404);
        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $headers = ['Cache-Control' => 'public, max-age=2592000'];
        if (isset(self::TYPES[$ext])) {
            $headers['Content-Type'] = self::TYPES[$ext];
        }
        if ($ext === 'svg') {
            $headers['Content-Security-Policy'] = "default-src 'none'; style-src 'unsafe-inline'";
        }
        if (in_array($ext, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'csv'], true)) {
            return response()->download($disk->path($path), basename($path), $headers);
        }

        return response()->file($disk->path($path), $headers);
    }

    /** Big release files (apps, guides) kept in storage/app/downloads, served at /downloads/{file}. */
    public function release(string $file)
    {
        abort_if(Str::contains($file, ['..', '/']), 404);
        $path = Media::releasePath($file);
        abort_unless($path, 404);
        $types = ['apk' => 'application/vnd.android.package-archive', 'exe' => 'application/vnd.microsoft.portable-executable',
            'zip' => 'application/zip', 'pdf' => 'application/pdf', 'txt' => 'text/plain'];
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $headers = ['Content-Type' => $types[$ext] ?? 'application/octet-stream', 'Cache-Control' => 'public, max-age=86400'];

        return in_array($ext, ['pdf', 'txt'], true)
            ? response()->file($path, $headers)
            : response()->download($path, $file, $headers);
    }

    public function sitemap()
    {
        $urls = [route('website.home')];
        foreach (\Website\Modules\Pages\PageController::NAMED as $slug => $name) {
            if (\Illuminate\Support\Facades\Route::has($name)) {
                $urls[] = route($name);
            }
        }
        foreach (\Website\Modules\Pages\Page::where('is_published', true)->whereNotIn('slug', array_keys(\Website\Modules\Pages\PageController::NAMED))->get() as $p) {
            $urls[] = route('website.page', $p->slug);
        }
        foreach (\Website\Modules\Notices\Notice::published()->latest('published_at')->limit(200)->get() as $n) {
            $urls[] = route('website.notices.show', $n->slug);
        }
        foreach (\Website\Modules\Events\Event::published()->latest('starts_at')->limit(100)->get() as $e) {
            $urls[] = route('website.events.show', $e->slug);
        }
        foreach (\Website\Modules\Gallery\Album::published()->get() as $a) {
            $urls[] = route('website.gallery.show', $a->slug);
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach (array_unique($urls) as $u) {
            $xml .= '<url><loc>'.e($u).'</loc></url>';
        }

        return response($xml.'</urlset>', 200, ['Content-Type' => 'application/xml']);
    }
}
