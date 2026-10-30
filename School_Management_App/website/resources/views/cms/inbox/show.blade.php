@extends('website::layouts.cms')
@section('title', $title)
@section('content')
<div class="page-head"><div><a class="crumb" href="{{ route('webadmin.inbox', $box) }}">← {{ $title }}</a><h1>{{ $item->student_name ?? $item->name }}</h1>
<p class="muted small">Received {{ $item->created_at->format('d M Y, g:i A') }}</p></div>
@if($item->phone)<a class="btn primary" href="tel:{{ $item->phone }}">@icon('phone','ic',18) Call {{ $item->phone }}</a>@endif
</div>
<div class="grid2">
    <section class="card">
        <dl class="details">
            @if($box === 'enquiries')
                <div><dt>Child's name</dt><dd>{{ $item->student_name }}</dd></div>
                <div><dt>Class sought</dt><dd>{{ $item->class_sought }}</dd></div>
                <div><dt>Date of birth</dt><dd>{{ optional($item->dob)->format('d M Y') ?? '—' }}</dd></div>
                <div><dt>Parent / guardian</dt><dd>{{ $item->parent_name }}</dd></div>
                <div><dt>Previous school</dt><dd>{{ $item->previous_school ?: '—' }}</dd></div>
            @else
                <div><dt>Name</dt><dd>{{ $item->name }}</dd></div>
                <div><dt>Subject</dt><dd>{{ $item->subject ?: '—' }}</dd></div>
            @endif
            <div><dt>Phone</dt><dd>@if($item->phone)<a href="tel:{{ $item->phone }}">{{ $item->phone }}</a> · <a href="https://wa.me/{{ (strlen(preg_replace('/\D/', '', $item->phone)) === 10 ? '91' : '').preg_replace('/\D/', '', $item->phone) }}" target="_blank" rel="noopener">WhatsApp</a>@else — @endif</dd></div>
            <div><dt>Email</dt><dd>@if($item->email)<a href="mailto:{{ $item->email }}">{{ $item->email }}</a>@else — @endif</dd></div>
            <div class="wide"><dt>Message</dt><dd class="pre">{{ $item->message ?: '—' }}</dd></div>
            @if($box === 'enquiries')<div><dt>In school-management system</dt><dd>{{ $item->school_enquiry_id ? 'Yes (enquiry #'.$item->school_enquiry_id.')' : 'Not copied' }}</dd></div>@endif
        </dl>
    </section>
    <section class="card">
        <h2>Follow-up</h2>
        <form method="post" action="{{ route('webadmin.inbox.update', [$box, $item->id]) }}" class="stack">
            @csrf @method('PUT')
            <label class="fld"><span>Status</span>
                <select name="status"><option value="new" @selected($item->status === 'new')>New — not handled yet</option><option value="handled" @selected($item->status === 'handled')>Handled — we have replied</option></select>
            </label>
            <label class="fld"><span>Office notes (only visible here)</span><textarea name="notes" rows="5">{{ old('notes', $item->notes) }}</textarea></label>
            @if($item->handled_at)<p class="muted small">Marked handled {{ $item->handled_at->format('d M Y, g:i A') }}</p>@endif
            <button class="btn primary" type="submit">@icon('check','ic',18) Save</button>
        </form>
        <form method="post" action="{{ route('webadmin.inbox.destroy', [$box, $item->id]) }}" data-confirm="Delete this for good?" class="mt-s">
            @csrf @method('DELETE')
            <button class="btn danger ghost" type="submit">@icon('trash-2','ic',16) Delete</button>
        </form>
    </section>
</div>
@endsection
