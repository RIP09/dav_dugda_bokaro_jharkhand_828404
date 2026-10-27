<?php

namespace App\Modules\Communication\Http;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Communication\Models\AppNotification;
use App\Modules\Communication\Models\Notice;
use App\Modules\Communication\Models\NoticeRead;
use Illuminate\Http\Request;

/** Free in-app notification centre (no SMS gateway or paid push needed) + notice read receipts. */
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $u = $request->user();
        $rows = AppNotification::where('user_id', $u->id)->latest('id')->limit(min(200, (int) $request->query('limit', 100)))->get();
        return response()->json(['data' => $rows->map(fn ($n) => [
            'id' => $n->id, 'title' => $n->title, 'body' => $n->body, 'module' => $n->module, 'record_id' => $n->record_id,
            'read_at' => $n->read_at?->format('Y-m-d H:i'), 'created_at' => $n->created_at?->format('Y-m-d H:i'),
        ]), 'meta' => ['unread' => AppNotification::where('user_id', $u->id)->whereNull('read_at')->count()]]);
    }

    public function unread(Request $request)
    {
        return $this->ok(['unread' => AppNotification::where('user_id', $request->user()->id)->whereNull('read_at')->count()]);
    }

    public function read(Request $request, int $id)
    {
        AppNotification::where('user_id', $request->user()->id)->whereKey($id)->update(['read_at' => now()]);
        return $this->ok(null, 'Marked read');
    }

    public function readAll(Request $request)
    {
        AppNotification::where('user_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);
        return $this->ok(null, 'All marked read');
    }

    public function markNoticeRead(Request $request, Notice $notice)
    {
        NoticeRead::firstOrCreate(['notice_id' => $notice->id, 'user_id' => $request->user()->id], ['read_at' => now()]);
        AppNotification::where('user_id', $request->user()->id)->where('module', 'notices')->where('record_id', $notice->id)->whereNull('read_at')->update(['read_at' => now()]);
        return $this->ok(['read' => true, 'read_count' => NoticeRead::where('notice_id', $notice->id)->count()], 'Marked read');
    }

    public function noticeReads(Request $request, Notice $notice)
    {
        $u = $request->user();
        abort_unless($u->isAdmin() || $notice->posted_by === $u->id || $u->role === 'teacher', 403);
        $roles = match ($notice->audience) { 'staff' => User::STAFF_ROLES, 'parents' => [User::PARENT], 'students' => [User::STUDENT], default => [User::ADMIN, ...User::STAFF_ROLES, User::PARENT, User::STUDENT] };
        $reads = NoticeRead::where('notice_id', $notice->id)->with('user:id,name,role')->get();
        $total = User::whereIn('role', $roles)->where('is_active', true)->count();
        return $this->ok([
            'read_count' => $reads->count(), 'audience_count' => $total,
            'readers' => $reads->map(fn ($r) => ['user_id' => $r->user_id, 'name' => $r->user?->name, 'role' => $r->user?->role, 'read_at' => $r->read_at?->format('Y-m-d H:i')])->values(),
        ]);
    }
}
