<?php

namespace App\Modules\Fees\Http;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\AcademicYear;
use App\Modules\Communication\Models\AppNotification;
use App\Modules\Fees\Models\Concession;
use App\Modules\Fees\Models\FeeStructure;
use App\Modules\Fees\Models\FeeType;
use App\Modules\Fees\Models\Invoice;
use App\Modules\Hostel\Models\HostelAllocation;
use App\Modules\Settings\Models\Setting;
use App\Modules\Students\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Monthly fee generation from the class-wise structure (minus concessions, plus transport/hostel) and due reminders. */
class FeeAutomationController extends Controller
{
    public function generate(Request $request)
    {
        $data = $request->validate(['month' => 'required|date_format:Y-m', 'class_id' => 'nullable|exists:school_classes,id', 'dry_run' => 'nullable|boolean']);
        $month = Carbon::parse($data['month'].'-01');
        $mNum = (int) $month->format('n');
        $applies = fn ($freq) => match ($freq) {
            'monthly' => true, 'quarterly' => in_array($mNum, [4, 7, 10, 1], true), 'half_yearly' => in_array($mNum, [4, 10], true),
            'yearly' => $mNum === 4, default => false,
        };
        $structures = FeeStructure::with('feeType')->when($data['class_id'] ?? null, fn ($q, $c) => $q->where('class_id', $c))->get()->groupBy('class_id');
        $students = Student::with('transportRoute')->where('status', 'active')->when($data['class_id'] ?? null, fn ($q, $c) => $q->where('class_id', $c))->get();
        $year = AcademicYear::current()?->id;
        $transportType = FeeType::firstOrCreate(['name' => 'Transport Fee'], ['default_amount' => 0]);
        $hostelType = FeeType::firstOrCreate(['name' => 'Hostel Fee'], ['default_amount' => 0]);
        $plan = [];
        foreach ($students as $s) {
            $items = [];
            foreach (($structures[$s->class_id] ?? collect()) as $fs) if ($applies($fs->frequency)) $items[] = [$fs->fee_type_id, $fs->feeType?->name, (float) $fs->amount, $fs->due_day];
            if ($s->transportRoute && $s->transportRoute->fare > 0) $items[] = [$transportType->id, 'Transport Fee', (float) $s->transportRoute->fare, 10];
            $alloc = HostelAllocation::with('room')->where('student_id', $s->id)->where('status', 'active')->first();
            if ($alloc && $alloc->room?->fee_per_month > 0) $items[] = [$hostelType->id, 'Hostel Fee', (float) $alloc->room->fee_per_month, 10];
            $conc = Concession::where('student_id', $s->id)->where(fn ($q) => $q->whereNull('valid_till')->orWhere('valid_till', '>=', $month->toDateString()))->get();
            foreach ($items as [$typeId, $name, $amount, $dueDay]) {
                if (Invoice::where('student_id', $s->id)->where('fee_type_id', $typeId)->where('month', $data['month'])->exists()) continue;
                $disc = 0;
                foreach ($conc as $c) if (! $c->fee_type_id || $c->fee_type_id === $typeId) $disc += $c->type === 'percent' ? $amount * $c->value / 100 : (float) $c->value;
                $plan[] = ['student_id' => $s->id, 'fee_type_id' => $typeId, 'title' => $name.' – '.$month->format('F Y'), 'amount' => $amount,
                    'discount' => round(min($amount, $disc), 2), 'month' => $data['month'], 'due_date' => $month->copy()->day(min(28, max(1, (int) $dueDay)))->toDateString()];
            }
        }
        if (! empty($data['dry_run'])) return $this->ok(['would_create' => count($plan), 'total' => round(collect($plan)->sum(fn ($p) => $p['amount'] - $p['discount']), 2), 'sample' => array_slice($plan, 0, 10)]);
        DB::transaction(function () use ($plan, $year) {
            $n = (Invoice::max('id') ?? 0);
            foreach ($plan as $p) Invoice::create($p + ['invoice_no' => sprintf('INV%s%05d', now()->format('y'), ++$n), 'academic_year_id' => $year, 'paid' => 0, 'status' => 'unpaid']);
        });
        return $this->ok(['created' => count($plan), 'total' => round(collect($plan)->sum(fn ($p) => $p['amount'] - $p['discount']), 2)], count($plan).' invoices created for '.$month->format('F Y'));
    }

    public function reminders(Request $request)
    {
        $data = $request->validate(['class_id' => 'nullable|exists:school_classes,id', 'section_id' => 'nullable|exists:sections,id', 'send' => 'nullable|boolean', 'overdue_only' => 'nullable|boolean']);
        $school = Setting::get('school_short_name', 'School');
        $inv = Invoice::with('student.user', 'student.parent', 'student.schoolClass', 'student.section')->where('status', '!=', 'paid')
            ->when($data['overdue_only'] ?? false, fn ($q) => $q->where('due_date', '<', now()->toDateString()))
            ->whereHas('student', fn ($q) => $q->where('status', 'active')
                ->when($data['class_id'] ?? null, fn ($w, $c) => $w->where('class_id', $c))
                ->when($data['section_id'] ?? null, fn ($w, $c) => $w->where('section_id', $c)))->get()->groupBy('student_id');
        $out = [];
        foreach ($inv as $sid => $list) {
            $s = $list->first()->student;
            $due = round($list->sum('balance'), 2);
            if ($due <= 0) continue;
            $phone = preg_replace('/\D/', '', (string) ($s->parent?->phone ?? $s->guardian_phone ?? ''));
            if (strlen($phone) === 10) $phone = '91'.$phone;
            $text = "Namaste. Fee reminder from {$school}: ₹".number_format($due, 2)." is due for {$s->name} ({$s->class_label}). Please pay at the school office. Thank you.";
            $out[] = ['student_id' => $s->id, 'student_name' => $s->name, 'class' => $s->class_label, 'parent_name' => $s->parent?->name, 'phone' => $s->parent?->phone ?? $s->guardian_phone,
                'due' => $due, 'invoices' => $list->count(), 'oldest_due_date' => $list->min(fn ($i) => $i->due_date?->format('Y-m-d')),
                'whatsapp_url' => $phone ? 'https://wa.me/'.$phone.'?text='.rawurlencode($text) : null, 'message' => $text];
        }
        $sent = 0;
        if ($data['send'] ?? true) {
            foreach ($out as $o) {
                $s = Student::find($o['student_id']);
                $sent += AppNotification::send(array_filter([$s->parent_id, $s->user_id]), 'Fee reminder: ₹'.number_format($o['due'], 2).' due', $o['message'], 'invoices', null);
            }
        }
        usort($out, fn ($a, $b) => $b['due'] <=> $a['due']);
        return response()->json(['data' => $out, 'meta' => ['students' => count($out), 'total_due' => round(collect($out)->sum('due'), 2), 'notifications_sent' => $sent],
            'message' => count($out).' families have dues'.($sent ? "; {$sent} in-app reminders sent" : '')]);
    }
}
