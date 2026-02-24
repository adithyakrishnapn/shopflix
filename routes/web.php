<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('/storage/{path}', function ($path) {
    $path = storage_path('app/public/' . $path);

    if (!File::exists($path)) {
        abort(404);
    }

    return response()->file($path);
})->where('path', '.*');


Route::get('/clear-cache', function () {
    Artisan::call('optimize:clear');
    return 'Application cache cleared';
});


