<?php

namespace Website\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Where website images and files live.
 *  - "asset:img/x.jpg"  → a built-in demo file shipped in website/public-assets (served at /site-assets/...)
 *  - "site/abc.jpg"     → a file uploaded in the CMS, stored on the "public" disk (storage/app/public/site/...)
 */
class Media
{
    public static function url(?string $path, ?string $fallback = null): ?string
    {
        if (! $path) {
            return $fallback ? self::asset($fallback) : null;
        }
        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }
        if (Str::startsWith($path, 'asset:')) {
            return self::asset(substr($path, 6));
        }
        if (Str::startsWith($path, '/')) {
            return url($path); // a file served from the site root, e.g. /downloads/App.apk
        }

        return url('site-media/'.ltrim($path, '/'));
    }

    public static function asset(string $rel): string
    {
        $rel = ltrim($rel, '/');
        $file = self::assetPath($rel);
        $v = is_file($file) ? substr((string) filemtime($file), -6) : '1';

        return url('site-assets/'.$rel).'?v='.$v;
    }

    public static function assetPath(string $rel): string
    {
        return dirname(__DIR__, 2).'/public-assets/'.ltrim($rel, '/');
    }

    /** Store an uploaded image, shrinking big photos so pages stay fast on phones. */
    public static function storeImage(UploadedFile $file, int $maxWidth = 1920): string
    {
        $dir = 'site/'.date('Y/m');
        $mime = $file->getMimeType();
        $canResize = function_exists('imagecreatefromstring') && in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true);
        if ($canResize) {
            try {
                $src = @imagecreatefromstring(file_get_contents($file->getRealPath()));
                if ($src) {
                    $w = imagesx($src);
                    $h = imagesy($src);
                    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
                        $exif = @exif_read_data($file->getRealPath());
                        $o = $exif['Orientation'] ?? 1;
                        if (in_array($o, [3, 6, 8])) {
                            $src = imagerotate($src, [3 => 180, 6 => -90, 8 => 90][$o], 0);
                            $w = imagesx($src);
                            $h = imagesy($src);
                        }
                    }
                    $hasAlpha = $mime === 'image/png' || $mime === 'image/webp';
                    $nw = min($w, $maxWidth);
                    $nh = (int) round($h * $nw / max($w, 1));
                    $dst = imagecreatetruecolor($nw, $nh);
                    if ($hasAlpha) {
                        imagealphablending($dst, false);
                        imagesavealpha($dst, true);
                    }
                    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                    ob_start();
                    if ($hasAlpha) {
                        imagepng($dst, null, 7);
                        $ext = 'png';
                    } else {
                        imagejpeg($dst, null, 82);
                        $ext = 'jpg';
                    }
                    $bytes = ob_get_clean();
                    $path = $dir.'/'.Str::random(20).'.'.$ext;
                    Storage::disk('public')->put($path, $bytes);

                    return $path;
                }
            } catch (\Throwable $e) {
                // fall through to storing the original file
            }
        }

        return $file->store($dir, 'public');
    }

    public static function storeFile(UploadedFile $file): string
    {
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'file';
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');

        return $file->storeAs('site/files/'.date('Y/m'), Str::limit($name, 60, '').'-'.Str::random(6).'.'.$ext, 'public');
    }

    public static function delete(?string $path): void
    {
        if ($path && ! Str::startsWith($path, ['asset:', 'http'])) {
            Storage::disk('public')->delete($path);
        }
    }

    /** Absolute path of a release file (/downloads/NAME): storage/app/downloads first, then public/downloads. */
    public static function releasePath(string $name): ?string
    {
        foreach ([storage_path('app/downloads/'.$name), public_path('downloads/'.$name)] as $p) {
            if (is_file($p)) {
                return $p;
            }
        }

        return null;
    }

    public static function sizeLabel(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        $full = Str::startsWith($path, 'asset:') ? self::assetPath(substr($path, 6))
            : (Str::startsWith($path, '/downloads/') ? (string) self::releasePath(basename($path))
                : (Str::startsWith($path, '/') ? public_path(ltrim($path, '/')) : Storage::disk('public')->path($path)));
        if (! is_file($full)) {
            return null;
        }
        $b = filesize($full);

        return $b > 1048576 ? round($b / 1048576, 1).' MB' : max(1, round($b / 1024)).' KB';
    }

    public static function extension(?string $path): string
    {
        return strtoupper(pathinfo((string) $path, PATHINFO_EXTENSION) ?: 'FILE');
    }
}
