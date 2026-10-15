<?php

use Illuminate\Support\Facades\Route;

Route::get('/start',[DefaultController::class, 'start'] )->middleware('jwt');
Route::get('/',     [DefaultController::class, 'show']  )->middleware('jwt')->name('default');
Route::post('/',    [DefaultController::class, 'resend'])->middleware('jwt');

Route::get('/about',[DefaultController::class, 'missing_info'] )->name('missing_info');
