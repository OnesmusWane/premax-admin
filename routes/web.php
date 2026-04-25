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
Route::get('/debug-ig-token', function () {
    $account = \App\Models\SocialAccount::where('platform', 'instagram')->first();
    $creds   = $account->credentials;

    $token     = $creds['access_token'] ?? null;
    $appId     = $creds['app_id'] ?? null;
    $appSecret = $creds['app_secret'] ?? null;

    $response = \Illuminate\Support\Facades\Http::get('https://graph.facebook.com/v25.0/debug_token', [
        'input_token'  => $token,
        'access_token' => "{$appId}|{$appSecret}",
    ]);

    return response()->json([
        'token_start'  => substr($token ?? '', 0, 20) . '...',
        'debug_result' => $response->json(),
    ]);
});

// Catch-all → hands everything to Vue Router
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
