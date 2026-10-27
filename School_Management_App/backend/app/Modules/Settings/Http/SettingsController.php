<?php

namespace App\Modules\Settings\Http;

use App\Http\Controllers\Controller;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\Settings\Models\Setting;
use App\Modules\Students\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** School settings (name, logo, principal …), file uploads, student photos and full backups. */
class SettingsController extends Controller
{
    public const KEYS = ['school_name', 'school_short_name', 'address', 'phone', 'email', 'website', 'principal_name', 'board', 'affiliation', 'affiliation_no',
        'school_code', 'logo', 'current_session', 'receipt_prefix', 'late_fine_per_day', 'library_fine_per_day', 'library_issue_days', 'udise_code', 'motto'];

    public static function profile(): array
    {
        $s = Setting::allAsArray();
        $logo = $s['logo'] ?? null;
        return array_merge($s, [
            'name' => $s['school_name'] ?? 'School', 'short_name' => $s['school_short_name'] ?? null,
            'principal_name' => $s['principal_name'] ?? ($s['principal'] ?? null), 'principal' => $s['principal_name'] ?? ($s['principal'] ?? null),
            'logo_url' => $logo ? url('storage/'.$logo) : null,
            'current_session' => \App\Modules\Academics\Models\AcademicYear::current()?->title ?? ($s['current_session'] ?? null),
        ]);
    }

    public function show() { return $this->ok(self::profile()); }

    public function update(Request $request)
    {
        $rules = array_fill_keys(self::KEYS, 'nullable|string|max:500');
        $rules['email'] = 'nullable|email'; $rules['late_fine_per_day'] = 'nullable|numeric|min:0';
        $data = $request->validate($rules);
        $changes = [];
        foreach ($data as $k => $v) {
            $old = Setting::get($k);
            if ((string) $old === (string) $v) continue;
            Setting::put($k, $v);
            if ($k === 'principal_name') Setting::put('principal', $v);
            $changes[$k] = ['old' => $old, 'new' => $v];
        }
        if ($changes) AuditLog::record('update', new Setting(['key' => 'settings']), $changes);
        return $this->ok(self::profile(), 'Settings saved');
    }

    public function upload(Request $request)
    {
        $request->validate(['file' => 'required|file|max:10240|mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,csv,txt,ppt,pptx,zip', 'folder' => 'nullable|alpha_dash|max:30']);
        $folder = 'uploads/'.($request->input('folder') ?: date('Y-m'));
        $f = $request->file('file');
        $name = Str::slug(pathinfo($f->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'file';
        $path = $f->storeAs($folder, $name.'-'.Str::random(6).'.'.strtolower($f->getClientOriginalExtension() ?: 'bin'), 'public');
        return $this->created(['path' => $path, 'url' => url('storage/'.$path), 'name' => $f->getClientOriginalName(), 'size' => $f->getSize()], 'Uploaded');
    }

    public function studentPhoto(Request $request, Student $student)
    {
        $u = $request->user();
        abort_unless($u->isAdmin() || in_array($u->role, ['teacher', 'accountant', 'staff'], true) || in_array($student->id, $u->ownStudentIds(), true), 403);
        $request->validate(['photo' => 'required|image|max:5120']);
        $path = $request->file('photo')->store('photos', 'public');
        $student->user->update(['photo' => $path]);
        return $this->ok($student->fresh(['user', 'schoolClass', 'section']), 'Photo updated');
    }

    public function userPhoto(Request $request)
    {
        $request->validate(['photo' => 'required|image|max:5120']);
        $path = $request->file('photo')->store('photos', 'public');
        $request->user()->update(['photo' => $path]);
        return $this->ok(['photo' => $path, 'photo_url' => url('storage/'.$path)], 'Photo updated');
    }

    /** Zip with every table as JSON + CSV (and the SQLite file when used). Admin only. */
    public function backup()
    {
        $tmp = storage_path('app/backup-'.now()->format('Ymd-His').'.zip');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $skip = ['personal_access_tokens', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens'];
        $tables = collect(Schema::getTables())->pluck('name')->reject(fn ($t) => in_array($t, $skip, true) || str_starts_with($t, 'sqlite_'))->values();
        $manifest = ['school' => Setting::get('school_name'), 'created_at' => now()->toIso8601String(), 'tables' => []];
        foreach ($tables as $t) {
            $rows = DB::table($t)->get()->map(fn ($r) => (array) $r);
            if ($t === 'users') $rows = $rows->map(function ($r) { unset($r['remember_token']); return $r; });
            $zip->addFromString("json/{$t}.json", json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $fh = fopen('php://temp', 'r+');
            if ($rows->isNotEmpty()) { fputcsv($fh, array_keys($rows->first())); foreach ($rows as $r) fputcsv($fh, array_map(fn ($v) => is_scalar($v) || $v === null ? $v : json_encode($v), $r)); }
            rewind($fh); $zip->addFromString("csv/{$t}.csv", stream_get_contents($fh)); fclose($fh);
            $manifest['tables'][$t] = $rows->count();
        }
        if (config('database.default') === 'sqlite' && is_file($db = config('database.connections.sqlite.database'))) $zip->addFile($db, 'database.sqlite');
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
        $zip->addFromString('README.txt', "Backup of the DAV school management database.\njson/ and csv/ hold every table (open CSV in Excel). database.sqlite is the full database when SQLite is used.\nRestore on a server: php artisan sms:restore <this-zip> (see docs).");
        $zip->close();
        return response()->download($tmp, 'DAV_backup_'.now()->format('Y-m-d_His').'.zip')->deleteFileAfterSend();
    }
}
