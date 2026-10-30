<?php

namespace Website\Modules\Cms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Website\Support\ChangeLog;
use Website\Support\Html;
use Website\Support\Media;
use Website\Support\Registry;

/** One controller that gives every content type (Resource) its list / add / edit / delete screens. */
class ResourceController extends Controller
{
    protected function resource(string $key): string
    {
        $r = Registry::find($key);
        abort_unless($r, 404);

        return $r;
    }

    public function index(Request $request, string $key)
    {
        $r = $this->resource($key);
        $q = $r::query();
        if ($s = trim((string) $request->query('q'))) {
            $q->where(function ($w) use ($r, $s) {
                foreach ($r::$searchable as $col) {
                    $w->orWhere($col, 'like', '%'.$s.'%');
                }
            });
        }
        foreach ($r::filters() as $col => $opts) {
            if (($v = $request->query($col)) !== null && $v !== '' && array_key_exists($v, $opts)) {
                $q->where($col, $v);
            }
        }
        $q->orderBy($r::$orderBy, $r::$orderDir)->orderBy('id', 'desc');
        $items = $q->with('editor')->paginate(25)->withQueryString();

        return view('website::cms.resource.index', ['r' => $r, 'items' => $items]);
    }

    public function create(string $key)
    {
        $r = $this->resource($key);
        abort_unless($r::$canCreate, 403);
        $model = new ($r::$model)();
        foreach ($r::fields() as $f) {
            if (array_key_exists('default', $f)) {
                $model->{$f['name']} = $f['default'] === 'today' ? now()->toDateString() : $f['default'];
            }
        }

        return view('website::cms.resource.form', ['r' => $r, 'item' => $model, 'history' => collect()]);
    }

    public function store(Request $request, string $key)
    {
        $r = $this->resource($key);
        abort_unless($r::$canCreate, 403);
        $model = new ($r::$model)();
        $this->fill($request, $r, $model);
        $model->save();
        $r::saved($model);
        ChangeLog::record('created', $r::$key, $model, [], $r::title($model));

        return $this->afterSave($request, $r, $model, ucfirst($r::$singular).' added.');
    }

    public function edit(string $key, int $id)
    {
        $r = $this->resource($key);
        $item = $r::query()->findOrFail($id);
        $history = DB::table('site_change_log')->where('module', $r::$key)->where('record_id', $id)->orderByDesc('id')->limit(15)->get();

        return view('website::cms.resource.form', ['r' => $r, 'item' => $item, 'history' => $history]);
    }

    public function update(Request $request, string $key, int $id)
    {
        $r = $this->resource($key);
        $model = ($r::$model)::findOrFail($id);
        $this->fill($request, $r, $model);
        $dirty = $model->isDirty();
        $model->save();
        $r::saved($model);
        if ($dirty) {
            ChangeLog::record('updated', $r::$key, $model, ChangeLog::diff($model), $r::title($model));
        }

        return $this->afterSave($request, $r, $model, $dirty ? 'Changes saved.' : 'Nothing was changed.');
    }

    public function destroy(string $key, int $id)
    {
        $r = $this->resource($key);
        $model = ($r::$model)::findOrFail($id);
        if (! $r::deletable($model)) {
            return back()->with('err', 'This '.$r::$singular.' is built into the website and cannot be deleted. You can hide it instead.');
        }
        foreach ($r::fields() as $f) {
            if (in_array($f['type'], ['image', 'file'], true)) {
                Media::delete($model->{$f['name']});
            }
        }
        ChangeLog::record('deleted', $r::$key, $model, [], $r::title($model));
        $model->delete();

        return redirect()->route('webadmin.r.index', $r::$key)->with('ok', ucfirst($r::$singular).' deleted.');
    }

    protected function afterSave(Request $request, string $r, Model $model, string $msg)
    {
        if ($request->input('_after') === 'new') {
            return redirect()->route('webadmin.r.create', $r::$key)->with('ok', $msg);
        }

        return redirect()->route('webadmin.r.edit', [$r::$key, $model->getKey()])->with('ok', $msg);
    }

    protected function fill(Request $request, string $r, Model $model): void
    {
        $rules = [];
        foreach ($r::fields() as $f) {
            $n = $f['name'];
            $req = ! empty($f['required']);
            $rules[$n] = match ($f['type']) {
                'image' => [($req && ! $model->{$n}) ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif,svg', 'max:10240'],
                'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip,txt,csv', 'max:20480'],
                'number' => [$req ? 'required' : 'nullable', 'integer', 'min:-100000', 'max:100000000'],
                'date', 'datetime' => [$req ? 'required' : 'nullable', 'date'],
                'email' => [$req ? 'required' : 'nullable', 'email', 'max:150'],
                'toggle' => ['nullable'],
                'select', 'icon' => [$req ? 'required' : 'nullable', 'in:'.implode(',', array_map(fn ($k) => str_replace(',', '\,', (string) $k), array_keys($f['options'])))],
                'rich' => [$req ? 'required' : 'nullable', 'string', 'max:500000'],
                'textarea' => [$req ? 'required' : 'nullable', 'string', 'max:20000'],
                'slug' => ['nullable', 'string', 'max:200'],
                default => [$req ? 'required' : 'nullable', 'string', 'max:500'],
            };
        }
        $labels = collect($r::fields())->mapWithKeys(fn ($f) => [$f['name'] => Str::lower(Str::before($f['label'], ' ('))])->all();
        $request->validate($rules, [], $labels);

        foreach ($r::fields() as $f) {
            $n = $f['name'];
            switch ($f['type']) {
                case 'image':
                case 'file':
                    if ($request->boolean('_remove_'.$n)) {
                        Media::delete($model->{$n});
                        $model->{$n} = null;
                    }
                    if ($request->hasFile($n)) {
                        Media::delete($model->{$n});
                        $up = $request->file($n);
                        $model->{$n} = ($f['type'] === 'image' && $up->getMimeType() !== 'image/svg+xml' && $up->getMimeType() !== 'image/gif')
                            ? Media::storeImage($up)
                            : Media::storeFile($up);
                    }
                    break;
                case 'toggle':
                    $model->{$n} = $request->boolean($n);
                    break;
                case 'rich':
                    $model->{$n} = Html::clean($request->input($n));
                    break;
                case 'number':
                    $v = $request->input($n);
                    $model->{$n} = ($v === null || $v === '') ? ($f['default'] ?? null) : (int) $v;
                    break;
                case 'datetime':
                case 'date':
                    $v = $request->input($n);
                    $model->{$n} = $v ? Carbon::parse($v) : null;
                    break;
                case 'slug':
                    break; // handled below
                case 'select':
                    $v = $request->input($n);
                    $model->{$n} = ($v === '' ? null : $v);
                    break;
                default:
                    $model->{$n} = $request->input($n);
            }
        }
        foreach ($r::fields() as $f) {
            if ($f['type'] === 'slug') {
                $n = $f['name'];
                $base = Str::slug($request->input($n) ?: $model->{$f['from']}) ?: Str::random(6);
                $slug = $base;
                $i = 2;
                while (($r::$model)::where($n, $slug)->when($model->exists, fn ($q) => $q->where('id', '!=', $model->getKey()))->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $model->{$n} = $slug;
            }
        }
        if (in_array('updated_by', $model->getConnection()->getSchemaBuilder()->getColumnListing($model->getTable()), true)) {
            $model->updated_by = Auth::guard('webadmin')->id();
        }
    }
}
