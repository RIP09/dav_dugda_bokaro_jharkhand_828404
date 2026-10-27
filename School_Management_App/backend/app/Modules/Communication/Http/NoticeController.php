<?php

namespace App\Modules\Communication\Http;

use App\Http\Controllers\Controller;
use App\Modules\Communication\Models\Event;
use App\Modules\Communication\Models\Notice;
use App\Models\User;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    public function index(Request $request)
    {
        return $this->ok(Notice::with('author')->visibleTo($request->user())
            ->orderByDesc('is_pinned')->latest('publish_date')->latest('id')->limit(100)->get());
    }

    /** Admin may post to anyone; teachers may post to parents/students. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:150',
            'body' => 'required|string|max:10000',
            'audience' => 'nullable|in:all,staff,parents,students',
            'is_pinned' => 'nullable|boolean',
            'publish_date' => 'nullable|date',
        ]);
        $u = $request->user();
        if ($u->role === User::TEACHER && in_array($data['audience'] ?? 'all', ['all', 'staff'], true)) {
            $data['audience'] = 'parents';
        }
        $notice = Notice::create($data + ['posted_by' => $u->id, 'publish_date' => $data['publish_date'] ?? now()->toDateString()]);
        return $this->created($notice->load('author'), 'Notice published');
    }

    public function destroy(Request $request, Notice $notice)
    {
        $u = $request->user();
        if (! $u->isAdmin() && $notice->posted_by !== $u->id) return $this->fail('Not allowed.', 403);
        $notice->delete();
        return $this->ok(null, 'Notice deleted');
    }

    // ---- Events / calendar -------------------------------------------------------------------
    public function events(Request $request)
    {
        $q = Event::orderBy('start_date');
        if ($request->boolean('upcoming', false)) $q->where('start_date', '>=', now()->toDateString());
        return $this->ok($q->limit(200)->get());
    }

    public function storeEvent(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:150', 'description' => 'nullable|string|max:5000',
            'start_date' => 'required|date', 'end_date' => 'nullable|date|after_or_equal:start_date',
            'location' => 'nullable|string|max:150', 'type' => 'nullable|in:event,holiday,exam,meeting',
            'audience' => 'nullable|in:all,staff,parents,students',
        ]);
        return $this->created(Event::create($data), 'Event added');
    }

    public function destroyEvent(Event $event)
    {
        $event->delete();
        return $this->ok(null, 'Event deleted');
    }
}
