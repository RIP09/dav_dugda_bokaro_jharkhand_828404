<?php

namespace App\Core\Modules;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Every management area declares its modules in app/Modules/<Module>/module.php.
 * The registry feeds the generic API (meta/modules, records/{module}) used by every app.
 */
class ModuleRegistry
{
    private static ?array $defs = null;

    public const ROLE_GROUPS = [
        'all' => ['admin', 'teacher', 'accountant', 'librarian', 'staff', 'parent', 'student'],
        'staff' => ['teacher', 'accountant', 'librarian', 'staff'],
        'employees' => ['admin', 'teacher', 'accountant', 'librarian', 'staff'],
        'office' => ['admin', 'accountant', 'staff'],
        'family' => ['parent', 'student'],
    ];

    public static function all(): array
    {
        if (self::$defs === null) {
            self::$defs = [];
            foreach (glob(app_path('Modules/*/module.php')) as $file) {
                $folder = basename(dirname($file));
                foreach ((require $file) as $def) {
                    $def['folder'] = $folder;
                    $def += ['group' => $folder, 'icon' => 'apps', 'label' => 'name', 'subtitle' => [], 'search' => [], 'sort' => '-id',
                        'with' => [], 'roles' => [], 'actions' => [], 'lockable' => false, 'lock_note' => null, 'import' => false,
                        'import_key' => null, 'order' => 100];
                    foreach ($def['fields'] as &$f) $f = array_merge(F::make($f['name'], $f['label']), $f);
                    unset($f);
                    self::$defs[$def['key']] = $def;
                }
            }
            uasort(self::$defs, fn ($a, $b) => $a['order'] <=> $b['order']);
        }
        return self::$defs;
    }

    public static function get(string $key): ?array { return self::all()[$key] ?? null; }

    public static function expandRoles(array|string $roles): array
    {
        $out = ['admin'];
        foreach ((array) $roles as $r) $out = array_merge($out, self::ROLE_GROUPS[$r] ?? [$r]);
        return array_values(array_unique($out));
    }

    public static function can(array $def, User $user, string $perm): bool
    {
        $roles = $def['roles'][$perm] ?? ($perm === 'view' ? [] : null);
        if ($roles === null) $roles = $def['roles']['manage'] ?? [];
        if ($perm === 'export' && ! isset($def['roles']['export'])) return self::can($def, $user, 'view') && $user->role !== 'student' && $user->role !== 'parent';
        if ($perm === 'import') return $def['import'] && self::can($def, $user, 'create');
        if ($perm === 'delete' && ($def['no_delete'] ?? false)) return false;
        if ($perm === 'create' && ($def['no_create'] ?? false)) return false;
        if ($perm === 'update' && ($def['no_update'] ?? false)) return false;
        return in_array($user->role, self::expandRoles($roles), true);
    }

    /** Module key for a model instance (first module declaring that class). */
    public static function keyForModel(Model $m): ?string
    {
        if ($m instanceof User) return match ($m->role) { 'teacher' => 'teachers', 'parent' => 'parents', 'student' => 'students', default => 'staff' };
        foreach (self::all() as $k => $d) {
            if ($d['model'] === get_class($m) && empty($d['alias_of'])) return $k;
        }
        return null;
    }

    public static function titleFor(?string $key, Model $m): string
    {
        if ($m instanceof User) return (string) $m->name;
        $def = $key ? self::get($key) : null;
        if ($def && isset($def['title_fn'])) {
            try { return (string) ($def['title_fn'])($m); } catch (\Throwable) {}
        }
        $label = $def['label'] ?? 'name';
        return (string) ($m->getAttribute($label) ?? $m->getAttribute('title') ?? $m->getAttribute('name') ?? ('#'.$m->getKey()));
    }
}
