<?php

namespace App\Modules\Library\Http;

use App\Http\Controllers\Controller;
use App\Modules\Library\Models\Book;
use App\Modules\Library\Models\BookIssue;
use App\Modules\Settings\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LibraryController extends Controller
{
    public function books(Request $request)
    {
        $q = Book::orderBy('title');
        if ($s = $request->query('search')) {
            $q->where(fn ($w) => $w->where('title', 'like', "%$s%")->orWhere('author', 'like', "%$s%")->orWhere('isbn', 'like', "%$s%"));
        }
        if ($request->filled('category')) $q->where('category', $request->category);
        return $this->ok($q->limit(500)->get());
    }

    public function storeBook(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:200', 'author' => 'nullable|string|max:150', 'isbn' => 'nullable|string|max:30',
            'category' => 'nullable|string|max:60', 'publisher' => 'nullable|string|max:120',
            'total_copies' => 'nullable|integer|min:1', 'rack' => 'nullable|string|max:30',
        ]);
        $data['total_copies'] ??= 1;
        $data['available_copies'] = $data['total_copies'];
        return $this->created(Book::create($data), 'Book added');
    }

    public function updateBook(Request $request, Book $book)
    {
        $data = $request->validate([
            'title' => 'sometimes|string|max:200', 'author' => 'sometimes|nullable|string|max:150',
            'category' => 'sometimes|nullable|string|max:60', 'total_copies' => 'sometimes|integer|min:1', 'rack' => 'sometimes|nullable|string|max:30',
        ]);
        if (isset($data['total_copies'])) {
            $out = $book->total_copies - $book->available_copies;
            if ($data['total_copies'] < $out) return $this->fail("$out copies are issued right now.");
            $data['available_copies'] = $data['total_copies'] - $out;
        }
        $book->update($data);
        return $this->ok($book, 'Book updated');
    }

    public function destroyBook(Book $book)
    {
        if ($book->issues()->whereNull('returned_on')->exists()) return $this->fail('Some copies are still issued.');
        $book->delete();
        return $this->ok(null, 'Book deleted');
    }

    /** GET /library/issues?status=issued|overdue|returned  (librarian/admin: all; others: own) */
    public function issues(Request $request)
    {
        $u = $request->user();
        $q = BookIssue::with(['book', 'user'])->latest('issued_on')->latest('id');
        if (! $u->hasRole(User::ADMIN, User::LIBRARIAN)) $q->where('user_id', $u->id);
        if ($request->query('status') === 'returned') $q->whereNotNull('returned_on');
        elseif ($request->query('status') === 'issued') $q->whereNull('returned_on');
        elseif ($request->query('status') === 'overdue') $q->whereNull('returned_on')->where('due_on', '<', now()->toDateString());
        return $this->ok($q->limit(300)->get());
    }

    /** POST /library/issues { book_id, user_id, due_on? } */
    public function issue(Request $request)
    {
        $data = $request->validate(['book_id' => 'required|exists:books,id', 'user_id' => 'required|exists:users,id', 'due_on' => 'nullable|date|after_or_equal:today']);
        $book = Book::findOrFail($data['book_id']);
        if ($book->available_copies < 1) return $this->fail('No copies of this book are available.');
        if (BookIssue::where('user_id', $data['user_id'])->whereNull('returned_on')->count() >= 3) {
            return $this->fail('This member already has 3 books issued.');
        }
        $issue = DB::transaction(function () use ($data, $book, $request) {
            $book->decrement('available_copies');
            return BookIssue::create([
                'book_id' => $book->id, 'user_id' => $data['user_id'], 'issued_on' => now()->toDateString(),
                'due_on' => $data['due_on'] ?? now()->addDays((int) Setting::get('library_issue_days', 14))->toDateString(),
                'issued_by' => $request->user()->id,
            ]);
        });
        return $this->created($issue->load(['book', 'user']), 'Book issued');
    }

    /** POST /library/issues/{issue}/return */
    public function returnBook(BookIssue $issue)
    {
        if ($issue->returned_on) return $this->fail('Already returned.');
        $lateDays = max(0, $issue->due_on->startOfDay()->diffInDays(now()->startOfDay(), false));
        DB::transaction(function () use ($issue, $lateDays) {
            $issue->update(['returned_on' => now()->toDateString(), 'fine' => $lateDays * (float) Setting::get('library_fine_per_day', 2)]);
            $issue->book->increment('available_copies');
        });
        return $this->ok($issue->fresh(['book', 'user']), $issue->fine > 0 ? 'Returned with fine ₹'.$issue->fine : 'Book returned');
    }
}
