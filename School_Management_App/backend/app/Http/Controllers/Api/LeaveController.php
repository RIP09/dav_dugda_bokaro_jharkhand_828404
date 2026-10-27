<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

/** Leave requests — staff for themselves, parents for a child, students for themselves (hrshadhin leaves). */
class LeaveController extends Controller
{
    /** GET /leaves?status=&scope=mine|review */
    public function index(Request $request)
    {
        $u = $request->user();
        $q = LeaveRequest::with(['applicant', 'student.user', 'reviewer'])->latest('id');
        $scope = $request->query('scope', $u->isAdmin() ? 'review' : 'mine');
        if ($scope === 'review' && $u->isAdmin()) {
            // all
        } elseif ($scope === 'review' && $u->role === User::TEACHER) {
            $q->whereHas('student', fn ($s) => $s->whereIn('section_id', $u->teachingSectionIds()));
        } elseif ($u->role === User::PARENT) {
            $q->where(fn ($w) => $w->where('user_id', $u->id)->orWhereIn('student_id', $u->ownStudentIds()));
        } else {
            $q->where('user_id', $u->id);
        }
        if ($request->filled('status')) $q->where('status', $request->status);
        return $this->ok($q->limit(200)->get());
    }

    public function store(Request $request)
    {
        $u = $request->user();
        $data = $request->validate([
            'type' => 'nullable|in:casual,sick,other',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'reason' => 'required|string|max:500',
            'student_id' => 'nullable|exists:students,id',
        ]);
        if ($u->role === User::PARENT) {
            $data['student_id'] = $this->resolveOwnStudent($u, $data['student_id'] ?? null)?->id;
            if (! $data['student_id']) return $this->fail('No child is linked to this account.');
        } elseif ($u->role === User::STUDENT) {
            $data['student_id'] = $u->student?->id;
        } else {
            $data['student_id'] = null;
        }
        $leave = LeaveRequest::create($data + ['user_id' => $u->id, 'type' => $data['type'] ?? 'casual']);
        return $this->created($leave->load(['applicant', 'student.user']), 'Leave request sent');
    }

    /** POST /leaves/{leave}/review { status: approved|rejected, note? } */
    public function review(Request $request, LeaveRequest $leave)
    {
        $u = $request->user();
        $data = $request->validate(['status' => 'required|in:approved,rejected', 'note' => 'nullable|string|max:300']);
        $allowed = $u->isAdmin() || ($u->role === User::TEACHER && $leave->student && in_array($leave->student->section_id, $u->teachingSectionIds(), true));
        if (! $allowed) return $this->fail('You cannot review this leave.', 403);
        $leave->update(['status' => $data['status'], 'review_note' => $data['note'] ?? null, 'reviewed_by' => $u->id]);

        // Approved student leave marks those days as "leave" in attendance (unless already marked).
        if ($data['status'] === 'approved' && $leave->student) {
            foreach (CarbonPeriod::create($leave->from_date, $leave->to_date) as $d) {
                if ($d->isSunday()) continue;
                StudentAttendance::firstOrCreate(['student_id' => $leave->student_id, 'date' => $d->toDateString()], [
                    'class_id' => $leave->student->class_id, 'section_id' => $leave->student->section_id,
                    'status' => 'leave', 'remark' => 'Approved leave', 'marked_by' => $u->id,
                ]);
            }
        }
        return $this->ok($leave->fresh(['applicant', 'student.user', 'reviewer']), 'Leave '.$data['status']);
    }

    public function destroy(Request $request, LeaveRequest $leave)
    {
        if ($leave->user_id !== $request->user()->id || $leave->status !== 'pending') return $this->fail('Only your own pending request can be cancelled.', 403);
        $leave->delete();
        return $this->ok(null, 'Leave request cancelled');
    }
}
