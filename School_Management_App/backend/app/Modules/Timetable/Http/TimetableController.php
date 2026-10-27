<?php

namespace App\Modules\Timetable\Http;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\Section;
use App\Modules\Timetable\Models\TimetableEntry;
use App\Models\User;
use Illuminate\Http\Request;

class TimetableController extends Controller
{
    private const WITH = ['subject', 'teacher', 'schoolClass', 'section'];

    /**
     * GET /timetable?section_id=        a section's week (anyone signed in)
     * GET /timetable?mine=1             teacher: own periods; student: own section; parent: child (?student_id=)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $q = TimetableEntry::with(self::WITH)->orderBy('day')->orderBy('start_time');
        if ($request->filled('section_id')) {
            $q->where('section_id', $request->section_id);
        } elseif ($user->role === User::TEACHER || ($user->isStaff() && $request->boolean('mine'))) {
            $q->where('teacher_id', $user->id);
        } elseif (in_array($user->role, [User::STUDENT, User::PARENT], true)) {
            $s = $this->resolveOwnStudent($user, $request->query('student_id'));
            if (! $s) return $this->ok([]);
            $q->where('section_id', $s->section_id);
        } else {
            $first = Section::orderBy('class_id')->first();
            $q->where('section_id', $first?->id ?? 0);
        }
        return $this->ok($q->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'nullable|exists:users,id',
            'day' => 'required|integer|between:1,7',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:30',
        ]);
        $section = Section::findOrFail($data['section_id']);
        $data['class_id'] = $section->class_id;
        $clash = TimetableEntry::where('day', $data['day'])
            ->where('start_time', '<', $data['end_time'])->where('end_time', '>', $data['start_time'])
            ->where(fn ($w) => $w->where('section_id', $data['section_id'])
                ->when($data['teacher_id'] ?? null, fn ($x, $t) => $x->orWhere('teacher_id', $t)))
            ->with(self::WITH)->first();
        if ($clash) {
            return $this->fail("Clashes with {$clash->subject_name} ({$clash->class_label}, {$clash->start_time}-{$clash->end_time}).");
        }
        return $this->created(TimetableEntry::create($data)->load(self::WITH), 'Period added');
    }

    public function destroy(TimetableEntry $entry)
    {
        $entry->delete();
        return $this->ok(null, 'Period removed');
    }
}
