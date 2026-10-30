<?php

namespace Website\Modules\Cms;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Website\Modules\Admissions\Enquiry;
use Website\Modules\Contact\ContactMessage;
use Website\Support\ChangeLog;

/** Admission enquiries and contact-form messages sent from the public website. */
class InboxController extends Controller
{
    protected function box(string $box): array
    {
        return match ($box) {
            'enquiries' => [Enquiry::class, 'Admission Enquiries', ['student_name', 'parent_name', 'phone', 'email', 'class_sought', 'dob', 'previous_school', 'message', 'status', 'notes', 'created_at']],
            'messages' => [ContactMessage::class, 'Contact Messages', ['name', 'email', 'phone', 'subject', 'message', 'status', 'notes', 'created_at']],
            default => abort(404),
        };
    }

    public function index(Request $request, string $box)
    {
        [$model, $title] = $this->box($box);
        $q = $model::query()->orderByRaw("CASE WHEN status = 'new' THEN 0 ELSE 1 END")->orderByDesc('id');
        if (in_array($request->query('status'), ['new', 'handled'], true)) {
            $q->where('status', $request->query('status'));
        }
        if ($s = trim((string) $request->query('q'))) {
            $q->where(function ($w) use ($s, $box) {
                foreach ($box === 'enquiries' ? ['student_name', 'parent_name', 'phone', 'email'] : ['name', 'email', 'phone', 'subject', 'message'] as $c) {
                    $w->orWhere($c, 'like', "%$s%");
                }
            });
        }

        return view('website::cms.inbox.index', ['box' => $box, 'title' => $title, 'items' => $q->paginate(25)->withQueryString()]);
    }

    public function show(string $box, int $id)
    {
        [$model, $title] = $this->box($box);

        return view('website::cms.inbox.show', ['box' => $box, 'title' => $title, 'item' => $model::findOrFail($id)]);
    }

    public function update(Request $request, string $box, int $id)
    {
        [$model] = $this->box($box);
        $item = $model::findOrFail($id);
        $data = $request->validate(['status' => 'required|in:new,handled', 'notes' => 'nullable|string|max:3000']);
        $item->fill($data);
        if ($item->isDirty('status')) {
            $item->handled_by = $data['status'] === 'handled' ? Auth::guard('webadmin')->id() : null;
            $item->handled_at = $data['status'] === 'handled' ? now() : null;
        }
        $item->save();
        ChangeLog::record('updated', $box, $item, ChangeLog::diff($item), $item->student_name ?? $item->name);

        return redirect()->route('webadmin.inbox', $box)->with('ok', 'Saved.');
    }

    public function destroy(string $box, int $id)
    {
        [$model] = $this->box($box);
        $item = $model::findOrFail($id);
        ChangeLog::record('deleted', $box, $item, [], $item->student_name ?? $item->name);
        $item->delete();

        return redirect()->route('webadmin.inbox', $box)->with('ok', 'Deleted.');
    }

    public function export(string $box)
    {
        [$model, $title, $cols] = $this->box($box);
        $name = str_replace(' ', '_', strtolower($title)).'_'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($model, $cols) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // so Excel opens Hindi / special characters correctly
            fputcsv($out, array_map(fn ($c) => ucwords(str_replace('_', ' ', $c)), $cols));
            $model::query()->orderByDesc('id')->chunk(500, function ($rows) use ($out, $cols) {
                foreach ($rows as $r) {
                    fputcsv($out, array_map(fn ($c) => $r->{$c} instanceof \DateTimeInterface ? $r->{$c}->format('Y-m-d H:i') : $r->{$c}, $cols));
                }
            });
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
