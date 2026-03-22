<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrintController;

// Printable documents (accessible without Vue SPA — open in new tab)
Route::get('/print/invoice/{invoice}',     [PrintController::class, 'invoice'])->name('print.invoice');
Route::get('/print/checklist/{checklist}', [PrintController::class, 'checklist'])->name('print.checklist');


// Catch-all → hands everything to Vue Router
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');