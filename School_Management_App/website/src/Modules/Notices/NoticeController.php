<?php

namespace Website\Modules\Notices;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class NoticeController extends Controller
{
    public function index(Request $request)
    {
        $cat = in_array($request->query('type'), ['notice', 'circular', 'news'], true) ? $request->query('type') : null;
        $q = Notice::published()->when($cat, fn ($q) => $q->where('category', $cat))
            ->orderByDesc('is_pinned')->orderByDesc('published_at')->orderByDesc('id');
        if ($s = trim((string) $request->query('q'))) {
            $q->where(fn ($w) => $w->where('title', 'like', "%$s%")->orWhere('body', 'like', "%$s%"));
        }

        return view('website::site.notices.index', ['notices' => $q->paginate(12)->withQueryString(), 'type' => $cat]);
    }

    public function show(string $slug)
    {
        $notice = Notice::published()->where('slug', $slug)->firstOrFail();

        return view('website::site.notices.show', [
            'notice' => $notice,
            'recent' => Notice::published()->where('id', '!=', $notice->id)->orderByDesc('published_at')->limit(5)->get(),
        ]);
    }
}
