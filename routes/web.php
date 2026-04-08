<?php

use App\Http\Controllers\DocumentAccessController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::patch('/document-access/{access}', [DocumentAccessController::class, 'update'])
        ->name('document.access.update');
    Route::delete('/document-access/{access}', [DocumentAccessController::class, 'destroy'])
        ->name('document.access.destroy');
});
