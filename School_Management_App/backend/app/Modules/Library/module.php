<?php

use App\Core\Modules\F;
use App\Models\User;

use App\Modules\Library\Models\Book;
use App\Modules\Library\Models\BookIssue;

return [
[
    'key' => 'books', 'title' => 'Library Books', 'group' => 'Library', 'icon' => 'local_library', 'order' => 160,
    'model' => Book::class, 'label' => 'title', 'subtitle' => ['author', 'available_copies'], 'search' => ['title', 'author', 'isbn', 'category'], 'sort' => 'title',
    'import' => true, 'import_key' => 'isbn',
    'roles' => ['view' => ['all'], 'create' => ['librarian'], 'update' => ['librarian'], 'delete' => ['librarian']],
    'fields' => [
        F::text('title', 'Title', ['required' => true, 'list' => true]),
        F::text('author', 'Author', ['list' => true]),
        F::text('isbn', 'ISBN'),
        F::select('category', 'Category', ['Textbook', 'Reference', 'Fiction', 'Story', 'Biography', 'Science', 'Competitive', 'Magazine', 'Other'], ['list' => true, 'filter' => true]),
        F::text('publisher', 'Publisher'),
        F::number('total_copies', 'Copies', ['list' => true, 'default' => 1]),
        F::number('available_copies', 'Available', ['readonly' => true, 'list' => true]),
        F::text('rack', 'Rack'),
    ],
    'before_save' => function ($m, $d, $u, $creating) {
        if ($creating) $m->available_copies = $m->total_copies ?? 1;
        elseif ($m->isDirty('total_copies')) $m->available_copies = max(0, $m->available_copies + ($m->total_copies - $m->getOriginal('total_copies')));
    },
],
[
    'key' => 'book_issues', 'title' => 'Issued Books & Fines', 'group' => 'Library', 'icon' => 'bookmark', 'order' => 161, 'no_create' => true,
    'model' => BookIssue::class, 'with' => ['book', 'user'], 'title_fn' => fn ($m) => ($m->book?->title ?? 'Book').' → '.($m->user?->name ?? ''),
    'subtitle' => ['due_on', 'status', 'fine'], 'search' => ['book.title', 'user.name'], 'sort' => '-issued_on',
    'scope' => function ($q, $user) { if (! in_array($user->role, ['admin', 'librarian'], true)) $q->where(fn ($w) => $w->where('user_id', $user->id)->orWhereIn('user_id', \App\Modules\Students\Models\Student::whereIn('id', $user->ownStudentIds() ?: [0])->pluck('user_id'))); },
    'filters' => ['overdue' => fn ($q) => $q->whereNull('returned_on')->where('due_on', '<', now()->toDateString())],
    'roles' => ['view' => ['all'], 'update' => ['librarian'], 'delete' => []],
    'fields' => [
        F::ref('book_id', 'Book', 'books', ['readonly' => true, 'list' => true]),
        F::text('member', 'Member', ['readonly' => true, 'virtual' => true, 'list' => true, 'get' => fn ($m) => $m->user?->name]),
        F::date('issued_on', 'Issued', ['list' => true]),
        F::date('due_on', 'Due', ['list' => true]),
        F::date('returned_on', 'Returned', ['readonly' => true]),
        F::money('fine', 'Fine (₹)', ['list' => true]),
        F::bool('fine_paid', 'Fine paid', ['filter' => true]),
        F::text('status', 'Status', ['readonly' => true, 'virtual' => true, 'list' => true, 'get' => fn ($m) => $m->status]),
    ],
    'actions' => [
        'return' => ['label' => 'Return book', 'kind' => 'post', 'roles' => ['librarian'], 'when' => fn ($m) => ! $m->returned_on,
            'run' => function ($m, $r, $u) {
                $resp = app(\App\Modules\Library\Http\LibraryController::class)->returnBook($m);
                if ($resp->getStatusCode() >= 400) abort($resp->getStatusCode(), $resp->getData(true)['message'] ?? 'Could not return');
                return ['message' => $resp->getData(true)['message'] ?? 'Returned'];
            }],
    ],
],
];
