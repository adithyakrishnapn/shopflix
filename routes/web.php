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

use App\Http\Controllers\RazorpayController;


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

Route::group(['middleware' => ['web']], function () {
    Route::get('razorpay-redirect', [RazorpayController::class, 'redirect'])->name('razorpay.process');

    Route::post('razorpaycheck', [RazorpayController::class, 'verify'])->name('razorpay.callback');

    Route::post('razorpaycheck-json', [RazorpayController::class, 'verifyJson'])->name('razorpay.callback.json');
});

// Razorpay webhook - server-to-server, outside session middleware
Route::post('razorpay-webhook', [RazorpayController::class, 'webhook'])->name('razorpay.webhook')->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);


