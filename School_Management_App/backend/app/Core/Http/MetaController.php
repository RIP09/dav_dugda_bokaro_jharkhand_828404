<?php

namespace App\Core\Http;

use App\Core\Modules\ModuleRegistry;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

/** The module registry and the role-aware menu that every app renders. */
class MetaController extends Controller
{
    public const GROUP_ORDER = ['Home', 'Admissions', 'Students', 'Parents', 'Teachers', 'Staff', 'Attendance', 'Academics', 'Timetable', 'Homework',
        'Exams', 'Certificates', 'Fees', 'Accounts', 'Payroll', 'Library', 'Transport', 'Hostel', 'Inventory', 'Front Office', 'Communication', 'Reports', 'Settings'];

    public function modules(Request $request)
    {
        $user = $request->user();
        $out = [];
        foreach (ModuleRegistry::all() as $key => $def) {
            if (! ModuleRegistry::can($def, $user, 'view')) continue;
            $out[] = self::describe($def, $user);
        }
        return $this->ok($out);
    }

    public function module(Request $request, string $key)
    {
        $def = ModuleRegistry::get($key) ?? abort(404);
        abort_unless(ModuleRegistry::can($def, $request->user(), 'view'), 403);
        return $this->ok(self::describe($def, $request->user()));
    }

    public static function describe(array $def, User $user): array
    {
        $fields = array_map(function ($f) {
            $o = array_intersect_key($f, array_flip(['name', 'label', 'type', 'required', 'list', 'filter', 'readonly', 'immutable', 'options', 'ref', 'help', 'default']));
            if (! empty($f['virtual']) && empty($f['write_only'])) $o['readonly'] = $o['readonly'] || ! isset($f['get']) ? $o['readonly'] : $o['readonly'];
            if (! empty($f['write_only'])) $o['write_only'] = true;
            if ($f['type'] === 'ref' && ($r = ModuleRegistry::get($f['ref']['module']))) $o['ref']['label_field'] = $r['label'] ?? 'name';
            return $o;
        }, $def['fields']);
        $actions = [];
        foreach ($def['actions'] as $k => $a) {
            if (! in_array($user->role, ModuleRegistry::expandRoles($a['roles'] ?? []), true)) continue;
            $actions[] = array_filter([
                'key' => $k, 'label' => $a['label'], 'kind' => $a['kind'], 'confirm' => $a['confirm'] ?? null,
                'needs_reason' => ! empty($a['reason']), 'doc' => $a['doc'] ?? null, 'url' => $a['kind'] === 'pdf' ? 'pdf/'.$a['doc'].'/{id}' : 'records/'.$def['key'].'/{id}/actions/'.$k,
                'inputs' => isset($a['inputs']) ? array_map(fn ($f) => array_intersect_key($f, array_flip(['name', 'label', 'type', 'required', 'options', 'default', 'help'])), $a['inputs']) : null,
            ], fn ($v) => $v !== null);
        }
        return [
            'key' => $def['key'], 'title' => $def['title'], 'group' => $def['group'], 'folder' => $def['folder'], 'icon' => $def['icon'],
            'label_field' => $def['label'], 'subtitle_fields' => $def['subtitle'],
            'permissions' => [
                'view' => true, 'create' => ModuleRegistry::can($def, $user, 'create'), 'update' => ModuleRegistry::can($def, $user, 'update'),
                'delete' => ModuleRegistry::can($def, $user, 'delete'), 'export' => ModuleRegistry::can($def, $user, 'export'),
                'import' => ModuleRegistry::can($def, $user, 'import'), 'history' => $user->isAdmin() || ModuleRegistry::can($def, $user, 'update'),
            ],
            'lockable' => (bool) $def['lockable'], 'lock_note' => $def['lock_note'], 'fields' => $fields, 'actions' => $actions,
        ];
    }

    public function menu(Request $request)
    {
        $user = $request->user();
        $r = $user->role;
        $is = fn (...$roles) => in_array($r, ['admin', ...$roles], true);
        $custom = [
            ['Home', 'dashboard', 'Dashboard', 'dashboard', true],
            ['Home', 'notifications', 'Notifications', 'notifications', true],
            ['Home', 'search', 'Search', 'search', true],
            ['Attendance', 'attendance_take', 'Take Attendance', 'fact_check', $is('teacher')],
            ['Attendance', 'attendance_report', 'Attendance Report', 'insights', $is('teacher', 'staff', 'accountant', 'librarian')],
            ['Attendance', 'staff_attendance', 'Mark Staff Attendance', 'how_to_reg', $is()],
            ['Attendance', 'my_attendance', 'My Attendance', 'event_available', in_array($r, ['parent', 'student', 'teacher', 'accountant', 'librarian', 'staff'], true)],
            ['Timetable', 'timetable', 'Timetable', 'schedule', true],
            ['Exams', 'marks_entry', 'Enter Marks', 'edit_note', $is('teacher')],
            ['Exams', 'report_cards', 'Report Cards', 'military_tech', true],
            ['Fees', 'fee_collect', 'Collect Fees', 'point_of_sale', $is('accountant')],
            ['Fees', 'fee_generate', 'Generate Monthly Fees', 'auto_awesome', $is('accountant')],
            ['Fees', 'fee_reminders', 'Fee Reminders', 'notifications_active', $is('accountant')],
            ['Payroll', 'payroll_run', 'Run Payroll', 'paid', $is('accountant')],
            ['Certificates', 'promotion', 'Promote / New Session', 'trending_up', $is()],
            ['Communication', 'messages', 'Messages', 'forum', true],
            ['Communication', 'calendar', 'Calendar', 'calendar_month', true],
            ['Reports', 'reports', 'Reports & Charts', 'bar_chart', $is('accountant')],
            ['Settings', 'import', 'Import from Excel', 'upload_file', $is()],
            ['Settings', 'backup', 'Backup & Export', 'backup', $is()],
            ['Settings', 'settings', 'School Settings', 'settings', $is()],
            ['Settings', 'profile', 'My Profile', 'account_circle', true],
        ];
        $groups = [];
        foreach ($custom as [$g, $screen, $title, $icon, $show]) if ($show) $groups[$g][] = ['key' => $screen, 'type' => 'screen', 'title' => $title, 'icon' => $icon, 'screen' => $screen];
        $customKeys = array_column($custom, 1);
        foreach (ModuleRegistry::all() as $key => $def) {
            if (! ModuleRegistry::can($def, $user, 'view')) continue;
            // records screens whose key is also a custom screen (timetable, staff_attendance …) get a distinct 'module:' screen id
            $screen = in_array($key, $customKeys, true) ? 'module:'.$key : $key;
            $groups[$def['group']][] = ['key' => $screen, 'type' => 'module', 'module' => $key, 'title' => $def['title'], 'icon' => $def['icon'], 'screen' => $screen];
        }
        $out = [];
        foreach (self::GROUP_ORDER as $g) if (! empty($groups[$g])) { $out[] = ['group' => $g, 'items' => $groups[$g]]; unset($groups[$g]); }
        foreach ($groups as $g => $items) $out[] = ['group' => $g, 'items' => $items];
        return $this->ok($out);
    }
}
