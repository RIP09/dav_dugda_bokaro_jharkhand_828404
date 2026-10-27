<?php

namespace App\Modules\Payroll\Http;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Attendance\Models\StaffAttendance;
use App\Modules\Payroll\Models\Payslip;
use App\Modules\Payroll\Models\SalaryStructure;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Monthly payroll: draft salary slips from salary structures and staff attendance (loss of pay for absences). */
class PayrollController extends Controller
{
    public function run(Request $request)
    {
        $data = $request->validate(['month' => 'required|date_format:Y-m', 'working_days' => 'nullable|integer|min:1|max:31']);
        $month = Carbon::parse($data['month'].'-01');
        $working = $data['working_days'] ?? 26;
        $created = 0; $skipped = 0;
        foreach (SalaryStructure::with('user')->get() as $ss) {
            if (! $ss->user || ! $ss->user->is_active) continue;
            if (Payslip::where('user_id', $ss->user_id)->where('month', $data['month'])->exists()) { $skipped++; continue; }
            $absent = StaffAttendance::where('user_id', $ss->user_id)->whereBetween('date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])
                ->selectRaw("sum(case when status='absent' then 1 when status='half_day' then 0.5 else 0 end) a")->value('a') ?? 0;
            $slip = Payslip::create([
                'payslip_no' => sprintf('PS%s-%04d', $month->format('ym'), (Payslip::max('id') ?? 0) + 1),
                'user_id' => $ss->user_id, 'month' => $data['month'], 'working_days' => $working, 'present_days' => max(0, $working - (float) $absent), 'status' => 'draft',
            ]);
            $this->recalc($slip);
            $created++;
        }
        return $this->ok(['created' => $created, 'already_existed' => $skipped], "{$created} salary slips drafted for ".$month->format('F Y').($skipped ? " ({$skipped} already existed)" : ''));
    }

    /** Recompute amounts of a draft slip from its salary structure and days present. */
    public function recalc(Payslip $slip): void
    {
        if ($slip->status !== 'draft') return;
        $ss = SalaryStructure::where('user_id', $slip->user_id)->first();
        if (! $ss) return;
        $ratio = $slip->working_days ? min(1, $slip->present_days / $slip->working_days) : 1;
        $earn = ['Basic' => $ss->basic, 'HRA' => $ss->hra, 'DA' => $ss->da, 'TA' => $ss->ta, 'Other allowance' => $ss->other_allowance];
        $earn = array_map(fn ($v) => round((float) $v * $ratio, 2), array_filter($earn, fn ($v) => (float) $v > 0));
        $ded = array_map(fn ($v) => round((float) $v, 2), array_filter(['PF' => $ss->pf, 'ESI' => $ss->esi, 'TDS' => $ss->tds, 'Other deduction' => $ss->other_deduction], fn ($v) => (float) $v > 0));
        $gross = array_sum($earn); $d = array_sum($ded);
        $slip->forceFill(['gross' => $gross, 'deductions' => $d, 'net' => round($gross - $d, 2), 'breakdown' => ['earnings' => $earn, 'deductions' => $ded, 'loss_of_pay_days' => $slip->working_days - $slip->present_days]])->saveQuietly();
    }
}
