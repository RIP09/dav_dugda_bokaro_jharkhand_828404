<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    private const WITH = ['user', 'schoolClass', 'section', 'parent', 'transportRoute'];

    /** GET /students?class_id=&section_id=&search=  (admin & staff: all; parent: children; student: self) */
    public function index(Request $request)
    {
        $user = $request->user();
        $q = Student::with(self::WITH)->orderBy('class_id')->orderBy('section_id')->orderBy('roll_no');
        if (! $user->isAdmin() && ! $user->isStaff()) {
            $q->whereIn('id', $user->ownStudentIds());
        }
        if ($request->filled('class_id')) $q->where('class_id', $request->class_id);
        if ($request->filled('section_id')) $q->where('section_id', $request->section_id);
        if ($request->filled('status')) $q->where('status', $request->status);
        if ($s = $request->query('search')) {
            $q->where(fn ($w) => $w->where('admission_no', 'like', "%$s%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%$s%")->orWhere('phone', 'like', "%$s%")));
        }
        return $this->ok($q->limit(500)->get());
    }

    /** GET /students/{id} — profile plus attendance and fee summary */
    public function show(Request $request, Student $student)
    {
        $this->ensureStudent($request->user(), $student);
        $student->load(self::WITH);
        $att = StudentAttendance::where('student_id', $student->id)
            ->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $total = $att->sum();
        $inv = Invoice::where('student_id', $student->id)->get();
        $data = $student->toArray();
        $data['attendance_summary'] = [
            'present' => (int) ($att['present'] ?? 0) + (int) ($att['late'] ?? 0),
            'absent' => (int) ($att['absent'] ?? 0),
            'total' => (int) $total,
            'percentage' => $total ? round((($att['present'] ?? 0) + ($att['late'] ?? 0) + 0.5 * ($att['half_day'] ?? 0)) * 100 / $total, 1) : 0,
        ];
        $data['fee_summary'] = [
            'total' => round($inv->sum(fn ($i) => $i->amount - $i->discount), 2),
            'paid' => round($inv->sum('paid'), 2),
            'due' => round($inv->sum('balance'), 2),
        ];
        return $this->ok($data);
    }

    /**
     * POST /students (admin) — admits a student: creates the student login and, if needed, the parent login.
     * Parent: pass parent_id of an existing parent, or parent_name + parent_phone (+ parent_email) to create one.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'nullable|string|max:20|unique:users,phone',
            'password' => 'nullable|string|min:6',
            'gender' => 'nullable|in:male,female,other',
            'dob' => 'nullable|date',
            'address' => 'nullable|string|max:500',
            'admission_no' => 'nullable|string|max:30|unique:students,admission_no',
            'roll_no' => 'nullable|integer',
            'class_id' => 'required|exists:school_classes,id',
            'section_id' => ['required', Rule::exists('sections', 'id')->where('class_id', $request->class_id)],
            'admission_date' => 'nullable|date',
            'blood_group' => 'nullable|string|max:5',
            'religion' => 'nullable|string|max:30',
            'category' => 'nullable|string|max:20',
            'aadhaar_no' => 'nullable|string|max:20',
            'father_name' => 'nullable|string|max:100',
            'mother_name' => 'nullable|string|max:100',
            'guardian_phone' => 'nullable|string|max:20',
            'house' => 'nullable|string|max:30',
            'transport_route_id' => 'nullable|exists:transport_routes,id',
            'parent_id' => ['nullable', Rule::exists('users', 'id')->where('role', User::PARENT)],
            'parent_name' => 'nullable|string|max:120',
            'parent_phone' => 'nullable|string|max:20',
            'parent_email' => 'nullable|email',
            'parent_password' => 'nullable|string|min:6',
        ]);

        $student = DB::transaction(function () use ($data) {
            $admissionNo = $data['admission_no'] ?? $this->nextAdmissionNo();
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'role' => User::STUDENT,
                'gender' => $data['gender'] ?? null,
                'dob' => $data['dob'] ?? null,
                'address' => $data['address'] ?? null,
                // Students sign in with email/phone, or admission number as phone-less login (see README)
                'password' => $data['password'] ?? 'student123',
            ]);
            if (! $user->email && ! $user->phone) {
                $user->update(['email' => strtolower($admissionNo).'@student.davdugda.in']);
            }

            $parentId = $data['parent_id'] ?? null;
            if (! $parentId && ! empty($data['parent_phone'])) {
                $parent = User::where('phone', $data['parent_phone'])->first();
                if ($parent && $parent->role !== User::PARENT) {
                    abort(422, 'That parent phone number belongs to a non-parent account.');
                }
                $parent ??= User::create([
                    'name' => $data['parent_name'] ?? ($data['father_name'] ?? 'Parent of '.$data['name']),
                    'phone' => $data['parent_phone'],
                    'email' => $data['parent_email'] ?? null,
                    'role' => User::PARENT,
                    'address' => $data['address'] ?? null,
                    'password' => $data['parent_password'] ?? 'parent123',
                ]);
                $parentId = $parent->id;
            }

            $rollNo = $data['roll_no'] ?? ((int) Student::where('section_id', $data['section_id'])->max('roll_no') + 1);

            return Student::create([
                'user_id' => $user->id,
                'admission_no' => $admissionNo,
                'roll_no' => $rollNo,
                'class_id' => $data['class_id'],
                'section_id' => $data['section_id'],
                'academic_year_id' => AcademicYear::current()?->id,
                'parent_id' => $parentId,
                'transport_route_id' => $data['transport_route_id'] ?? null,
                'admission_date' => $data['admission_date'] ?? now()->toDateString(),
                'blood_group' => $data['blood_group'] ?? null,
                'religion' => $data['religion'] ?? null,
                'category' => $data['category'] ?? null,
                'aadhaar_no' => $data['aadhaar_no'] ?? null,
                'father_name' => $data['father_name'] ?? null,
                'mother_name' => $data['mother_name'] ?? null,
                'guardian_phone' => $data['guardian_phone'] ?? ($data['parent_phone'] ?? null),
                'house' => $data['house'] ?? null,
            ]);
        });

        return $this->created($student->load(self::WITH), 'Student admitted');
    }

    public function update(Request $request, Student $student)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:120',
            'email' => 'sometimes|nullable|email|unique:users,email,'.$student->user_id,
            'phone' => 'sometimes|nullable|string|max:20|unique:users,phone,'.$student->user_id,
            'gender' => 'sometimes|nullable|in:male,female,other',
            'dob' => 'sometimes|nullable|date',
            'address' => 'sometimes|nullable|string|max:500',
            'roll_no' => 'sometimes|nullable|integer',
            'class_id' => 'sometimes|exists:school_classes,id',
            'section_id' => 'sometimes|exists:sections,id',
            'blood_group' => 'sometimes|nullable|string|max:5',
            'father_name' => 'sometimes|nullable|string|max:100',
            'mother_name' => 'sometimes|nullable|string|max:100',
            'guardian_phone' => 'sometimes|nullable|string|max:20',
            'house' => 'sometimes|nullable|string|max:30',
            'transport_route_id' => 'sometimes|nullable|exists:transport_routes,id',
            'parent_id' => ['sometimes', 'nullable', Rule::exists('users', 'id')->where('role', User::PARENT)],
            'status' => 'sometimes|in:active,left,graduated',
        ]);
        $userFields = array_intersect_key($data, array_flip(['name', 'email', 'phone', 'gender', 'dob', 'address']));
        if ($userFields) $student->user->update($userFields);
        $student->update(array_diff_key($data, $userFields));
        return $this->ok($student->fresh()->load(self::WITH), 'Student updated');
    }

    public function destroy(Student $student)
    {
        $student->user()->delete(); // cascades to the student row
        return $this->ok(null, 'Student removed');
    }

    /** POST /students/promote (admin) { from_section_id, to_class_id, to_section_id } — like lav_sms promotions */
    public function promote(Request $request)
    {
        $data = $request->validate([
            'from_section_id' => 'required|exists:sections,id',
            'to_class_id' => 'required|exists:school_classes,id',
            'to_section_id' => ['required', Rule::exists('sections', 'id')->where('class_id', $request->to_class_id)],
            'student_ids' => 'nullable|array',
        ]);
        $q = Student::where('section_id', $data['from_section_id'])->where('status', 'active');
        if (! empty($data['student_ids'])) $q->whereIn('id', $data['student_ids']);
        $n = $q->update(['class_id' => $data['to_class_id'], 'section_id' => $data['to_section_id'], 'academic_year_id' => AcademicYear::current()?->id]);
        return $this->ok(['promoted' => $n], "$n students promoted");
    }

    private function nextAdmissionNo(): string
    {
        $year = now()->format('Y');
        $n = Student::where('admission_no', 'like', "DAV{$year}%")->count() + 1;
        do {
            $no = sprintf('DAV%s%04d', $year, $n++);
        } while (Student::where('admission_no', $no)->exists());
        return $no;
    }
}
