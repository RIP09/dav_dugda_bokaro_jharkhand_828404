<?php

namespace App\Modules\Fees\Http;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\AcademicYear;
use App\Modules\Fees\Models\FeeType;
use App\Modules\Fees\Models\Invoice;
use App\Modules\Fees\Models\Payment;
use App\Modules\Students\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Fee types, invoices and payments (lav_sms payments / payment_records / receipts). */
class FeeController extends Controller
{
    public function types()
    {
        return $this->ok(FeeType::orderBy('name')->get());
    }

    public function storeType(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:80', 'description' => 'nullable|string|max:255', 'default_amount' => 'nullable|numeric|min:0']);
        return $this->created(FeeType::create($data), 'Fee type added');
    }

    public function destroyType(FeeType $type)
    {
        $type->delete();
        return $this->ok(null, 'Fee type deleted');
    }

    /** GET /invoices?status=&student_id=&class_id=&search= ; parents/students see only their own */
    public function invoices(Request $request)
    {
        $user = $request->user();
        $q = Invoice::with(['student.user', 'student.schoolClass', 'student.section', 'feeType', 'payments'])->latest('due_date');
        if (in_array($user->role, [User::PARENT, User::STUDENT], true)) {
            $ids = $user->ownStudentIds();
            if ($request->filled('student_id') && in_array((int) $request->student_id, $ids, true)) $ids = [(int) $request->student_id];
            $q->whereIn('student_id', $ids);
        } else {
            if ($request->filled('student_id')) $q->where('student_id', $request->student_id);
            if ($request->filled('class_id')) $q->whereHas('student', fn ($s) => $s->where('class_id', $request->class_id));
            if ($s = $request->query('search')) {
                $q->where(fn ($w) => $w->where('invoice_no', 'like', "%$s%")->orWhereHas('student.user', fn ($u) => $u->where('name', 'like', "%$s%")));
            }
        }
        if ($request->filled('status')) {
            $request->status === 'due' ? $q->where('status', '!=', 'paid') : $q->where('status', $request->status);
        }
        return $this->ok($q->limit(500)->get());
    }

    /**
     * POST /invoices — bill one student (student_id) or a whole class (class_id [+ section_id]).
     * { fee_type_id?, title, amount, discount?, due_date }
     */
    public function storeInvoice(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required_without:class_id|nullable|exists:students,id',
            'class_id' => 'required_without:student_id|nullable|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'fee_type_id' => 'nullable|exists:fee_types,id',
            'title' => 'required|string|max:120',
            'amount' => 'required|numeric|min:1',
            'discount' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
        ]);
        $students = ! empty($data['student_id'])
            ? Student::whereKey($data['student_id'])->get()
            : Student::where('class_id', $data['class_id'])->where('status', 'active')
                ->when($data['section_id'] ?? null, fn ($q, $s) => $q->where('section_id', $s))->get();
        if ($students->isEmpty()) return $this->fail('No students found to bill.');
        $year = AcademicYear::current()?->id;
        $created = DB::transaction(fn () => $students->map(fn ($s) => Invoice::create([
            'invoice_no' => $this->nextNo('INV', Invoice::class, 'invoice_no'),
            'student_id' => $s->id, 'fee_type_id' => $data['fee_type_id'] ?? null, 'academic_year_id' => $year,
            'title' => $data['title'], 'amount' => $data['amount'], 'discount' => $data['discount'] ?? 0,
            'due_date' => $data['due_date'] ?? now()->addDays(15)->toDateString(),
        ])));
        return $this->created(['count' => $created->count()], $created->count().' invoice(s) created');
    }

    public function destroyInvoice(Invoice $invoice)
    {
        if ($invoice->paid > 0) return $this->fail('This invoice has payments recorded and cannot be deleted.');
        $invoice->delete();
        return $this->ok(null, 'Invoice deleted');
    }

    /** POST /invoices/{invoice}/payments { amount, method, reference?, paid_on? } — record a payment, issue receipt */
    public function pay(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'method' => 'nullable|in:cash,upi,card,bank,cheque,online',
            'reference' => 'nullable|string|max:100',
            'paid_on' => 'nullable|date',
        ]);
        if ($data['amount'] > $invoice->balance + 0.001) {
            return $this->fail('Amount is more than the balance due (₹'.number_format($invoice->balance, 2).').');
        }
        $payment = DB::transaction(function () use ($data, $invoice, $request) {
            $p = $invoice->payments()->create([
                'receipt_no' => $this->nextNo('RCPT', Payment::class, 'receipt_no'),
                'amount' => $data['amount'], 'method' => $data['method'] ?? 'cash', 'reference' => $data['reference'] ?? null,
                'paid_on' => $data['paid_on'] ?? now()->toDateString(), 'received_by' => $request->user()->id,
            ]);
            $invoice->refreshStatus();
            return $p;
        });
        return $this->created(['payment' => $payment, 'invoice' => $invoice->fresh(['payments', 'student.user'])], 'Payment recorded. Receipt '.$payment->receipt_no);
    }

    /** GET /payments?from=&to= (admin/accountant), own for parent/student */
    public function payments(Request $request)
    {
        $user = $request->user();
        $q = Payment::with(['invoice.student.user'])->latest('paid_on')->latest('id');
        if (in_array($user->role, [User::PARENT, User::STUDENT], true)) {
            $q->whereHas('invoice', fn ($i) => $i->whereIn('student_id', $user->ownStudentIds()));
        }
        if ($request->filled('from')) $q->where('paid_on', '>=', $request->from);
        if ($request->filled('to')) $q->where('paid_on', '<=', $request->to.' 23:59:59');
        return $this->ok($q->limit(300)->get()->map(fn ($p) => array_merge($p->toArray(), [
            'student_name' => $p->invoice?->student?->name, 'invoice_title' => $p->invoice?->title, 'invoice_no' => $p->invoice?->invoice_no,
        ])));
    }

    /** GET /fees/summary — collection dashboard */
    public function summary()
    {
        $inv = Invoice::selectRaw('sum(amount - discount) billed, sum(paid) collected')->first();
        return $this->ok([
            'billed' => round((float) $inv->billed, 2),
            'collected' => round((float) $inv->collected, 2),
            'due' => round((float) $inv->billed - (float) $inv->collected, 2),
            'today' => round((float) Payment::where('status', '!=', 'cancelled')->where('paid_on', '>=', now()->toDateString())->sum('amount'), 2),
            'this_month' => round((float) Payment::where('status', '!=', 'cancelled')->where('paid_on', '>=', now()->startOfMonth()->toDateString())->sum('amount'), 2),
            'overdue_invoices' => Invoice::where('status', '!=', 'paid')->where('due_date', '<', now()->toDateString())->count(),
        ]);
    }

    private function nextNo(string $prefix, string $model, string $col): string
    {
        $base = $prefix.now()->format('ym');
        $n = $model::where($col, 'like', $base.'%')->count() + 1;
        do { $no = sprintf('%s%05d', $base, $n++); } while ($model::where($col, $no)->exists());
        return $no;
    }
}
