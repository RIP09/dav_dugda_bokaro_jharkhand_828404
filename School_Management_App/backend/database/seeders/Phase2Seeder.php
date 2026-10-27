<?php

namespace Database\Seeders;

use App\Core\Locks;
use App\Models\User;
use App\Modules\Accounts\Models\AccountHead;
use App\Modules\Accounts\Models\Transaction;
use App\Modules\Admissions\Models\Enquiry;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Subject;
use App\Modules\Academics\Models\Syllabus;
use App\Modules\Academics\Models\StudyMaterial;
use App\Modules\Certificates\Models\Certificate;
use App\Modules\Communication\Models\AppNotification;
use App\Modules\Communication\Models\Complaint;
use App\Modules\Exams\Models\Exam;
use App\Modules\Fees\Models\Concession;
use App\Modules\Fees\Models\FeeStructure;
use App\Modules\Fees\Models\FeeType;
use App\Modules\FrontOffice\Models\CallLog;
use App\Modules\FrontOffice\Models\PostalRecord;
use App\Modules\FrontOffice\Models\Visitor;
use App\Modules\Hostel\Models\Hostel;
use App\Modules\Hostel\Models\HostelAllocation;
use App\Modules\Hostel\Models\HostelRoom;
use App\Modules\Inventory\Models\Asset;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Payroll\Http\PayrollController;
use App\Modules\Payroll\Models\Payslip;
use App\Modules\Payroll\Models\SalaryStructure;
use App\Modules\Students\Models\Student;
use App\Modules\Transport\Models\TransportRoute;
use App\Modules\Transport\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/** Demo data for the Phase-2 modules (admissions, certificates, fee structure, accounts, payroll, inventory, hostel …). */
class Phase2Seeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'principal@davdugda.in')->first();
        $acct = User::where('email', 'accountant@davdugda.in')->first();
        $today = Carbon::today();

        // Admissions pipeline
        $cls = SchoolClass::orderBy('numeric_order')->get();
        foreach ([
            ['Ishaan Kumar', 'Ravi Kumar', '9431100001', 'Class 1', 'walk_in', 'new'], ['Ananya Kumari', 'Sunita Devi', '9431100002', 'LKG', 'website', 'contacted'],
            ['Arjun Mahto', 'Bablu Mahto', '9431100003', 'Class 6', 'reference', 'test'], ['Riya Singh', 'Manoj Singh', '9431100004', 'Class 9', 'phone', 'approved'],
            ['Kabir Oraon', 'Sanjay Oraon', '9431100005', 'UKG', 'walk_in', 'visit'], ['Diya Gupta', 'Alok Gupta', '9431100006', 'Class 11', 'website', 'new'],
        ] as $i => [$n, $p, $ph, $c, $src, $st]) {
            Enquiry::create(['student_name' => $n, 'parent_name' => $p, 'phone' => $ph, 'class_sought' => $c, 'class_id' => $cls->firstWhere('name', $c)?->id,
                'source' => $src, 'status' => $st, 'follow_up_date' => $today->copy()->addDays($i + 1)->toDateString(), 'previous_school' => $i % 2 ? 'Govt. Middle School, Dugda' : null,
                'message' => 'Want admission for session 2027-28. Please share fee details.', 'assigned_to' => $admin->id]);
        }

        // Fee structure (monthly tuition by wing + yearly charges) and concessions
        $tuition = FeeType::firstOrCreate(['name' => 'Tuition Fee'], ['default_amount' => 1200]);
        $annual = FeeType::firstOrCreate(['name' => 'Annual Charges'], ['default_amount' => 3000]);
        $computer = FeeType::firstOrCreate(['name' => 'Computer Fee'], ['default_amount' => 200]);
        foreach ($cls as $c) {
            $amt = match (true) { $c->numeric_order <= 1 => 900, $c->numeric_order <= 6 => 1100, $c->numeric_order <= 10 => 1300, default => 1600 };
            FeeStructure::create(['class_id' => $c->id, 'fee_type_id' => $tuition->id, 'amount' => $amt, 'frequency' => 'monthly', 'due_day' => 10]);
            FeeStructure::create(['class_id' => $c->id, 'fee_type_id' => $annual->id, 'amount' => 3000, 'frequency' => 'yearly', 'due_day' => 15]);
            if ($c->numeric_order >= 4) FeeStructure::create(['class_id' => $c->id, 'fee_type_id' => $computer->id, 'amount' => 200, 'frequency' => 'monthly', 'due_day' => 10]);
        }
        foreach (Student::inRandomOrder()->limit(6)->get() as $i => $s) {
            Concession::create(['student_id' => $s->id, 'fee_type_id' => $i % 2 ? $tuition->id : null, 'type' => $i % 3 ? 'percent' : 'fixed', 'value' => $i % 3 ? [10, 25, 50][$i % 3] : 300,
                'reason' => ['Sibling', 'Staff ward', 'Merit', 'Financial need', 'Sports', 'RTE'][$i]]);
        }

        // Accounts
        $heads = [];
        foreach ([['Electricity', 'expense'], ['Salaries', 'expense'], ['Stationery', 'expense'], ['Maintenance', 'expense'], ['Diesel (buses)', 'expense'], ['Donation', 'income'], ['Uniform sale', 'income'], ['Hall rent', 'income']] as [$n, $t]) {
            $heads[$n] = AccountHead::create(['name' => $n, 'type' => $t]);
        }
        $v = 0;
        foreach (range(5, 0) as $m) {
            $d = $today->copy()->startOfMonth()->subMonths($m);
            foreach ([['Electricity', 18000], ['Diesel (buses)', 42000], ['Stationery', 6500], ['Maintenance', 9000], ['Uniform sale', 15000], ['Donation', $m % 2 ? 0 : 21000]] as [$h, $amt]) {
                if (! $amt) continue;
                Transaction::create(['voucher_no' => sprintf('V26%05d', ++$v), 'date' => $d->copy()->addDays(mt_rand(2, 25))->toDateString(), 'account_head_id' => $heads[$h]->id,
                    'type' => $heads[$h]->type, 'amount' => $amt + mt_rand(-1500, 1500), 'mode' => ['cash', 'bank', 'upi'][mt_rand(0, 2)],
                    'party' => ['JBVNL', 'Indian Oil, Phusro', 'Gupta Stationers', 'Local contractor', 'Parents', 'Well-wisher'][array_search($h, ['Electricity', 'Diesel (buses)', 'Stationery', 'Maintenance', 'Uniform sale', 'Donation'])],
                    'created_by' => $acct->id]);
            }
        }

        // Payroll: salary structures for all staff, last month's slips paid (locked), this month draft
        foreach (User::whereIn('role', [User::ADMIN, ...User::STAFF_ROLES])->with('staffProfile')->get() as $u) {
            $basic = round(((float) ($u->staffProfile?->salary ?? 30000)) * 0.6);
            SalaryStructure::create(['user_id' => $u->id, 'basic' => $basic, 'hra' => round($basic * 0.2), 'da' => round($basic * 0.3), 'ta' => 1200, 'other_allowance' => 0,
                'pf' => round($basic * 0.12), 'esi' => 0, 'tds' => $basic > 30000 ? 1500 : 0, 'other_deduction' => 0, 'bank_name' => 'State Bank of India, Dugda', 'account_no' => '3'.mt_rand(100000000, 999999999), 'ifsc' => 'SBIN0001234']);
        }
        $req = new \Illuminate\Http\Request(['month' => $today->copy()->subMonth()->format('Y-m')]);
        app(PayrollController::class)->run($req);
        Payslip::where('month', $today->copy()->subMonth()->format('Y-m'))->get()->each(fn ($p) => $p->update(['status' => 'paid', 'paid_on' => $today->copy()->startOfMonth()->toDateString(), 'mode' => 'bank']));
        app(PayrollController::class)->run(new \Illuminate\Http\Request(['month' => $today->format('Y-m')]));

        // Inventory & assets
        foreach ([['A4 paper (rim)', 'Stationery', 'rim', 40, 10, 260], ['Chalk box', 'Stationery', 'box', 120, 30, 45], ['Whiteboard marker', 'Stationery', 'pcs', 60, 20, 25],
            ['Football', 'Sports', 'pcs', 8, 3, 650], ['Test tubes', 'Lab', 'pcs', 150, 40, 12], ['Phenyl (5 L)', 'Cleaning', 'can', 6, 4, 320], ['Tube light', 'Electrical', 'pcs', 3, 5, 180]] as [$n, $c, $unit, $q, $min, $price]) {
            $it = InventoryItem::create(['name' => $n, 'category' => $c, 'unit' => $unit, 'quantity' => 0, 'min_quantity' => $min, 'unit_price' => $price, 'location' => 'Main store', 'supplier' => 'Gupta Stationers']);
            StockMovement::create(['inventory_item_id' => $it->id, 'type' => 'in', 'quantity' => $q, 'date' => $today->copy()->subDays(40)->toDateString(), 'party' => 'Gupta Stationers', 'note' => 'Opening stock', 'created_by' => $acct->id]);
            if ($q > 10) StockMovement::create(['inventory_item_id' => $it->id, 'type' => 'out', 'quantity' => round($q / 5), 'date' => $today->copy()->subDays(5)->toDateString(), 'party' => 'Class teachers', 'created_by' => $acct->id]);
        }
        foreach ([['Smart board (Class 10-A)', 'Computer', 'Room 10-A', 85000], ['Computer lab PCs (20)', 'Computer', 'Computer lab', 520000], ['School bus JH09-AB-1234', 'Vehicle', 'Garage', 1850000],
            ['Physics lab equipment set', 'Lab equipment', 'Physics lab', 120000], ['Steel almirah', 'Furniture', 'Office', 14000], ['Inverter + batteries', 'Electrical', 'Office', 42000]] as $i => [$n, $c, $loc, $cost]) {
            Asset::create(['asset_tag' => sprintf('DAV-A%04d', $i + 1), 'name' => $n, 'category' => $c, 'location' => $loc, 'purchase_date' => $today->copy()->subMonths(6 + $i * 5)->toDateString(), 'cost' => $cost, 'condition' => $i === 4 ? 'repair' : 'good', 'vendor' => 'Local vendor']);
        }

        // Hostel
        $boys = Hostel::create(['name' => 'Aryabhatta Boys Hostel', 'type' => 'boys', 'warden_name' => 'Ramesh Thakur', 'warden_phone' => '9431200001', 'address' => 'School campus']);
        $girls = Hostel::create(['name' => 'Gargi Girls Hostel', 'type' => 'girls', 'warden_name' => 'Sushma Devi', 'warden_phone' => '9431200002', 'address' => 'School campus']);
        $rooms = [];
        foreach ([$boys, $girls] as $h) foreach (range(1, 4) as $r) $rooms[$h->type][] = HostelRoom::create(['hostel_id' => $h->id, 'room_no' => ($h->type === 'boys' ? 'B' : 'G').'-10'.$r, 'room_type' => 'Non-AC, 4 beds', 'capacity' => 4, 'fee_per_month' => 3500]);
        foreach (Student::with('user')->whereHas('schoolClass', fn ($q) => $q->where('numeric_order', '>=', 8))->limit(10)->get() as $i => $s) {
            $list = $rooms[$s->user?->gender === 'female' ? 'girls' : 'boys'];
            HostelAllocation::create(['student_id' => $s->id, 'hostel_room_id' => $list[$i % 4]->id, 'bed_no' => (string) (1 + intdiv($i, 4)), 'from_date' => '2026-04-05', 'status' => 'active']);
        }

        // Vehicles
        foreach (TransportRoute::all() as $i => $r) {
            Vehicle::create(['vehicle_no' => $r->vehicle_no ?: 'JH09-BUS-'.($i + 1), 'model' => 'Tata Starbus 40', 'capacity' => 40, 'transport_route_id' => $r->id, 'driver_name' => $r->driver_name, 'driver_phone' => $r->driver_phone,
                'driver_license' => 'JH09'.mt_rand(2010, 2020).mt_rand(1000000, 9999999), 'insurance_expiry' => $today->copy()->addMonths(3 + $i * 2)->toDateString(), 'fitness_expiry' => $today->copy()->addMonths(8)->toDateString(), 'pollution_expiry' => $today->copy()->addDays(20 + $i * 30)->toDateString()]);
        }

        // Front office
        foreach ([['Sunil Prasad', 'Admission enquiry', 'Office'], ['Courier boy (DTDC)', 'Delivery', 'Office'], ['Mrs. Kumari', 'Meet class teacher', 'Class teacher 5-A'], ['CBSE inspection team', 'Inspection', 'Principal'], ['Ramesh Electricals', 'Repair work', 'Office']] as $i => [$n, $p, $whom]) {
            Visitor::create(['pass_no' => 'VP'.$today->format('ymd').'-00'.($i + 1), 'name' => $n, 'phone' => '94311'.mt_rand(10000, 99999), 'purpose' => $p, 'whom_to_meet' => $whom, 'persons' => 1 + $i % 3,
                'in_time' => $today->copy()->setTime(9 + $i, 15), 'out_time' => $i < 4 ? $today->copy()->setTime(9 + $i, 50) : null]);
        }
        PostalRecord::create(['type' => 'receive', 'title' => 'CBSE circular – Board exam registration', 'from_to' => 'CBSE Regional Office, Patna', 'reference_no' => 'RP12345678IN', 'date' => $today->copy()->subDays(3)->toDateString()]);
        PostalRecord::create(['type' => 'dispatch', 'title' => 'TC documents', 'from_to' => 'DAV Public School, Dhanbad', 'reference_no' => 'EM98765432IN', 'date' => $today->copy()->subDays(1)->toDateString()]);
        CallLog::create(['name' => 'Parent of Aarav', 'phone' => '9431000001', 'type' => 'incoming', 'purpose' => 'Bus timing', 'date' => $today->toDateString()]);
        CallLog::create(['name' => 'Gupta Stationers', 'phone' => '9431300001', 'type' => 'outgoing', 'purpose' => 'Order A4 paper', 'date' => $today->copy()->subDay()->toDateString(), 'follow_up_date' => $today->copy()->addDays(2)->toDateString()]);

        // Complaints & feedback
        $parent = User::where('email', 'parent@davdugda.in')->first();
        Complaint::create(['user_id' => $parent->id, 'type' => 'complaint', 'category' => 'Transport', 'subject' => 'Bus arrives late at Phusro stop', 'description' => 'For the last week the bus is 15 minutes late.', 'status' => 'in_progress', 'assigned_to' => $admin->id]);
        Complaint::create(['user_id' => $parent->id, 'type' => 'suggestion', 'category' => 'Academics', 'subject' => 'Weekly test for Class 8', 'description' => 'A short weekly test would help children revise.', 'status' => 'open']);

        // Syllabus & study material
        foreach (Subject::with('schoolClass')->whereHas('schoolClass', fn ($q) => $q->where('name', 'Class 8'))->get() as $sub) {
            foreach (['Chapter 1', 'Chapter 2', 'Chapter 3'] as $k => $ch) Syllabus::create(['class_id' => $sub->class_id, 'subject_id' => $sub->id, 'title' => $sub->name.' – '.$ch, 'planned_month' => Carbon::create(2026, 4 + $k * 2)->format('Y-m'), 'status' => ['completed', 'in_progress', 'pending'][$k], 'completed_on' => $k === 0 ? '2026-05-30' : null]);
            StudyMaterial::create(['class_id' => $sub->class_id, 'subject_id' => $sub->id, 'title' => $sub->name.' – revision notes', 'description' => 'Important questions for the half-yearly exam.', 'link' => 'https://ncert.nic.in/textbook.php', 'uploaded_by' => $sub->teacher_id]);
        }

        // Certificates: an issued bonafide (locked), a draft character certificate
        $aarav = Student::whereHas('user', fn ($q) => $q->where('email', 'student@davdugda.in'))->first();
        Certificate::create(['certificate_no' => 'BC/2026/0001', 'type' => 'bonafide', 'student_id' => $aarav->id, 'issue_date' => $today->copy()->subDays(10)->toDateString(), 'status' => 'issued', 'purpose' => 'Opening a bank account', 'issued_by' => $admin->id]);
        Certificate::create(['certificate_no' => 'CC/2026/0001', 'type' => 'character', 'student_id' => $aarav->id, 'status' => 'draft', 'conduct' => 'Very Good']);

        // One exam with results locked (final) to show permanent marks
        if ($e = Exam::where('is_published', true)->orderBy('start_date')->first()) Locks::bypass(fn () => $e->update(['results_locked' => true, 'locked_at' => now()]));

        // A few in-app notifications for the demo parent/student
        $student = User::where('email', 'student@davdugda.in')->first();
        AppNotification::send([$parent->id, $student->id], 'Welcome to DAV Dugda online', 'Fees, attendance, homework and results are now on your phone.', 'notices', null);
    }
}
