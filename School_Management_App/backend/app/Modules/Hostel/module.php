<?php

use App\Core\Modules\F;
use App\Models\User;

use App\Modules\Hostel\Models\Hostel;
use App\Modules\Hostel\Models\HostelRoom;
use App\Modules\Hostel\Models\HostelAllocation;

return [
[
    'key' => 'hostels', 'title' => 'Hostels', 'group' => 'Hostel', 'icon' => 'apartment', 'order' => 140,
    'model' => Hostel::class, 'label' => 'name', 'subtitle' => ['type', 'warden_name'],
    'roles' => ['view' => ['employees', 'family'], 'create' => ['office'], 'update' => ['office'], 'delete' => []],
    'fields' => [
        F::text('name', 'Hostel', ['required' => true, 'list' => true]),
        F::select('type', 'For', ['boys' => 'Boys', 'girls' => 'Girls', 'mixed' => 'Mixed'], ['list' => true]),
        F::text('warden_name', 'Warden', ['list' => true]),
        F::phone('warden_phone', 'Warden mobile'),
        F::text('address', 'Address'),
    ],
],
[
    'key' => 'hostel_rooms', 'title' => 'Hostel Rooms', 'group' => 'Hostel', 'icon' => 'bed', 'order' => 141,
    'model' => HostelRoom::class, 'with' => ['hostel'], 'title_fn' => fn ($m) => ($m->hostel?->name ?? '').' · Room '.$m->room_no,
    'subtitle' => ['capacity', 'occupied', 'fee_per_month'],
    'roles' => ['view' => ['employees'], 'create' => ['office'], 'update' => ['office'], 'delete' => []],
    'fields' => [
        F::ref('hostel_id', 'Hostel', 'hostels', ['required' => true, 'list' => true, 'filter' => true]),
        F::text('room_no', 'Room no.', ['required' => true, 'list' => true]),
        F::text('room_type', 'Type (AC / non-AC / dorm)'),
        F::number('capacity', 'Beds', ['list' => true]),
        F::number('occupied', 'Occupied', ['readonly' => true, 'virtual' => true, 'list' => true, 'get' => fn ($m) => $m->allocations()->where('status', 'active')->count()]),
        F::money('fee_per_month', 'Fee per month (₹)', ['list' => true]),
    ],
],
[
    'key' => 'hostel_allocations', 'title' => 'Hostel Allotment', 'group' => 'Hostel', 'icon' => 'hotel', 'order' => 142,
    'model' => HostelAllocation::class, 'with' => ['student.user', 'room.hostel'], 'title_fn' => fn ($m) => ($m->student?->name ?? '').' · '.($m->room?->hostel?->name ?? '').' '.$m->room?->room_no,
    'subtitle' => ['from_date', 'status'], 'search' => ['student.admission_no'],
    'scope' => function ($q, $user) { if (! $user->isAdmin() && ! $user->isStaff()) $q->whereIn('student_id', $user->ownStudentIds() ?: [0]); },
    'roles' => ['view' => ['employees', 'family'], 'create' => ['office'], 'update' => ['office'], 'delete' => ['office']],
    'fields' => [
        F::ref('student_id', 'Student', 'students', ['required' => true, 'list' => true]),
        F::ref('hostel_room_id', 'Room', 'hostel_rooms', ['required' => true, 'list' => true, 'filter' => true]),
        F::text('bed_no', 'Bed'),
        F::date('from_date', 'From', ['required' => true, 'list' => true]),
        F::date('to_date', 'Till'),
        F::select('status', 'Status', ['active' => 'Staying', 'left' => 'Left'], ['list' => true, 'filter' => true, 'default' => 'active']),
    ],
    'validate' => function (array $d, $m) {
        $room = HostelRoom::find($d['hostel_room_id'] ?? $m?->hostel_room_id);
        if ($room && ! $m && ($d['status'] ?? 'active') === 'active' && $room->allocations()->where('status', 'active')->count() >= $room->capacity) {
            throw \Illuminate\Validation\ValidationException::withMessages(['hostel_room_id' => 'This room is full.']);
        }
    },
],
];
