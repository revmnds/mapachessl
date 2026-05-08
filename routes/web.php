<?php

use App\Http\Controllers\WizardApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Language switching
Route::post('/locale', function (Request $request) {
    $locale = $request->input('locale');
    if (in_array($locale, ['es', 'en', 'nl'])) {
        session(['locale' => $locale]);
    }
    return redirect()->back();
})->name('locale.switch');

// SPA - Single page app
Route::get('/', [WizardApiController::class, 'index']);
Route::get('/download', [WizardApiController::class, 'download']);

// API endpoints — global rate limit per IP (anti-bot, not anti-user)
Route::prefix('api/wizard')->middleware('throttle:60,1')->group(function () {
    Route::get('/status', [WizardApiController::class, 'status']);
    Route::post('/start', [WizardApiController::class, 'start']);
    Route::post('/start-fresh', [WizardApiController::class, 'startFresh']);
    Route::post('/discard', [WizardApiController::class, 'discard']);
    Route::post('/step/{step}', [WizardApiController::class, 'saveStep'])->where('step', '[1-4]');
    Route::post('/generate', [WizardApiController::class, 'generate']);
    Route::get('/poll-tokens', [WizardApiController::class, 'pollTokens'])->withoutMiddleware('throttle:60,1');
});
