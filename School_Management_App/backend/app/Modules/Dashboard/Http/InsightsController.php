<?php

namespace App\Modules\Dashboard\Http;

use App\Core\Modules\ModuleRegistry;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Accounts\Models\Transaction;
use App\Modules\Admissions\Models\Enquiry;
use App\Modules\Attendance\Models\StudentAttendance;
use App\Modules\Fees\Models\Invoice;
use App\Modules\Fees\Models\Payment;
use App\Modules\Exams\Models\Mark;
use App\Modules\Homework\Models\Homework;
use App\Modules\Library\Models\BookIssue;
use App\Modules\Students\Models\Student;
use App\Modules\Settings\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Dashboard cards + charts (role-aware) and global search. */
class InsightsController extends Controller
{
    public function charts(Request $request)
    {
        $u = $request->user();
        $cards = []; $charts = [];
        $months = collect(range(5, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $principal = Setting::get('principal_name', Setting::get('principal'));

        if (in_array($u->role, ['admin', 'accountant', 'teacher', 'librarian', 'staff'], true)) {
            $today = now()->toDateString();
            $att = StudentAttendance::where('date', $today)->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');
            $present = ($att['present'] ?? 0) + ($att['late'] ?? 0);
            $cards = [
                ['label' => 'Students', 'value' => Student::where('status', 'active')->count(), 'icon' => 'school', 'color' => '#3949AB'],
                ['label' => 'Teachers', 'value' => User::where('role', 'teacher')->count(), 'icon' => 'co_present', 'color' => '#00897B'],
                ['label' => 'Staff', 'value' => User::whereIn('role', User::STAFF_ROLES)->count(), 'icon' => 'badge', 'color' => '#5E35B1'],
                ['label' => 'Present today', 'value' => $att->sum() ? round($present * 100 / $att->sum()).'%' : '—', 'icon' => 'fact_check', 'color' => '#43A047'],
            ];
            if (in_array($u->role, ['admin', 'accountant'], true)) {
                $cards[] = ['label' => 'Fees this month', 'value' => '₹'.number_format((float) Payment::where('status', '!=', 'cancelled')->where('paid_on', '>=', now()->startOfMonth()->toDateString())->sum('amount')), 'icon' => 'payments', 'color' => '#2E7D32'];
                $cards[] = ['label' => 'Fees due', 'value' => '₹'.number_format((float) Invoice::where('status', '!=', 'paid')->get()->sum('balance')), 'icon' => 'pending_actions', 'color' => '#E53935'];
                $cards[] = ['label' => 'New enquiries', 'value' => Enquiry::where('status', 'new')->count(), 'icon' => 'person_add', 'color' => '#FB8C00'];
                $charts[] = ['id' => 'fees', 'title' => 'Fee collection (last 6 months)', 'type' => 'bar', 'labels' => $months->map->format('M')->all(),
                    'datasets' => [['label' => 'Collected ₹', 'data' => $months->map(fn ($m) => round((float) Payment::where('status', '!=', 'cancelled')->whereBetween('paid_on', [$m->toDateString(), $m->copy()->endOfMonth()->toDateString()])->sum('amount')))->all()]]];
                $charts[] = ['id' => 'income_expense', 'title' => 'Income vs expense', 'type' => 'line', 'labels' => $months->map->format('M')->all(),
                    'datasets' => [
                        ['label' => 'Income ₹', 'data' => $months->map(fn ($m) => round((float) Transaction::where('type', 'income')->whereBetween('date', [$m->toDateString(), $m->copy()->endOfMonth()->toDateString()])->sum('amount') + (float) Payment::where('status', '!=', 'cancelled')->whereBetween('paid_on', [$m->toDateString(), $m->copy()->endOfMonth()->toDateString()])->sum('amount')))->all()],
                        ['label' => 'Expense ₹', 'data' => $months->map(fn ($m) => round((float) Transaction::where('type', 'expense')->whereBetween('date', [$m->toDateString(), $m->copy()->endOfMonth()->toDateString()])->sum('amount')))->all()],
                    ]];
                $st = Invoice::selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');
                $charts[] = ['id' => 'invoice_status', 'title' => 'Invoices by status', 'type' => 'doughnut', 'labels' => ['Paid', 'Part paid', 'Unpaid'],
                    'datasets' => [['label' => 'Invoices', 'data' => [(int) ($st['paid'] ?? 0), (int) ($st['partial'] ?? 0), (int) ($st['unpaid'] ?? 0)]]]];
            }
            $days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i))->reject(fn ($d) => $d->isSunday())->values();
            $charts[] = ['id' => 'attendance', 'title' => 'Student attendance % (last days)', 'type' => 'line', 'labels' => $days->map->format('d M')->all(),
                'datasets' => [['label' => 'Present %', 'data' => $days->map(function ($d) {
                    $a = StudentAttendance::where('date', $d->toDateString())->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');
                    return $a->sum() ? round((($a['present'] ?? 0) + ($a['late'] ?? 0)) * 100 / $a->sum(), 1) : 0;
                })->all()]]];
            $byClass = Student::where('status', 'active')->with('schoolClass')->get()->groupBy(fn ($s) => $s->schoolClass?->name)->map->count();
            $charts[] = ['id' => 'strength', 'title' => 'Students per class', 'type' => 'bar', 'labels' => $byClass->keys()->values()->all(),
                'datasets' => [['label' => 'Students', 'data' => $byClass->values()->all()]]];
            $g = Student::where('status', 'active')->join('users', 'users.id', '=', 'students.user_id')->selectRaw('users.gender g, count(*) c')->groupBy('users.gender')->pluck('c', 'g');
            $charts[] = ['id' => 'gender', 'title' => 'Boys / girls', 'type' => 'pie', 'labels' => ['Boys', 'Girls'], 'datasets' => [['label' => 'Students', 'data' => [(int) ($g['male'] ?? 0), (int) ($g['female'] ?? 0)]]]];
        } else {
            $student = $this->resolveOwnStudent($u, $request->query('student_id'));
            if ($student) {
                $att = StudentAttendance::where('student_id', $student->id)->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');
                $tot = $att->sum();
                $due = Invoice::where('student_id', $student->id)->get()->sum('balance');
                $cards = [
                    ['label' => 'Attendance', 'value' => $tot ? round((($att['present'] ?? 0) + ($att['late'] ?? 0)) * 100 / $tot).'%' : '—', 'icon' => 'event_available', 'color' => '#43A047'],
                    ['label' => 'Fee due', 'value' => '₹'.number_format((float) $due), 'icon' => 'currency_rupee', 'color' => '#E53935'],
                    ['label' => 'Homework this week', 'value' => Homework::where('class_id', $student->class_id)->where('assigned_on', '>=', now()->subDays(7)->toDateString())->count(), 'icon' => 'assignment', 'color' => '#8E24AA'],
                    ['label' => 'Books issued', 'value' => BookIssue::where('user_id', $student->user_id)->whereNull('returned_on')->count(), 'icon' => 'local_library', 'color' => '#6D4C41'],
                ];
                $charts[] = ['id' => 'my_attendance', 'title' => 'Attendance', 'type' => 'doughnut', 'labels' => ['Present', 'Absent', 'Late', 'Leave'],
                    'datasets' => [['label' => 'Days', 'data' => [(int) ($att['present'] ?? 0), (int) ($att['absent'] ?? 0), (int) ($att['late'] ?? 0), (int) ($att['leave'] ?? 0)]]]];
                $marks = Mark::where('student_id', $student->id)->whereHas('exam', fn ($e) => $e->where('is_published', true))->with('exam', 'subject')->get();
                $byExam = $marks->groupBy(fn ($m) => $m->exam?->name);
                $charts[] = ['id' => 'my_results', 'title' => 'Result % by exam', 'type' => 'bar', 'labels' => $byExam->keys()->values()->all(),
                    'datasets' => [['label' => '%', 'data' => $byExam->map(fn ($g) => $g->sum('max_marks') ? round($g->sum('marks_obtained') * 100 / $g->sum('max_marks'), 1) : 0)->values()->all()]]];
            }
        }
        return response()->json(['data' => ['principal_name' => $principal, 'cards' => $cards, 'charts' => $charts], 'cards' => $cards, 'charts' => $charts, 'principal_name' => $principal]);
    }

    public function search(Request $request)
    {
        $u = $request->user();
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) return $this->ok([]);
        $out = [];
        $targets = ['students', 'teachers', 'staff', 'parents', 'enquiries', 'books', 'invoices', 'payments', 'certificates', 'notices', 'visitors', 'inventory_items', 'assets', 'transactions'];
        foreach ($targets as $key) {
            $def = ModuleRegistry::get($key);
            if (! $def || ! ModuleRegistry::can($def, $u, 'view')) continue;
            $query = \App\Core\Modules\RecordsController::baseQuery($def, $u);
            \App\Core\Modules\RecordsController::applySearch($query, $def, $q);
            $rows = \App\Core\Modules\RecordsController::rows($def, $query->limit(6)->get(), $u);
            foreach ($rows as $r) $out[] = ['module' => $key, 'module_title' => $def['title'], 'id' => $r['id'], 'title' => $r['_title'], 'subtitle' => $r['_subtitle'], 'screen' => $key];
        }
        return $this->ok($out);
    }
}
