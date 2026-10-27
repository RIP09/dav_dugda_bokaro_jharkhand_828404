<?php

namespace App\Core\Modules;

use App\Core\Exceptions\RecordLockedException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Audit\Models\AuditLog;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * One generic engine for every module: list/search/filter, view, create, edit, delete, history, actions.
 * Role rules, row scopes, permanent (locked) records and immutable fields are all enforced here and in the models.
 */
class RecordsController extends Controller
{
    public function index(Request $request, string $module)
    {
        [$def, $user] = $this->def($request, $module, 'view');
        $q = $this->query($def, $user);
        $this->applySearch($q, $def, (string) $request->query('q', ''));
        $this->applyFilters($q, $def, (array) $request->query('filter', []));
        $this->applySort($q, $def, (string) $request->query('sort', $def['sort']));
        $per = min(500, max(1, (int) $request->query('per_page', 25)));
        $page = $q->paginate($per);
        $rows = $this->rows($def, $page->getCollection(), $user);
        return response()->json(['data' => $rows, 'meta' => [
            'total' => $page->total(), 'page' => $page->currentPage(), 'per_page' => $page->perPage(), 'last_page' => $page->lastPage(),
        ]]);
    }

    public function show(Request $request, string $module, int $id)
    {
        [$def, $user] = $this->def($request, $module, 'view');
        $m = $this->find($def, $user, $id);
        return $this->ok($this->rows($def, collect([$m]), $user)[0]);
    }

    public function store(Request $request, string $module)
    {
        [$def, $user] = $this->def($request, $module, 'create');
        $data = $this->validated($def, $request->all(), null);
        $m = DB::transaction(function () use ($def, $data, $request, $user) {
            if (isset($def['create'])) return ($def['create'])($data, $request, $user);
            return $this->saveModel($def, new $def['model'], $data, $user, true);
        });
        $m = $this->find($def, $user, $m->getKey(), false);
        if (isset($def['after_create'])) ($def['after_create'])($m, $user);
        return $this->created($this->rows($def, collect([$m]), $user)[0], $def['title_singular'] ?? 'Saved');
    }

    public function update(Request $request, string $module, int $id)
    {
        [$def, $user] = $this->def($request, $module, 'view');
        $m = $this->find($def, $user, $id);
        if ($reason = $m->originalLockReason()) throw new RecordLockedException($reason);
        $this->def($request, $module, 'update');
        if (isset($def['can_edit']) && ! ($def['can_edit'])($m, $user)) abort(403, 'You can only edit your own records here.');
        $data = $this->validated($def, $request->all(), $m);
        foreach ($def['fields'] as $f) {
            if (($f['immutable'] ?? false) && array_key_exists($f['name'], $data) && (string) $this->value($m, $f) !== '' && (string) $data[$f['name']] !== (string) $this->value($m, $f)) {
                throw ValidationException::withMessages([$f['name'] => "“{$f['label']}” is permanent and cannot be changed once set."]);
            }
        }
        DB::transaction(function () use ($def, $m, $data, $request, $user) {
            if (isset($def['update'])) return ($def['update'])($m, $data, $request, $user);
            return $this->saveModel($def, $m, $data, $user, false);
        });
        $m = $this->find($def, $user, $id, false);
        return $this->ok($this->rows($def, collect([$m]), $user)[0], 'Changes saved');
    }

    public function destroy(Request $request, string $module, int $id)
    {
        [$def, $user] = $this->def($request, $module, 'view');
        $m = $this->find($def, $user, $id);
        if ($reason = $m->originalLockReason()) throw new RecordLockedException($reason);
        $this->def($request, $module, 'delete');
        if (isset($def['can_delete']) && ! ($def['can_delete'])($m, $user)) abort(403, 'You cannot delete this record.');
        DB::transaction(function () use ($def, $m, $user) {
            if (isset($def['delete'])) return ($def['delete'])($m, $user);
            $m->delete();
        });
        return $this->ok(null, 'Deleted');
    }

    public function history(Request $request, string $module, int $id)
    {
        [$def, $user] = $this->def($request, $module, 'view');
        abort_unless($user->isAdmin() || ModuleRegistry::can($def, $user, 'update'), 403, 'Only the office can see change history.');
        $m = $this->find($def, $user, $id);
        $pairs = [[$m->getTable(), $m->getKey()]];
        foreach ($def['fields'] as $f) {
            if (! empty($f['on']) && ($rel = $m->{$f['on']})) $pairs[$rel->getTable().':'.$rel->getKey()] = [$rel->getTable(), $rel->getKey()];
        }
        $q = AuditLog::query()->where(function ($w) use ($pairs) {
            foreach ($pairs as [$t, $rid]) $w->orWhere(fn ($x) => $x->where('table_name', $t)->where('record_id', $rid));
        })->orderByDesc('id')->limit(200);
        return $this->ok($q->get()->map(fn ($a) => self::auditRow($a))->values());
    }

    public function action(Request $request, string $module, int $id, string $action)
    {
        [$def, $user] = $this->def($request, $module, 'view');
        $act = $def['actions'][$action] ?? abort(404, 'Unknown action');
        abort_unless(in_array($user->role, ModuleRegistry::expandRoles($act['roles'] ?? []), true), 403, 'You are not allowed to do this.');
        $m = $this->find($def, $user, $id);
        if (isset($act['when']) && ! ($act['when'])($m)) abort(422, 'This action is not available for this record now.');
        if (! empty($act['reason'])) $request->validate(['reason' => 'required|string|min:3|max:200']);
        $result = DB::transaction(fn () => ($act['run'])($m, $request, $user));
        $message = is_array($result) ? ($result['message'] ?? 'Done') : 'Done';
        $fresh = $def['model']::find($id);
        $data = $fresh ? $this->rows($def, collect([$this->find($def, $user, $id, false)]), $user)[0] : null;
        if (is_array($result) && array_key_exists('data', $result)) $data = $result['data'];
        return $this->ok($data, $message);
    }

    // ---------------------------------------------------------------------------------------------

    /** @return array{0: array, 1: User} */
    protected function def(Request $request, string $module, string $perm): array
    {
        $def = ModuleRegistry::get($module) ?? abort(404, "Unknown module “{$module}”.");
        $user = $request->user();
        abort_unless(ModuleRegistry::can($def, $user, $perm), 403, 'Your account is not allowed to '.$perm.' '.$def['title'].'.');
        return [$def, $user];
    }

    public static function baseQuery(array $def, User $user): Builder
    {
        $q = $def['model']::query()->with($def['with']);
        if (isset($def['base'])) ($def['base'])($q);
        if (isset($def['scope'])) ($def['scope'])($q, $user);
        return $q;
    }

    protected function query(array $def, User $user): Builder { return self::baseQuery($def, $user); }

    protected function find(array $def, User $user, $id, bool $scoped = true): Model
    {
        $q = $scoped ? $this->query($def, $user) : $def['model']::query()->with($def['with']);
        return $q->whereKey($id)->firstOr(fn () => abort(404, 'Record not found (or not yours to see).'));
    }

    public static function applySearch(Builder $q, array $def, string $term): void
    {
        $term = trim($term);
        if ($term === '' || ! $def['search']) return;
        $q->where(function ($w) use ($def, $term) {
            foreach ($def['search'] as $col) {
                if (str_contains($col, '.')) {
                    [$rel, $c] = explode('.', $col, 2);
                    $w->orWhereHas($rel, fn ($r) => $r->where($c, 'like', "%{$term}%"));
                } else {
                    $w->orWhere($w->getModel()->getTable().'.'.$col, 'like', "%{$term}%");
                }
            }
        });
    }

    public static function applyFilters(Builder $q, array $def, array $filters): void
    {
        $fields = collect($def['fields'])->keyBy('name');
        foreach ($filters as $name => $value) {
            if ($value === '' || $value === null) continue;
            $f = $fields[$name] ?? null;
            if (! $f) {
                if (isset($def['filters'][$name])) ($def['filters'][$name])($q, $value);
                continue;
            }
            if (! empty($f['virtual'])) continue;
            if ($f['type'] === 'bool') $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            if (! empty($f['on'])) $q->whereHas($f['on'], fn ($r) => $r->where($f['column'] ?? $name, $value));
            elseif (in_array($f['type'], ['date', 'datetime'], true) && strlen((string) $value) === 7) $q->where($name, 'like', $value.'%');
            else $q->where($q->getModel()->getTable().'.'.($f['column'] ?? $name), $value);
        }
    }

    protected function applySort(Builder $q, array $def, string $sort): void
    {
        $desc = str_starts_with($sort, '-');
        $col = ltrim($sort, '-');
        $allowed = collect($def['fields'])->filter(fn ($f) => empty($f['on']) && empty($f['virtual']))->pluck('name')->push('id', 'created_at', 'updated_at')->all();
        if (! in_array($col, $allowed, true)) { $col = 'id'; $desc = true; }
        $q->orderBy($q->getModel()->getTable().'.'.$col, $desc ? 'desc' : 'asc');
    }

    protected function validated(array $def, array $input, ?Model $m): array
    {
        $rules = [];
        $labels = [];
        foreach ($def['fields'] as $f) {
            if ($f['readonly'] || $f['type'] === 'json' || ! empty($f['virtual_readonly'])) continue;
            if ($m && ! empty($f['create_only'])) continue;
            $n = $f['name'];
            $r = $m ? ['sometimes'] : [];
            $r[] = $f['required'] ? 'required' : 'nullable';
            $r = array_merge($r, match ($f['type']) {
                'text' => ['string', 'max:'.($f['max'] ?? 255)],
                'textarea' => ['string', 'max:20000'],
                'number' => ['numeric'],
                'money' => ['numeric', 'min:0'],
                'date' => ['date'],
                'datetime' => ['date'],
                'time' => ['string', 'max:8'],
                'bool' => ['boolean'],
                'email' => ['email', 'max:120'],
                'phone' => ['string', 'max:20'],
                'select' => [Rule::in(array_column($f['options'] ?? [], 'value'))],
                'ref' => ['integer', $this->existsRule($f)],
                'file', 'image' => ['string', 'max:255'],
                default => [],
            });
            if (! empty($f['unique'])) {
                $table = ! empty($f['on']) ? (new $def['model'])->{$f['on']}()->getRelated()->getTable() : (new $def['model'])->getTable();
                $ignore = $m ? (! empty($f['on']) ? $m->{$f['on']}?->getKey() : $m->getKey()) : null;
                $r[] = Rule::unique($table, $f['column'] ?? $n)->ignore($ignore);
            }
            if (! empty($f['rules'])) $r = array_merge($r, (array) $f['rules']);
            $rules[$n] = $r;
            $labels[$n] = $f['label'];
        }
        foreach (($def['extra_rules'] ?? []) as $n => $r) $rules[$n] = $r;
        $v = Validator::make($input, $rules, [], $labels);
        $data = $v->validate();
        if (isset($def['validate'])) ($def['validate'])($data, $m);
        return $data;
    }

    protected function existsRule(array $f)
    {
        $ref = ModuleRegistry::get($f['ref']['module']);
        $table = $ref ? (new $ref['model'])->getTable() : $f['ref']['module'];
        return Rule::exists($table, 'id');
    }

    /** Generic save: own columns on the model, fields with 'on' => relation saved on the related model. */
    protected function saveModel(array $def, Model $m, array $data, User $user, bool $creating): Model
    {
        $own = []; $related = [];
        foreach ($def['fields'] as $f) {
            if (! array_key_exists($f['name'], $data) || ! empty($f['virtual'])) continue;
            $val = $data[$f['name']];
            if ($f['type'] === 'bool') $val = (bool) $val;
            if (! empty($f['on'])) $related[$f['on']][$f['column'] ?? $f['name']] = $val;
            else $own[$f['column'] ?? $f['name']] = $val;
        }
        $m->fill($own);
        if (isset($def['before_save'])) ($def['before_save'])($m, $data, $user, $creating);
        $m->save();
        foreach ($related as $rel => $vals) {
            if ($r = $m->{$rel}) $r->fill($vals)->save();
        }
        if (isset($def['after_save'])) ($def['after_save'])($m, $data, $user, $creating);
        return $m;
    }

    public static function value(Model $m, array $f)
    {
        if (isset($f['get'])) return ($f['get'])($m);
        $src = ! empty($f['on']) ? $m->{$f['on']} : $m;
        if (! $src) return null;
        $v = $src->getAttribute($f['column'] ?? $f['name']);
        if ($v instanceof CarbonInterface) $v = $f['type'] === 'datetime' ? $v->format('Y-m-d H:i') : ($f['type'] === 'date' ? $v->format('Y-m-d') : $v->toIso8601String());
        if ($f['type'] === 'bool') $v = (bool) $v;
        if (in_array($f['type'], ['money', 'number'], true) && $v !== null && is_numeric($v)) $v = $v + 0;
        return $v;
    }

    /** Shape models into API rows: fields, ref labels, file URLs, title/subtitle, lock state, permissions, actions. */
    public static function rows(array $def, $models, User $user): array
    {
        $models = collect($models);
        $refLabels = [];
        foreach ($def['fields'] as $f) {
            if ($f['type'] !== 'ref') continue;
            $ids = $models->map(fn ($m) => self::value($m, $f))->filter()->unique()->values();
            $refLabels[$f['name']] = $ids->isEmpty() ? [] : self::labelsFor($f['ref']['module'], $ids->all());
        }
        $canUpdate = ModuleRegistry::can($def, $user, 'update');
        $canDelete = ModuleRegistry::can($def, $user, 'delete');
        return $models->map(function (Model $m) use ($def, $refLabels, $user, $canUpdate, $canDelete) {
            $row = ['id' => $m->getKey()];
            foreach ($def['fields'] as $f) {
                if (! empty($f['write_only'])) continue;
                $v = self::value($m, $f);
                $row[$f['name']] = $v;
                if ($f['type'] === 'ref') $row[$f['name'].'_label'] = $v ? ($refLabels[$f['name']][$v] ?? null) : null;
                if (in_array($f['type'], ['file', 'image'], true)) $row[$f['name'].'_url'] = $v ? (str_starts_with($v, 'http') ? $v : url('storage/'.$v)) : null;
            }
            $lock = method_exists($m, 'lockReason') ? $m->lockReason() : null;
            $row['_title'] = ModuleRegistry::titleFor($def['key'], $m);
            $row['_subtitle'] = collect($def['subtitle'])->map(fn ($s) => $row[$s.'_label'] ?? $row[$s] ?? null)
                ->filter(fn ($x) => $x !== null && $x !== '')->map(fn ($x) => is_bool($x) ? ($x ? 'Yes' : 'No') : (string) $x)->implode(' · ');
            $row['_locked'] = (bool) $lock;
            $row['_lock_reason'] = $lock;
            $row['_can_edit'] = ! $lock && $canUpdate && (! isset($def['can_edit']) || ($def['can_edit'])($m, $user));
            $row['_can_delete'] = ! $lock && $canDelete && (! isset($def['can_delete']) || ($def['can_delete'])($m, $user));
            $row['_actions'] = collect($def['actions'])->filter(fn ($a) => in_array($user->role, ModuleRegistry::expandRoles($a['roles'] ?? []), true)
                && (! isset($a['when']) || ($a['when'])($m)))->keys()->values()->all();
            $row['created_at'] = $m->created_at?->format('Y-m-d H:i');
            $row['updated_at'] = $m->updated_at?->format('Y-m-d H:i');
            if (isset($def['extra'])) $row = array_merge($row, ($def['extra'])($m, $user));
            return $row;
        })->values()->all();
    }

    public static function labelsFor(string $module, array $ids): array
    {
        $ref = ModuleRegistry::get($module);
        if (! $ref) return [];
        return $ref['model']::query()->with($ref['with'])->whereIn((new $ref['model'])->getKeyName(), $ids)->get()
            ->mapWithKeys(fn ($m) => [$m->getKey() => ModuleRegistry::titleFor($module, $m)])->all();
    }

    public static function auditRow(AuditLog $a): array
    {
        return [
            'id' => $a->id, 'user_id' => $a->user_id, 'user_name' => $a->user_name, 'action' => $a->action, 'module' => $a->module,
            'record_id' => $a->record_id, 'record_title' => $a->record_title, 'changes' => $a->changes ?: (object) [],
            'ip' => $a->ip, 'created_at' => $a->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
