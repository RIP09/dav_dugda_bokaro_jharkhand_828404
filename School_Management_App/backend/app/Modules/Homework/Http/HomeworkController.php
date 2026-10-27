<?php

namespace App\Modules\Homework\Http;

use App\Http\Controllers\Controller;
use App\Modules\Homework\Models\Homework;
use App\Modules\Homework\Models\HomeworkSubmission;
use App\Modules\Academics\Models\Section;
use App\Models\User;
use Illuminate\Http\Request;

class HomeworkController extends Controller
{
    private const WITH = ['subject', 'teacher', 'schoolClass', 'section'];

    /** GET /homework?section_id=&student_id= */
    public function index(Request $request)
    {
        $user = $request->user();
        $q = Homework::with(self::WITH)->withCount('submissions')->latest('assigned_on')->latest('id');
        if (in_array($user->role, [User::PARENT, User::STUDENT], true)) {
            $s = $this->resolveOwnStudent($user, $request->query('student_id'));
            if (! $s) return $this->ok([]);
            $q->where('class_id', $s->class_id)->where(fn ($w) => $w->whereNull('section_id')->orWhere('section_id', $s->section_id));
            $subs = HomeworkSubmission::where('student_id', $s->id)->get()->keyBy('homework_id');
            return $this->ok($q->limit(200)->get()->map(fn ($h) => array_merge($h->toArray(), ['my_submission' => $subs[$h->id] ?? null])));
        }
        if ($user->role === User::TEACHER && ! $request->filled('section_id')) $q->where('teacher_id', $user->id);
        if ($request->filled('section_id')) $q->where('section_id', $request->section_id);
        return $this->ok($q->limit(200)->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'title' => 'required|string|max:150',
            'description' => 'nullable|string|max:5000',
            'due_date' => 'nullable|date|after_or_equal:today',
        ]);
        $section = Section::findOrFail($data['section_id']);
        $hw = Homework::create($data + ['class_id' => $section->class_id, 'teacher_id' => $request->user()->id, 'assigned_on' => now()->toDateString()]);
        return $this->created($hw->load(self::WITH), 'Homework posted');
    }

    public function destroy(Request $request, Homework $homework)
    {
        $u = $request->user();
        if (! $u->isAdmin() && $homework->teacher_id !== $u->id) return $this->fail('Only the teacher who posted it can delete it.', 403);
        $homework->delete();
        return $this->ok(null, 'Homework deleted');
    }

    /** POST /homework/{homework}/submit { content } (student) */
    public function submit(Request $request, Homework $homework)
    {
        $data = $request->validate(['content' => 'required|string|max:5000']);
        $student = $request->user()->student;
        if (! $student) return $this->fail('Only students can submit homework.', 403);
        $sub = HomeworkSubmission::updateOrCreate(
            ['homework_id' => $homework->id, 'student_id' => $student->id],
            ['content' => $data['content'], 'submitted_at' => now(), 'status' => 'submitted']
        );
        return $this->ok($sub, 'Homework submitted');
    }

    /** GET /homework/{homework}/submissions (teacher/admin) */
    public function submissions(Homework $homework)
    {
        return $this->ok($homework->submissions()->with('student.user')->latest('submitted_at')->get());
    }

    /** POST /submissions/{submission}/review { status, grade, feedback } */
    public function review(Request $request, HomeworkSubmission $submission)
    {
        $submission->update($request->validate([
            'status' => 'required|in:checked,redo',
            'grade' => 'nullable|string|max:10',
            'feedback' => 'nullable|string|max:500',
        ]));
        return $this->ok($submission, 'Feedback saved');
    }
}
