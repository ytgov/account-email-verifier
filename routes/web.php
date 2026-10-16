<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DefaultController;

Route::middleware(['jwt'])->group(function() {
    Route::get('/start',[DefaultController::class, 'start'] );
    Route::get('/',     [DefaultController::class, 'show']  )->name('default');
    Route::post('/',    [DefaultController::class, 'resend']);
});

Route::get('/about',[DefaultController::class, 'missing_info'] )->name('missing_info');
