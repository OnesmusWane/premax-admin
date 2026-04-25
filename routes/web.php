<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrintController;


// Printable documents (accessible without Vue SPA — open in new tab)
Route::get('/print/invoice/{invoice}',     [PrintController::class, 'invoice'])->name('print.invoice');
Route::get('/print/checklist/{checklist}', [PrintController::class, 'checklist'])->name('print.checklist');

// Public media fallback for files stored on the public disk.
Route::get('/media/{path}', function (string $path) {
    abort_unless(Storage::disk('public')->exists($path), 404);

    return response()->file(Storage::disk('public')->path($path));
})->where('path', '.*')->name('media.public');

// TEMPORARY — remove after debugging
Route::get('/debug-cloudinary', function () {
    return response()->json([
        'url'    => config('cloudinary.cloud_url'),
        'cloud'  => config('cloudinary.cloud_name'),
        'key'    => config('cloudinary.api_key') ? 'set' : 'missing',
        'secret' => config('cloudinary.api_secret') ? 'set' : 'missing',
    ]);
});

// Catch-all → hands everything to Vue Router
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
