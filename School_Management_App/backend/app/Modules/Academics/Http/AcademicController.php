<?php

namespace App\Modules\Academics\Http;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\AcademicYear;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Settings\Models\Setting;
use App\Modules\Academics\Models\Subject;
use Illuminate\Http\Request;

class AcademicController extends Controller
{
    // ---- Classes -------------------------------------------------------------------------------
    public function classes()
    {
        return $this->ok(SchoolClass::with(['sections.teacher', 'sections.schoolClass', 'subjects.teacher'])->withCount('students')
            ->orderBy('numeric_order')->get());
    }

    public function storeClass(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:50|unique:school_classes,name',
            'numeric_order' => 'nullable|integer',
            'group' => 'nullable|string|max:30',
            'sections' => 'nullable|array',          // e.g. ["A","B"]
            'sections.*' => 'string|max:20',
        ]);
        $class = SchoolClass::create([
            'name' => $data['name'],
            'numeric_order' => $data['numeric_order'] ?? (SchoolClass::max('numeric_order') + 1),
            'group' => $data['group'] ?? null,
        ]);
        foreach ($data['sections'] ?? ['A'] as $s) $class->sections()->create(['name' => $s]);
        return $this->created($class->load('sections'), 'Class created');
    }

    public function updateClass(Request $request, SchoolClass $class)
    {
        $class->update($request->validate([
            'name' => 'sometimes|string|max:50|unique:school_classes,name,'.$class->id,
            'numeric_order' => 'sometimes|integer',
            'group' => 'sometimes|nullable|string|max:30',
        ]));
        return $this->ok($class->load('sections'), 'Class updated');
    }

    public function destroyClass(SchoolClass $class)
    {
        if ($class->students()->exists()) return $this->fail('This class still has students. Move them first.');
        $class->delete();
        return $this->ok(null, 'Class deleted');
    }

    // ---- Sections ------------------------------------------------------------------------------
    public function sections(Request $request)
    {
        $q = Section::with(['schoolClass', 'teacher'])->withCount('students');
        if ($request->filled('class_id')) $q->where('class_id', $request->class_id);
        return $this->ok($q->orderBy('class_id')->orderBy('name')->get());
    }

    public function storeSection(Request $request)
    {
        $data = $request->validate([
            'class_id' => 'required|exists:school_classes,id',
            'name' => 'required|string|max:20',
            'stream' => 'nullable|in:'.implode(',', Section::STREAMS),
            'capacity' => 'nullable|integer|min:1',
            'teacher_id' => 'nullable|exists:users,id',
            'room' => 'nullable|string|max:30',
        ]);
        if (Section::where('class_id', $data['class_id'])->where('name', $data['name'])->exists()) {
            return $this->fail('That section already exists in this class.');
        }
        if (! empty($data['stream']) && ! SchoolClass::isSeniorSecondary(SchoolClass::find($data['class_id']))) {
            return $this->fail('Stream (Science / Commerce) is only for Class XI and XII.');
        }
        return $this->created(Section::create($data)->load('teacher', 'schoolClass'), 'Section created');
    }

    public function updateSection(Request $request, Section $section)
    {
        $section->update($request->validate([
            'name' => 'sometimes|string|max:20',
            'stream' => 'sometimes|nullable|in:'.implode(',', Section::STREAMS),
            'capacity' => 'sometimes|integer|min:1',
            'teacher_id' => 'sometimes|nullable|exists:users,id',
            'room' => 'sometimes|nullable|string|max:30',
        ]));
        return $this->ok($section->load('teacher', 'schoolClass'), 'Section updated');
    }

    public function destroySection(Section $section)
    {
        if ($section->students()->exists()) return $this->fail('This section still has students.');
        $section->delete();
        return $this->ok(null, 'Section deleted');
    }

    // ---- Subjects ------------------------------------------------------------------------------
    public function subjects(Request $request)
    {
        $q = Subject::with(['teacher', 'schoolClass']);
        if ($request->filled('class_id')) $q->where('class_id', $request->class_id);
        if ($request->boolean('mine')) $q->where('teacher_id', $request->user()->id);
        return $this->ok($q->orderBy('class_id')->orderBy('name')->get());
    }

    public function storeSubject(Request $request)
    {
        $data = $request->validate([
            'class_id' => 'required|exists:school_classes,id',
            'name' => 'required|string|max:80',
            'code' => 'nullable|string|max:20',
            'type' => 'nullable|in:theory,practical,both',
            'teacher_id' => 'nullable|exists:users,id',
        ]);
        return $this->created(Subject::create($data)->load('teacher'), 'Subject added');
    }

    public function updateSubject(Request $request, Subject $subject)
    {
        $subject->update($request->validate([
            'name' => 'sometimes|string|max:80',
            'code' => 'sometimes|nullable|string|max:20',
            'type' => 'sometimes|in:theory,practical,both',
            'teacher_id' => 'sometimes|nullable|exists:users,id',
        ]));
        return $this->ok($subject->load('teacher'), 'Subject updated');
    }

    public function destroySubject(Subject $subject)
    {
        $subject->delete();
        return $this->ok(null, 'Subject deleted');
    }

    // ---- Academic years ------------------------------------------------------------------------
    public function years()
    {
        return $this->ok(AcademicYear::orderByDesc('start_date')->get());
    }

    public function storeYear(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:50',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'nullable|boolean',
        ]);
        if (! empty($data['is_current'])) AcademicYear::query()->update(['is_current' => false]);
        return $this->created(AcademicYear::create($data), 'Session created');
    }

    public function makeCurrentYear(AcademicYear $year)
    {
        AcademicYear::query()->update(['is_current' => false]);
        $year->update(['is_current' => true]);
        return $this->ok($year, $year->title.' is now the current session');
    }

    // ---- Settings (school profile) ---------------------------------------------------------------
    public function settings()
    {
        $s = Setting::allAsArray();
        $s['current_session'] = AcademicYear::current()?->title;
        return $this->ok($s);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'school_name' => 'sometimes|string|max:150',
            'school_short_name' => 'sometimes|string|max:50',
            'address' => 'sometimes|string|max:300',
            'phone' => 'sometimes|string|max:30',
            'email' => 'sometimes|email',
            'principal' => 'sometimes|string|max:100',
            'affiliation' => 'sometimes|string|max:100',
            'website' => 'sometimes|nullable|string|max:150',
            'late_fine_per_day' => 'sometimes|numeric',
            'library_fine_per_day' => 'sometimes|numeric',
            'library_issue_days' => 'sometimes|integer',
        ]);
        foreach ($data as $k => $v) Setting::put($k, $v);
        return $this->settings();
    }
}
