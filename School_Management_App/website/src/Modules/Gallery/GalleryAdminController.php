<?php

namespace Website\Modules\Gallery;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Website\Support\ChangeLog;
use Website\Support\Media;

/** Adding many photos to an album at once, captions, order and delete. */
class GalleryAdminController extends Controller
{
    public function upload(Request $request, Album $album)
    {
        $request->validate([
            'photos' => 'required|array|max:40',
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:15360',
        ], ['photos.required' => 'Choose at least one photo.', 'photos.*.image' => 'Only photos (JPG, PNG, WebP) can be added.']);
        $next = (int) $album->photos()->max('sort');
        $n = 0;
        foreach ($request->file('photos') as $file) {
            $album->photos()->create(['image' => Media::storeImage($file, 1800), 'sort' => ++$next]);
            $n++;
        }
        ChangeLog::record('updated', 'gallery', $album, ['photos' => [null, "+{$n} photo(s)"]]);

        return back()->with('ok', "{$n} photo(s) added to \"{$album->title}\".");
    }

    public function updatePhotos(Request $request, Album $album)
    {
        $data = $request->validate(['caption' => 'array', 'caption.*' => 'nullable|string|max:250', 'sort' => 'array', 'sort.*' => 'nullable|integer']);
        foreach ($album->photos as $p) {
            $p->caption = $data['caption'][$p->id] ?? $p->caption;
            if (isset($data['sort'][$p->id])) {
                $p->sort = (int) $data['sort'][$p->id];
            }
            $p->save();
        }
        ChangeLog::record('updated', 'gallery', $album, ['photo captions' => [null, 'edited']]);

        return back()->with('ok', 'Captions saved.');
    }

    public function destroyPhoto(Album $album, Photo $photo)
    {
        abort_unless($photo->album_id === $album->id, 404);
        Media::delete($photo->image);
        $photo->delete();
        ChangeLog::record('updated', 'gallery', $album, ['photos' => [null, '-1 photo']]);

        return back()->with('ok', 'Photo removed.');
    }

    public function cover(Album $album, Photo $photo)
    {
        abort_unless($photo->album_id === $album->id, 404);
        $album->cover = $photo->image;
        $album->save();

        return back()->with('ok', 'Cover photo set.');
    }
}
