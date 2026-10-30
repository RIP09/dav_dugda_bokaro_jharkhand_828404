<?php

namespace Website\Modules\Settings;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Website\Support\ChangeLog;
use Website\Support\Media;
use Website\Support\Settings;

class SettingsController extends Controller
{
    public function edit()
    {
        return view('website::cms.settings', ['schema' => Settings::schema(), 'values' => Settings::all()]);
    }

    public function update(Request $request)
    {
        $rules = [];
        foreach (Settings::schema() as $fields) {
            foreach ($fields as [$key, $label, $type]) {
                $rules[$key] = match ($type) {
                    'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg,ico', 'max:5120'],
                    'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
                    'toggle' => ['nullable'],
                    'textarea' => ['nullable', 'string', 'max:5000'],
                    default => ['nullable', 'string', 'max:1000'],
                };
            }
        }
        $request->validate($rules);
        $current = Settings::all();
        $changes = [];
        $adminId = Auth::guard('webadmin')->id();
        foreach (Settings::schema() as $fields) {
            foreach ($fields as [$key, $label, $type]) {
                $old = $current[$key] ?? null;
                if ($type === 'image') {
                    $new = $old;
                    if ($request->boolean('_remove_'.$key)) {
                        Media::delete($old);
                        $new = null;
                    }
                    if ($request->hasFile($key)) {
                        $f = $request->file($key);
                        $new = in_array($f->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true) ? Media::storeImage($f, 1200) : Media::storeFile($f);
                    }
                } elseif ($type === 'toggle') {
                    $new = $request->boolean($key) ? '1' : '0';
                } else {
                    $new = $request->input($key);
                }
                if ((string) $old !== (string) $new) {
                    Settings::set($key, $new, $adminId);
                    $changes[$key] = [$old, $new];
                }
            }
        }
        if ($changes) {
            ChangeLog::record('updated', 'settings', null, $changes, 'Site settings');
        }

        return back()->with('ok', $changes ? 'Settings saved ('.count($changes).' changed).' : 'Nothing was changed.');
    }
}
