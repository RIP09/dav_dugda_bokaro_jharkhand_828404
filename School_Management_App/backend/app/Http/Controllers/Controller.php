<?php

namespace App\Http\Controllers;

use App\Modules\Students\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;

abstract class Controller
{
    protected function ok($data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $body = ['data' => $data];
        if ($message) $body['message'] = $message;
        return response()->json($body, $status);
    }

    protected function created($data, string $message = 'Created'): JsonResponse
    {
        return $this->ok($data, $message, 201);
    }

    protected function fail(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }

    /** Admin and staff can see any student; parents only their children; students only themselves. */
    protected function canSeeStudent(User $user, Student $student): bool
    {
        if ($user->isAdmin() || $user->isStaff()) return true;
        return in_array($student->id, $user->ownStudentIds(), true);
    }

    protected function ensureStudent(User $user, Student $student): void
    {
        abort_unless($this->canSeeStudent($user, $student), 403, 'You are not allowed to view this student.');
    }

    /** Resolve the student a parent/student request is about (?student_id= for parents with several children). */
    protected function resolveOwnStudent(User $user, $studentId = null): ?Student
    {
        $ids = $user->ownStudentIds();
        if (! $ids) return null;
        $id = $studentId && in_array((int) $studentId, $ids, true) ? (int) $studentId : $ids[0];
        return Student::with(['user', 'schoolClass', 'section'])->find($id);
    }
}
