<?php

namespace App\Modules\Transport\Http;

use App\Http\Controllers\Controller;
use App\Modules\Students\Models\Student;
use App\Modules\Transport\Models\TransportRoute;
use App\Models\User;
use Illuminate\Http\Request;

class TransportController extends Controller
{
    public function index(Request $request)
    {
        $u = $request->user();
        $routes = TransportRoute::withCount('students')->orderBy('name')->get();
        $mine = collect($u->ownStudentIds())->map(fn ($id) => Student::find($id)?->transport_route_id)->filter()->unique()->values();
        return $this->ok($routes->map(fn ($r) => array_merge($r->toArray(), ['is_mine' => $mine->contains($r->id)])));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100', 'vehicle_no' => 'nullable|string|max:30',
            'driver_name' => 'nullable|string|max:100', 'driver_phone' => 'nullable|string|max:20',
            'fare' => 'nullable|numeric|min:0', 'stops' => 'nullable|array', 'stops.*' => 'string|max:100',
            'pickup_time' => 'nullable|string|max:10', 'drop_time' => 'nullable|string|max:10',
        ]);
        return $this->created(TransportRoute::create($data), 'Route added');
    }

    public function update(Request $request, TransportRoute $route)
    {
        $route->update($request->validate([
            'name' => 'sometimes|string|max:100', 'vehicle_no' => 'sometimes|nullable|string|max:30',
            'driver_name' => 'sometimes|nullable|string|max:100', 'driver_phone' => 'sometimes|nullable|string|max:20',
            'fare' => 'sometimes|numeric|min:0', 'stops' => 'sometimes|array',
        ]));
        return $this->ok($route, 'Route updated');
    }

    public function destroy(TransportRoute $route)
    {
        $route->delete();
        return $this->ok(null, 'Route deleted');
    }

    /** GET /transport/routes/{route}/students */
    public function students(TransportRoute $route)
    {
        return $this->ok($route->students()->with(['user', 'schoolClass', 'section'])->get());
    }
}
