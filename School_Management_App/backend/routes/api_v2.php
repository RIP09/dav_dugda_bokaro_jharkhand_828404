<?php

use App\Core\Http\MetaController;
use App\Core\Modules\RecordsController;
use App\Modules\Attendance\Http\AttendanceReportController;
use App\Modules\Certificates\Http\PromotionController;
use App\Modules\Communication\Http\NotificationController;
use App\Modules\Dashboard\Http\InsightsController;
use App\Modules\Fees\Http\FeeAutomationController;
use App\Modules\Payroll\Http\PayrollController;
use App\Modules\Reports\Http\DataController;
use App\Modules\Reports\Http\PdfController;
use App\Modules\Settings\Http\SettingsController;
use Illuminate\Support\Facades\Route;

/*
| Phase 2 API (inside /api/v1, auth:sanctum). Role checks for generic modules happen in the module registry.
| See docs/API_V2_CONTRACT.md.
*/

Route::get('meta/modules', [MetaController::class, 'modules']);
Route::get('meta/modules/{key}', [MetaController::class, 'module']);
Route::get('meta/menu', [MetaController::class, 'menu']);

Route::get('records/{module}', [RecordsController::class, 'index']);
Route::post('records/{module}', [RecordsController::class, 'store']);
Route::get('records/{module}/{id}', [RecordsController::class, 'show'])->whereNumber('id');
Route::put('records/{module}/{id}', [RecordsController::class, 'update'])->whereNumber('id');
Route::patch('records/{module}/{id}', [RecordsController::class, 'update'])->whereNumber('id');
Route::delete('records/{module}/{id}', [RecordsController::class, 'destroy'])->whereNumber('id');
Route::get('records/{module}/{id}/history', [RecordsController::class, 'history'])->whereNumber('id');
Route::post('records/{module}/{id}/actions/{action}', [RecordsController::class, 'action'])->whereNumber('id');

$all = 'role:admin,teacher,accountant,librarian,staff,parent,student';
$employees = 'role:admin,teacher,accountant,librarian,staff';
$office = 'role:admin,accountant';

Route::middleware($all)->group(function () {
    Route::get('dashboard/charts', [InsightsController::class, 'charts']);
    Route::get('search', [InsightsController::class, 'search']);
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread', [NotificationController::class, 'unread']);
    Route::post('notifications/read-all', [NotificationController::class, 'readAll']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'read'])->whereNumber('id');
    Route::post('notices/{notice}/read', [NotificationController::class, 'markNoticeRead']);
    Route::post('uploads', [SettingsController::class, 'upload']);
    Route::post('auth/photo', [SettingsController::class, 'userPhoto']);
    Route::post('students/{student}/photo', [SettingsController::class, 'studentPhoto']);
    Route::get('pdf/{doc}/{id?}', [PdfController::class, 'show'])->whereNumber('id');
    Route::get('export/{module}', [DataController::class, 'export']);
    Route::get('import/{module}/template', [DataController::class, 'template']);
    Route::post('import/{module}', [DataController::class, 'import']);
});
Route::middleware($employees)->group(function () {
    Route::get('notices/{notice}/reads', [NotificationController::class, 'noticeReads']);
    Route::get('attendance/report', [AttendanceReportController::class, 'report']);
    Route::get('attendance/daily', [AttendanceReportController::class, 'daily']);
});
Route::middleware($office)->group(function () {
    Route::post('fees/generate', [FeeAutomationController::class, 'generate']);
    Route::post('fees/reminders', [FeeAutomationController::class, 'reminders']);
    Route::get('fees/reminders', [FeeAutomationController::class, 'reminders']);
    Route::post('payroll/run', [PayrollController::class, 'run']);
});
Route::middleware('role:admin')->group(function () {
    Route::get('settings', [SettingsController::class, 'show']);
    Route::put('settings', [SettingsController::class, 'update']);
    Route::get('promotion/preview', [PromotionController::class, 'preview']);
    Route::post('promotion/run', [PromotionController::class, 'run']);
    Route::get('backup/download', [SettingsController::class, 'backup']);
    Route::get('audit', function (\Illuminate\Http\Request $r) {
        $q = \App\Modules\Audit\Models\AuditLog::query()->orderByDesc('id');
        foreach (['module', 'user_id', 'action', 'record_id'] as $k) if ($r->filled($k)) $q->where($k, $r->query($k));
        if ($r->filled('from')) $q->where('created_at', '>=', $r->query('from'));
        if ($r->filled('to')) $q->where('created_at', '<=', $r->query('to').' 23:59:59');
        $p = $q->paginate(min(200, (int) $r->query('per_page', 50)));
        return response()->json(['data' => collect($p->items())->map(fn ($a) => RecordsController::auditRow($a)), 'meta' => ['total' => $p->total(), 'page' => $p->currentPage(), 'per_page' => $p->perPage(), 'last_page' => $p->lastPage()]]);
    });
});
