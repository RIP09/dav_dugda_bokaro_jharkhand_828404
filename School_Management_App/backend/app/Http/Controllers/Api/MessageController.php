<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /** GET /messages?box=inbox|sent */
    public function index(Request $request)
    {
        $u = $request->user();
        $q = Message::with(['sender', 'receiver'])->latest('id');
        $request->query('box') === 'sent' ? $q->where('sender_id', $u->id) : $q->where('receiver_id', $u->id);
        return $this->ok($q->limit(200)->get());
    }

    public function unread(Request $request)
    {
        return $this->ok(['count' => Message::where('receiver_id', $request->user()->id)->whereNull('read_at')->count()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['receiver_id' => 'required|exists:users,id', 'subject' => 'nullable|string|max:150', 'body' => 'required|string|max:5000']);
        if (! $this->contactIds($request->user())->contains((int) $data['receiver_id'])) {
            return $this->fail('You cannot message this person.', 403);
        }
        $m = Message::create($data + ['sender_id' => $request->user()->id]);
        return $this->created($m->load(['sender', 'receiver']), 'Message sent');
    }

    public function read(Request $request, Message $message)
    {
        if ($message->receiver_id === $request->user()->id && ! $message->read_at) $message->update(['read_at' => now()]);
        return $this->ok($message->load(['sender', 'receiver']));
    }

    /** GET /messages/contacts — who this user may write to */
    public function contacts(Request $request)
    {
        $users = User::whereIn('id', $this->contactIds($request->user()))->where('is_active', true)->orderBy('role')->orderBy('name')
            ->get(['id', 'name', 'role', 'phone']);
        return $this->ok($users);
    }

    private function contactIds(User $u)
    {
        $admins = User::where('role', User::ADMIN)->pluck('id');
        return match (true) {
            $u->isAdmin(), $u->isStaff() => User::where('id', '!=', $u->id)->pluck('id'),
            $u->role === User::PARENT => $admins->merge($this->teachersOf(Student::whereIn('id', $u->ownStudentIds())->pluck('section_id'))),
            $u->role === User::STUDENT => $admins->merge($this->teachersOf(collect([$u->student?->section_id]))),
            default => $admins,
        };
    }

    private function teachersOf($sectionIds)
    {
        $classTeachers = Section::whereIn('id', $sectionIds)->pluck('teacher_id');
        $subjectTeachers = \App\Models\TimetableEntry::whereIn('section_id', $sectionIds)->pluck('teacher_id');
        return $classTeachers->merge($subjectTeachers)->filter()->unique();
    }
}
