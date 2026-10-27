<?php

use App\Core\Modules\F;
use App\Models\User;

use App\Modules\Transport\Models\TransportRoute;
use App\Modules\Transport\Models\Vehicle;

return [
[
    'key' => 'transport_routes', 'title' => 'Bus Routes', 'group' => 'Transport', 'icon' => 'directions_bus', 'order' => 150,
    'model' => TransportRoute::class, 'label' => 'name', 'subtitle' => ['vehicle_no', 'driver_name', 'fare'], 'search' => ['name', 'vehicle_no', 'driver_name'],
    'roles' => ['view' => ['all'], 'create' => ['office'], 'update' => ['office'], 'delete' => []],
    'fields' => [
        F::text('name', 'Route', ['required' => true, 'list' => true]),
        F::text('vehicle_no', 'Bus no.', ['list' => true]),
        F::text('driver_name', 'Driver', ['list' => true]),
        F::phone('driver_phone', 'Driver mobile'),
        F::money('fare', 'Monthly fare (₹)', ['list' => true]),
        F::time('pickup_time', 'Pick-up time'),
        F::time('drop_time', 'Drop time'),
        F::textarea('stops_text', 'Stops (one per line)', ['virtual' => true, 'get' => fn ($m) => implode("\n", (array) $m->stops)]),
        F::number('students_count', 'Students', ['readonly' => true, 'virtual' => true, 'list' => true, 'get' => fn ($m) => $m->students()->count()]),
    ],
    'after_save' => function ($m, $d) {
        if (array_key_exists('stops_text', $d)) { $m->stops = array_values(array_filter(array_map('trim', preg_split('/\r?\n|,/', (string) $d['stops_text'])))); $m->save(); }
    },
],
[
    'key' => 'vehicles', 'title' => 'Vehicles', 'group' => 'Transport', 'icon' => 'airport_shuttle', 'order' => 151,
    'model' => Vehicle::class, 'label' => 'vehicle_no', 'subtitle' => ['model', 'driver_name', 'fitness_expiry'], 'search' => ['vehicle_no', 'driver_name'],
    'roles' => ['view' => ['employees'], 'create' => ['office'], 'update' => ['office'], 'delete' => []],
    'fields' => [
        F::text('vehicle_no', 'Vehicle no.', ['required' => true, 'list' => true, 'unique' => true]),
        F::text('model', 'Model', ['list' => true]),
        F::number('capacity', 'Seats'),
        F::ref('transport_route_id', 'Route', 'transport_routes', ['list' => true]),
        F::text('driver_name', 'Driver', ['list' => true]),
        F::phone('driver_phone', 'Driver mobile'),
        F::text('driver_license', 'Driving licence no.'),
        F::date('insurance_expiry', 'Insurance till', ['list' => true]),
        F::date('fitness_expiry', 'Fitness till'),
        F::date('pollution_expiry', 'PUC till'),
    ],
],
];
