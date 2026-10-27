<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Book;
use App\Models\BookIssue;
use App\Models\Event;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\FeeType;
use App\Models\Grade;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\Invoice;
use App\Models\LeaveRequest;
use App\Models\Mark;
use App\Models\Message;
use App\Models\Notice;
use App\Models\Payment;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Subject;
use App\Models\TimetableEntry;
use App\Models\TransportRoute;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Demo data for DAV Public School, Dugda (Bokaro, Jharkhand 828404).
 * Demo logins (password in brackets) are listed in the README and printed at the end.
 */
class DatabaseSeeder extends Seeder
{
    private array $boys = ['Aarav', 'Vivaan', 'Aditya', 'Arjun', 'Sai', 'Reyansh', 'Krishna', 'Ishaan', 'Rohan', 'Kabir', 'Ayaan', 'Dhruv', 'Ansh', 'Shivam', 'Rahul', 'Aman', 'Harsh', 'Nikhil', 'Pranav', 'Yash', 'Ritesh', 'Saurabh', 'Abhishek', 'Kunal'];
    private array $girls = ['Aadhya', 'Ananya', 'Diya', 'Saanvi', 'Pari', 'Ishita', 'Kavya', 'Riya', 'Sneha', 'Priya', 'Anjali', 'Khushi', 'Muskan', 'Neha', 'Pooja', 'Shreya', 'Tanvi', 'Aditi', 'Simran', 'Nandini', 'Sakshi', 'Komal', 'Megha', 'Ritika'];
    private array $surnames = ['Kumar', 'Singh', 'Sharma', 'Verma', 'Mahato', 'Gupta', 'Prasad', 'Mishra', 'Pandey', 'Sinha', 'Yadav', 'Oraon', 'Munda', 'Soren', 'Jha', 'Thakur', 'Choudhary', 'Rawani', 'Agarwal', 'Das'];
    private array $fathers = ['Rajesh', 'Suresh', 'Manoj', 'Sanjay', 'Anil', 'Vinod', 'Ashok', 'Ramesh', 'Dinesh', 'Mukesh', 'Pramod', 'Ajay', 'Santosh', 'Rakesh', 'Umesh', 'Arvind'];
    private array $mothers = ['Sunita', 'Anita', 'Rekha', 'Poonam', 'Kiran', 'Seema', 'Manju', 'Geeta', 'Rita', 'Usha', 'Mamta', 'Savita', 'Nisha', 'Pinki', 'Rani', 'Kavita'];

    private int $phone = 9100000000;

    public function run(): void
    {
        mt_srand(828404);
        DB::disableQueryLog();

        // ---- School profile -------------------------------------------------------------------
        foreach ([
            'school_name' => 'DAV Public School, Dugda',
            'school_short_name' => 'DAV Dugda',
            'address' => 'Dugda, Bokaro, Jharkhand 828404',
            'phone' => '+91 6549 000000',
            'email' => 'office@davdugda.in',
            'principal' => 'Dr. R. K. Sinha',
            'affiliation' => 'CBSE, New Delhi',
            'website' => null,
            'late_fine_per_day' => '10',
            'library_fine_per_day' => '2',
            'library_issue_days' => '14',
        ] as $k => $v) Setting::put($k, $v);

        $year = AcademicYear::create(['title' => '2026-27', 'start_date' => '2026-04-01', 'end_date' => '2027-03-31', 'is_current' => true]);
        AcademicYear::create(['title' => '2025-26', 'start_date' => '2025-04-01', 'end_date' => '2026-03-31', 'is_current' => false]);

        foreach ([['A1', 91, 100, 10, 'Outstanding'], ['A2', 81, 90.99, 9, 'Excellent'], ['B1', 71, 80.99, 8, 'Very good'],
            ['B2', 61, 70.99, 7, 'Good'], ['C1', 51, 60.99, 6, 'Above average'], ['C2', 41, 50.99, 5, 'Average'],
            ['D', 33, 40.99, 4, 'Pass'], ['E', 0, 32.99, 0, 'Needs improvement']] as [$n, $min, $max, $pt, $r]) {
            Grade::create(['name' => $n, 'min_percent' => $min, 'max_percent' => $max, 'point' => $pt, 'remark' => $r]);
        }

        // ---- People: admin & staff -----------------------------------------------------------------
        $admin = $this->user('Deepanshu (Admin)', 'admin@davdugda.in', User::ADMIN, 'admin123', 'male');
        $this->user('Dr. R. K. Sinha', 'principal@davdugda.in', User::ADMIN, 'admin123', 'male');

        $teacherNames = [
            ['Sunil Kumar Mishra', 'male', 'Mathematics'], ['Rina Sharma', 'female', 'English'], ['Amit Kumar Singh', 'male', 'Science'],
            ['Pratima Kumari', 'female', 'Hindi'], ['Rakesh Prasad', 'male', 'Social Science'], ['Neelam Verma', 'female', 'Computer'],
            ['Vijay Pandey', 'male', 'Sanskrit'], ['Shalini Gupta', 'female', 'Physics'], ['Manish Jha', 'male', 'Chemistry'],
            ['Kumari Anita', 'female', 'Biology'], ['Sangeeta Das', 'female', 'Primary'], ['Alok Thakur', 'male', 'Physical Education'],
        ];
        $teachers = [];
        foreach ($teacherNames as $i => [$name, $g, $dept]) {
            $email = $i === 0 ? 'teacher@davdugda.in' : strtolower(str_replace([' ', '.'], ['.', ''], $name)).'@davdugda.in';
            $teachers[$dept] = $this->user($name, $email, User::TEACHER, 'teacher123', $g, [
                'designation' => $i < 3 ? 'PGT' : ($dept === 'Primary' ? 'PRT' : 'TGT'), 'department' => $dept,
                'qualification' => $i % 2 ? 'M.A., B.Ed.' : 'M.Sc., B.Ed.', 'salary' => 32000 + 1500 * (12 - $i),
            ]);
        }
        $accountant = $this->user('Pankaj Agarwal', 'accountant@davdugda.in', User::ACCOUNTANT, 'accountant123', 'male', ['designation' => 'Accountant', 'department' => 'Accounts', 'salary' => 28000]);
        $librarian = $this->user('Meena Kumari', 'librarian@davdugda.in', User::LIBRARIAN, 'librarian123', 'female', ['designation' => 'Librarian', 'department' => 'Library', 'salary' => 26000]);
        $this->user('Ramesh Mahato', 'office@davdugda.in', User::STAFF, 'staff123', 'male', ['designation' => 'Office Assistant', 'department' => 'Administration', 'salary' => 18000]);
        $this->user('Bablu Rawani', 'transport@davdugda.in', User::STAFF, 'staff123', 'male', ['designation' => 'Transport In-charge', 'department' => 'Transport', 'salary' => 17000]);

        // ---- Transport -------------------------------------------------------------------------
        $routes = collect([
            ['Route 1 - Dugda Colony', 'JH09 AB 1234', ['Dugda More', 'Dugda Colony', 'Station Road', 'School'], 900],
            ['Route 2 - Chandrapura', 'JH09 AB 5678', ['Chandrapura Bazar', 'DVC Colony', 'Telo', 'School'], 1200],
            ['Route 3 - Nawadih', 'JH09 CD 2468', ['Nawadih Chowk', 'Bermo', 'Jaridih', 'School'], 1400],
            ['Route 4 - Phusro', 'JH09 CD 1357', ['Phusro Bazar', 'Kargali', 'Dugda Washery', 'School'], 1100],
        ])->map(fn ($r, $i) => TransportRoute::create([
            'name' => $r[0], 'vehicle_no' => $r[1], 'stops' => $r[2], 'fare' => $r[3],
            'driver_name' => ['Shankar Yadav', 'Mahesh Soren', 'Ganesh Munda', 'Rajan Oraon'][$i], 'driver_phone' => '98350'.str_pad((string) (11111 * ($i + 1)), 5, '0', STR_PAD_LEFT),
            'pickup_time' => ['06:50', '06:35', '06:30', '06:45'][$i], 'drop_time' => ['14:20', '14:40', '14:45', '14:30'][$i],
        ]));

        // ---- Classes, sections, subjects ---------------------------------------------------------
        $classDefs = [['LKG', 'Pre-primary'], ['UKG', 'Pre-primary']];
        for ($c = 1; $c <= 12; $c++) $classDefs[] = ["Class $c", $c <= 5 ? 'Primary' : ($c <= 10 ? 'Secondary' : 'Senior Secondary')];
        $teacherList = array_values($teachers);
        $sections = [];
        foreach ($classDefs as $order => [$name, $group]) {
            $class = SchoolClass::create(['name' => $name, 'numeric_order' => $order + 1, 'group' => $group]);
            $num = (int) filter_var($name, FILTER_SANITIZE_NUMBER_INT);
            $secNames = ($num >= 6 && $num <= 10) ? ['A', 'B'] : ['A'];
            foreach ($secNames as $si => $sn) {
                $sections[] = $class->sections()->create(['name' => $sn, 'capacity' => 40, 'room' => 'R-'.(100 + count($sections)),
                    'teacher_id' => $teacherList[(count($sections)) % count($teacherList)]->id]);
            }
            $subjects = match (true) {
                $group === 'Pre-primary' => ['English', 'Hindi', 'Mathematics', 'Drawing', 'Rhymes & Stories'],
                $num <= 5 => ['English', 'Hindi', 'Mathematics', 'EVS', 'Computer', 'GK'],
                $num <= 8 => ['English', 'Hindi', 'Mathematics', 'Science', 'Social Science', 'Sanskrit', 'Computer'],
                $num <= 10 => ['English', 'Hindi', 'Mathematics', 'Science', 'Social Science', 'Computer'],
                default => ['English', 'Physics', 'Chemistry', 'Mathematics', 'Biology', 'Computer Science'],
            };
            foreach ($subjects as $k => $sub) {
                $dept = match ($sub) {
                    'English', 'Rhymes & Stories' => 'English', 'Hindi' => 'Hindi', 'Mathematics' => 'Mathematics',
                    'Science', 'EVS' => 'Science', 'Social Science', 'GK' => 'Social Science', 'Sanskrit' => 'Sanskrit',
                    'Computer', 'Computer Science' => 'Computer', 'Physics' => 'Physics', 'Chemistry' => 'Chemistry',
                    'Biology' => 'Biology', default => 'Primary',
                };
                if ($group === 'Pre-primary') $dept = 'Primary';
                Subject::create(['class_id' => $class->id, 'name' => $sub, 'code' => strtoupper(substr(str_replace(' ', '', $sub), 0, 3)).($num ?: 0),
                    'type' => in_array($sub, ['Physics', 'Chemistry', 'Biology', 'Computer Science'], true) ? 'both' : 'theory', 'teacher_id' => $teachers[$dept]->id]);
            }
        }

        // ---- Timetable: Mon-Sat, 6 periods per section -------------------------------------------
        $slots = [['08:00', '08:40'], ['08:40', '09:20'], ['09:20', '10:00'], ['10:20', '11:00'], ['11:00', '11:40'], ['11:40', '12:20']];
        $busy = [];
        foreach ($sections as $sec) {
            $subs = Subject::where('class_id', $sec->class_id)->get()->values();
            for ($day = 1; $day <= 6; $day++) {
                foreach ($slots as $p => [$st, $en]) {
                    for ($try = 0; $try < $subs->count(); $try++) {
                        $sub = $subs[($day + $p + $try) % $subs->count()];
                        $key = "$sub->teacher_id-$day-$p";
                        if (! isset($busy[$key])) break;
                    }
                    $teacherId = isset($busy[$key]) ? null : $sub->teacher_id;
                    if ($teacherId) $busy[$key] = true;
                    TimetableEntry::create(['class_id' => $sec->class_id, 'section_id' => $sec->id, 'subject_id' => $sub->id,
                        'teacher_id' => $teacherId, 'day' => $day, 'start_time' => $st, 'end_time' => $en, 'room' => $sec->room]);
                }
            }
        }

        // ---- Students & parents ----------------------------------------------------------------
        $studentPw = Hash::make('student123');
        $parentPw = Hash::make('parent123');
        $demoParent = User::create(['name' => 'Rajesh Kumar Mahato', 'email' => 'parent@davdugda.in', 'phone' => '9431000001', 'role' => User::PARENT,
            'gender' => 'male', 'address' => 'Dugda Colony, Bokaro, Jharkhand 828404', 'password' => 'parent123']);
        $admNo = 1;
        $allStudents = [];
        foreach ($sections as $si => $sec) {
            $count = 6;
            for ($k = 0; $k < $count; $k++) {
                $girl = ($k + $si) % 2 === 0;
                $first = $girl ? $this->girls[mt_rand(0, count($this->girls) - 1)] : $this->boys[mt_rand(0, count($this->boys) - 1)];
                $sur = $this->surnames[mt_rand(0, count($this->surnames) - 1)];
                $fatherFirst = $this->fathers[mt_rand(0, count($this->fathers) - 1)];
                $isDemoStudent = $sec->schoolClass->name === 'Class 8' && $sec->name === 'A' && $k === 0;
                $isDemoSibling = $sec->schoolClass->name === 'Class 3' && $k === 0;

                if ($isDemoStudent) { $first = 'Aarav'; $sur = 'Mahato'; $girl = false; }
                if ($isDemoSibling) { $first = 'Ananya'; $sur = 'Mahato'; $girl = true; }
                $admission = sprintf('DAV2026%04d', $admNo++);
                $u = User::create([
                    'name' => "$first $sur",
                    'email' => $isDemoStudent ? 'student@davdugda.in' : strtolower($admission).'@student.davdugda.in',
                    'phone' => null, 'role' => User::STUDENT, 'gender' => $girl ? 'female' : 'male',
                    'dob' => Carbon::create(2026 - 5 - $sec->schoolClass->numeric_order, mt_rand(1, 12), mt_rand(1, 28))->toDateString(),
                    'address' => ['Dugda Colony', 'Chandrapura', 'Nawadih', 'Phusro', 'Bermo', 'Telo'][mt_rand(0, 5)].', Bokaro, Jharkhand 828404',
                    'password' => $studentPw, // already hashed; the 'hashed' cast keeps it as is
                ]);

                if ($isDemoStudent || $isDemoSibling) {
                    $parent = $demoParent;
                } else {
                    $parent = User::create(['name' => "$fatherFirst $sur", 'phone' => (string) ($this->phone++), 'role' => User::PARENT, 'gender' => 'male',
                        'address' => $u->address, 'password' => $parentPw]);
                }
                $route = mt_rand(0, 2) === 0 ? $routes[mt_rand(0, 3)] : null;
                $allStudents[] = Student::create([
                    'user_id' => $u->id, 'admission_no' => $admission, 'roll_no' => $k + 1, 'class_id' => $sec->class_id, 'section_id' => $sec->id,
                    'academic_year_id' => $year->id, 'parent_id' => $parent->id, 'transport_route_id' => $isDemoStudent ? $routes[0]->id : $route?->id,
                    'admission_date' => Carbon::create(2026 - mt_rand(0, 6), 4, mt_rand(1, 15))->toDateString(),
                    'blood_group' => ['A+', 'B+', 'O+', 'AB+', 'A-', 'B-', 'O-'][mt_rand(0, 6)], 'religion' => 'Hindu',
                    'category' => ['General', 'OBC', 'SC', 'ST'][mt_rand(0, 3)],
                    'father_name' => $parent->name, 'mother_name' => $this->mothers[mt_rand(0, count($this->mothers) - 1)].' Devi',
                    'guardian_phone' => $parent->phone, 'house' => ['Tagore', 'Raman', 'Ashoka', 'Shivaji'][mt_rand(0, 3)],
                ]);
            }
        }

        // ---- Attendance: last 25 school days ---------------------------------------------------------
        $days = [];
        for ($d = Carbon::today()->subDays(35); $d->lte(Carbon::yesterday()); $d->addDay()) if (! $d->isSunday()) $days[] = $d->toDateString();
        $rows = [];
        $now = now();
        foreach ($allStudents as $st) {
            foreach ($days as $day) {
                $r = mt_rand(1, 100);
                $status = $r <= 86 ? 'present' : ($r <= 93 ? 'absent' : ($r <= 98 ? 'late' : 'half_day'));
                $rows[] = ['student_id' => $st->id, 'class_id' => $st->class_id, 'section_id' => $st->section_id, 'date' => $day, 'status' => $status, 'marked_by' => $admin->id, 'created_at' => $now, 'updated_at' => $now];
            }
        }
        foreach (array_chunk($rows, 500) as $chunk) StudentAttendance::insert($chunk);
        $rows = [];
        foreach (User::whereIn('role', User::STAFF_ROLES)->get() as $su) {
            foreach ($days as $day) {
                $r = mt_rand(1, 100);
                $rows[] = ['user_id' => $su->id, 'date' => $day, 'status' => $r <= 92 ? 'present' : ($r <= 96 ? 'leave' : 'late'),
                    'in_time' => '07:'.str_pad((string) mt_rand(30, 59), 2, '0', STR_PAD_LEFT), 'out_time' => '14:'.str_pad((string) mt_rand(30, 59), 2, '0', STR_PAD_LEFT),
                    'marked_by' => $admin->id, 'created_at' => $now, 'updated_at' => $now];
            }
        }
        foreach (array_chunk($rows, 500) as $chunk) StaffAttendance::insert($chunk);

        // ---- Exams & marks ---------------------------------------------------------------------
        $ut1 = Exam::create(['academic_year_id' => $year->id, 'name' => 'Periodic Test 1', 'term' => 'Term 1', 'start_date' => '2026-07-14', 'end_date' => '2026-07-19', 'is_published' => true]);
        $hy = Exam::create(['academic_year_id' => $year->id, 'name' => 'Half Yearly Examination', 'term' => 'Term 1', 'start_date' => '2026-09-21', 'end_date' => '2026-10-03', 'is_published' => true]);
        Exam::create(['academic_year_id' => $year->id, 'name' => 'Periodic Test 2', 'term' => 'Term 2', 'start_date' => '2026-12-07', 'end_date' => '2026-12-12', 'is_published' => false]);
        $annual = Exam::create(['academic_year_id' => $year->id, 'name' => 'Annual Examination', 'term' => 'Term 2', 'start_date' => '2027-02-22', 'end_date' => '2027-03-10', 'is_published' => false]);
        $grades = Grade::orderByDesc('min_percent')->get();
        $gradeFor = fn ($pct) => $grades->first(fn ($g) => $pct >= $g->min_percent);
        $markRows = [];
        $subjectsByClass = Subject::all()->groupBy('class_id');
        foreach ([[$ut1, 40], [$hy, 80]] as [$exam, $max]) {
            foreach ($subjectsByClass as $classId => $subs) {
                foreach ($subs->values() as $i => $sub) {
                    ExamSchedule::create(['exam_id' => $exam->id, 'class_id' => $classId, 'subject_id' => $sub->id,
                        'exam_date' => Carbon::parse($exam->start_date)->addDays($i)->toDateString(), 'start_time' => '09:00', 'end_time' => $max > 40 ? '12:00' : '10:30',
                        'max_marks' => $max, 'pass_marks' => (int) round($max * 0.33)]);
                }
            }
            foreach ($allStudents as $st) {
                $ability = mt_rand(45, 98);
                foreach ($subjectsByClass[$st->class_id] as $sub) {
                    $pct = max(20, min(100, $ability + mt_rand(-12, 10)));
                    $m = round($max * $pct / 100);
                    $g = $gradeFor($m * 100 / $max);
                    $markRows[] = ['exam_id' => $exam->id, 'student_id' => $st->id, 'subject_id' => $sub->id, 'class_id' => $st->class_id, 'section_id' => $st->section_id,
                        'marks_obtained' => $m, 'max_marks' => $max, 'grade' => $g?->name, 'point' => $g?->point, 'is_absent' => false, 'entered_by' => $sub->teacher_id,
                        'created_at' => $now, 'updated_at' => $now];
                }
            }
        }
        foreach (array_chunk($markRows, 500) as $chunk) Mark::insert($chunk);
        foreach ($subjectsByClass as $classId => $subs) {
            foreach ($subs->values() as $i => $sub) {
                ExamSchedule::create(['exam_id' => $annual->id, 'class_id' => $classId, 'subject_id' => $sub->id,
                    'exam_date' => Carbon::parse($annual->start_date)->addDays($i * 2)->toDateString(), 'start_time' => '09:00', 'end_time' => '12:00', 'max_marks' => 80, 'pass_marks' => 26]);
            }
        }

        // ---- Fees ------------------------------------------------------------------------------
        $tuition = FeeType::create(['name' => 'Tuition Fee', 'description' => 'Quarterly tuition fee', 'default_amount' => 4500]);
        $transportFee = FeeType::create(['name' => 'Transport Fee', 'description' => 'Quarterly bus fee', 'default_amount' => 3000]);
        $examFee = FeeType::create(['name' => 'Examination Fee', 'description' => 'Per term', 'default_amount' => 600]);
        FeeType::create(['name' => 'Admission Fee', 'description' => 'One time, new admissions', 'default_amount' => 5000]);
        FeeType::create(['name' => 'Annual Charges', 'description' => 'Development, sports & lab', 'default_amount' => 3500]);
        $inv = 1; $rc = 1;
        $quarters = [['Q1 (Apr-Jun)', '2026-04-15', 'paid'], ['Q2 (Jul-Sep)', '2026-07-15', 'paid'], ['Q3 (Oct-Dec)', '2026-10-15', 'open']];
        foreach ($allStudents as $st) {
            $order = SchoolClass::find($st->class_id)->numeric_order;
            $tuitionAmt = 3000 + $order * 250;
            $bills = [];
            foreach ($quarters as [$q, $due, $state]) {
                $bills[] = [$tuition->id, "Tuition Fee $q", $tuitionAmt, $due, $state];
                if ($st->transport_route_id) $bills[] = [$transportFee->id, "Transport Fee $q", $routes->firstWhere('id', $st->transport_route_id)->fare * 3, $due, $state];
            }
            $bills[] = [$examFee->id, 'Examination Fee Term 1', 600, '2026-09-10', 'paid'];
            foreach ($bills as [$type, $title, $amount, $due, $state]) {
                $invoice = Invoice::create(['invoice_no' => sprintf('INV2604%05d', $inv++), 'student_id' => $st->id, 'fee_type_id' => $type, 'academic_year_id' => $year->id,
                    'title' => $title, 'amount' => $amount, 'discount' => 0, 'due_date' => $due]);
                $roll = mt_rand(1, 100);
                $pay = $state === 'paid' ? ($roll <= 94 ? $amount : 0) : ($roll <= 35 ? $amount : ($roll <= 55 ? round($amount / 2) : 0));
                if ($pay > 0) {
                    Payment::create(['receipt_no' => sprintf('RCPT2604%05d', $rc++), 'invoice_id' => $invoice->id, 'amount' => $pay,
                        'method' => ['cash', 'upi', 'upi', 'bank', 'cheque'][mt_rand(0, 4)], 'reference' => null,
                        'paid_on' => Carbon::parse($due)->subDays(mt_rand(0, 10))->toDateString(), 'received_by' => $accountant->id]);
                    $invoice->refreshStatus();
                }
            }
        }

        // ---- Homework ----------------------------------------------------------------------------
        $hwTemplates = [
            ['Complete exercise %s from the textbook', ['3.2', '4.1', '5.3', '2.4', '6.2']],
            ['Write a short essay on "%s"', ['My School', 'Water Conservation', 'Digital India', 'Festivals of Jharkhand', 'Swachh Bharat']],
            ['Revise chapter %s and solve the worksheet', ['5', '7', '2', '9', '4']],
            ['Prepare a project chart on %s', ['Pollution', 'the Solar System', 'Our Constitution', 'Healthy Food', 'Coal Mining in Jharkhand']],
        ];
        foreach ($sections as $sec) {
            $subs = Subject::where('class_id', $sec->class_id)->get();
            for ($h = 0; $h < 4; $h++) {
                $sub = $subs[$h % $subs->count()];
                $hw = Homework::create(['class_id' => $sec->class_id, 'section_id' => $sec->id, 'subject_id' => $sub->id, 'teacher_id' => $sub->teacher_id,
                    'title' => sprintf($hwTemplates[$h][0], $hwTemplates[$h][1][$sec->id % count($hwTemplates[$h][1])]),
                    'description' => "Do it neatly in your {$sub->name} notebook. Bring it to class on the due date.",
                    'assigned_on' => Carbon::today()->subDays(6 - $h * 2)->toDateString(), 'due_date' => Carbon::today()->addDays($h * 2 - 2)->toDateString()]);
                if ($h < 2) {
                    foreach (Student::where('section_id', $sec->id)->limit(4)->get() as $st) {
                        HomeworkSubmission::create(['homework_id' => $hw->id, 'student_id' => $st->id, 'content' => 'Completed in notebook. Pages 12-14.',
                            'submitted_at' => now()->subDays(2), 'status' => $h === 0 ? 'checked' : 'submitted', 'grade' => $h === 0 ? 'A' : null,
                            'feedback' => $h === 0 ? 'Good work, keep it up.' : null]);
                    }
                }
            }
        }

        // ---- Notices & events ----------------------------------------------------------------------
        foreach ([
            ['Half Yearly results published', 'Results of the Half Yearly Examination are now available in the Parent and Student apps. Parent-teacher meeting on Saturday.', 'all', true],
            ['Diwali & Chhath vacation', 'The school will remain closed from 6 November to 12 November 2026 for Diwali and Chhath Puja. Classes resume on 13 November.', 'all', true],
            ['Q3 fee reminder', 'Quarter 3 (Oct-Dec) tuition and transport fees were due on 15 October. Please clear dues to avoid late fine.', 'parents', false],
            ['Staff meeting', 'All teaching staff to assemble in the conference room on Friday at 2:30 PM for the Term 2 planning meeting.', 'staff', false],
            ['Science exhibition', 'Students of classes 6 to 12 can register for the inter-house science exhibition with their class teacher by 5 November.', 'students', false],
            ['Winter uniform', 'Winter uniform is compulsory from 15 November. Navy blue blazer and grey trousers/skirt.', 'all', false],
        ] as $i => [$t, $b, $a, $pin]) {
            Notice::create(['title' => $t, 'body' => $b, 'audience' => $a, 'is_pinned' => $pin, 'posted_by' => $admin->id, 'publish_date' => Carbon::today()->subDays($i * 2)->toDateString()]);
        }
        foreach ([
            ['Parent-Teacher Meeting', 'meeting', 4, 'School auditorium'], ['Diwali & Chhath vacation', 'holiday', 10, null],
            ["Children's Day celebration", 'event', 18, 'School ground'], ['Inter-house Science Exhibition', 'event', 25, 'Science block'],
            ['Annual Sports Day', 'event', 40, 'School ground'], ['Periodic Test 2', 'exam', 41, null],
        ] as [$t, $type, $d, $loc]) {
            Event::create(['title' => $t, 'type' => $type, 'start_date' => Carbon::today()->addDays($d)->toDateString(),
                'end_date' => $type === 'holiday' ? Carbon::today()->addDays($d + 6)->toDateString() : null, 'location' => $loc, 'description' => $t.' - all are requested to note the date.']);
        }

        // ---- Library ------------------------------------------------------------------------------
        $books = [
            ['NCERT Mathematics Class 10', 'NCERT', 'Textbook'], ['NCERT Science Class 8', 'NCERT', 'Textbook'], ['Wings of Fire', 'A. P. J. Abdul Kalam', 'Biography'],
            ['Godan', 'Munshi Premchand', 'Hindi Literature'], ['The Discovery of India', 'Jawaharlal Nehru', 'History'], ['Malgudi Days', 'R. K. Narayan', 'Fiction'],
            ['Concepts of Physics Vol 1', 'H. C. Verma', 'Reference'], ['Concepts of Physics Vol 2', 'H. C. Verma', 'Reference'], ['Objective General Knowledge', 'Lucent', 'GK'],
            ['Panchatantra Stories', 'Vishnu Sharma', 'Children'], ['Gitanjali', 'Rabindranath Tagore', 'Poetry'], ['Ignited Minds', 'A. P. J. Abdul Kalam', 'Biography'],
            ['Organic Chemistry', 'Morrison & Boyd', 'Reference'], ['Rashmirathi', 'Ramdhari Singh Dinkar', 'Hindi Literature'], ['The Story of My Experiments with Truth', 'M. K. Gandhi', 'Biography'],
            ['Computer Science with Python Class 12', 'Sumita Arora', 'Textbook'], ['Oxford English Dictionary', 'Oxford', 'Reference'], ['Ruskin Bond Omnibus', 'Ruskin Bond', 'Fiction'],
            ['Tenali Raman Stories', 'Various', 'Children'], ['Indian Polity', 'M. Laxmikanth', 'Reference'],
        ];
        $bookModels = collect($books)->map(fn ($b, $i) => Book::create(['title' => $b[0], 'author' => $b[1], 'category' => $b[2], 'isbn' => '978-81-'.str_pad((string) (1000 + $i * 37), 4, '0', STR_PAD_LEFT).'-'.($i % 10),
            'publisher' => $b[1] === 'NCERT' ? 'NCERT' : null, 'total_copies' => $b[2] === 'Textbook' ? 10 : 3, 'available_copies' => $b[2] === 'Textbook' ? 10 : 3, 'rack' => 'R'.(1 + $i % 6)]));
        $demoStudentUser = User::where('email', 'student@davdugda.in')->first();
        $borrowers = collect([$demoStudentUser])->merge(User::where('role', User::STUDENT)->inRandomOrder()->limit(8)->get())->merge([$teachers['Mathematics']]);
        foreach ($borrowers as $i => $bu) {
            $book = $bookModels[($i * 3) % $bookModels->count()];
            $issued = Carbon::today()->subDays(5 + $i * 2);
            BookIssue::create(['book_id' => $book->id, 'user_id' => $bu->id, 'issued_on' => $issued->toDateString(), 'due_on' => $issued->copy()->addDays(14)->toDateString(), 'issued_by' => $librarian->id]);
            $book->decrement('available_copies');
        }

        // ---- Leaves & messages -------------------------------------------------------------------
        $aarav = Student::whereHas('user', fn ($q) => $q->where('email', 'student@davdugda.in'))->first();
        LeaveRequest::create(['user_id' => $demoParent->id, 'student_id' => $aarav->id, 'type' => 'sick', 'from_date' => Carbon::today()->addDay()->toDateString(), 'to_date' => Carbon::today()->addDays(2)->toDateString(), 'reason' => 'Aarav has fever, doctor advised rest for two days.']);
        LeaveRequest::create(['user_id' => $teachers['English']->id, 'type' => 'casual', 'from_date' => Carbon::today()->addDays(5)->toDateString(), 'to_date' => Carbon::today()->addDays(5)->toDateString(), 'reason' => 'Family function in Dhanbad.']);
        LeaveRequest::create(['user_id' => $teachers['Mathematics']->id, 'type' => 'sick', 'from_date' => Carbon::today()->subDays(12)->toDateString(), 'to_date' => Carbon::today()->subDays(11)->toDateString(), 'reason' => 'Viral fever.', 'status' => 'approved', 'reviewed_by' => $admin->id, 'review_note' => 'Get well soon']);
        $classTeacher = User::find($aarav->section->teacher_id);
        Message::create(['sender_id' => $classTeacher->id, 'receiver_id' => $demoParent->id, 'subject' => 'Aarav - Half yearly performance', 'body' => 'Namaste, Aarav has done well in the half yearly exams. Please make sure he practises Mathematics daily. Regards.', 'created_at' => now()->subDays(2)]);
        Message::create(['sender_id' => $demoParent->id, 'receiver_id' => $classTeacher->id, 'subject' => 'Re: Aarav - Half yearly performance', 'body' => 'Thank you madam/sir, we will make sure he practises daily.', 'created_at' => now()->subDay(), 'read_at' => now()]);
        Message::create(['sender_id' => $admin->id, 'receiver_id' => $teachers['Mathematics']->id, 'subject' => 'Timetable change', 'body' => 'From Monday you will also take Class 10-B period 3. Please check the timetable in the app.', 'created_at' => now()->subHours(5)]);

        $this->command?->info('Demo data seeded for DAV Public School, Dugda.');
        $this->command?->table(['App', 'Login', 'Password'], [
            ['School (admin)', 'admin@davdugda.in', 'admin123'],
            ['Staff (teacher)', 'teacher@davdugda.in', 'teacher123'],
            ['Staff (accountant)', 'accountant@davdugda.in', 'accountant123'],
            ['Staff (librarian)', 'librarian@davdugda.in', 'librarian123'],
            ['Parent', 'parent@davdugda.in  or  9431000001', 'parent123'],
            ['Student', 'student@davdugda.in', 'student123'],
        ]);
    }

    private function user(string $name, string $email, string $role, string $password, string $gender, array $staff = []): User
    {
        $u = User::create(['name' => $name, 'email' => $email, 'phone' => (string) ($this->phone++), 'role' => $role, 'gender' => $gender,
            'address' => 'Dugda, Bokaro, Jharkhand 828404', 'password' => $password]);
        if (in_array($role, User::STAFF_ROLES, true)) {
            Staff::create(['user_id' => $u->id, 'employee_id' => sprintf('EMP%04d', Staff::count() + 1), 'designation' => $staff['designation'] ?? ucfirst($role),
                'department' => $staff['department'] ?? null, 'joining_date' => Carbon::create(2010 + mt_rand(0, 14), mt_rand(1, 12), 1)->toDateString(),
                'qualification' => $staff['qualification'] ?? 'Graduate', 'salary' => $staff['salary'] ?? 20000]);
        }
        return $u;
    }
}
