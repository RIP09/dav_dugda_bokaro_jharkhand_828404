<?php

use App\Core\Http\MetaController;
use App\Core\Modules\RecordsController;
use Illuminate\Support\Facades\Route;

/*
| Phase 2 API (inside /api/v1, auth:sanctum). Role checks for generic modules happen in the module registry.
| See docs/API_V2_CONTRACT.md.
*/

Route::get('meta/modules', [MetaController::class, 'modules']);
Route::get('meta/modules/{key}', [MetaController::class, 'module']);
Route::get('meta/menu', [MetaController::class, 'menu']);

Route::get('records/{module}', [RecordsController::class, 'index']);
Route::post('records/{module}', [RecordsController::class, 'store']);
Route::get('records/{module}/{id}', [RecordsController::class, 'show'])->whereNumber('id');
Route::put('records/{module}/{id}', [RecordsController::class, 'update'])->whereNumber('id');
Route::patch('records/{module}/{id}', [RecordsController::class, 'update'])->whereNumber('id');
Route::delete('records/{module}/{id}', [RecordsController::class, 'destroy'])->whereNumber('id');
Route::get('records/{module}/{id}/history', [RecordsController::class, 'history'])->whereNumber('id');
Route::post('records/{module}/{id}/actions/{action}', [RecordsController::class, 'action'])->whereNumber('id');
