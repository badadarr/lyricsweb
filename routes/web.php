<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\LyricsScraperController;
use Illuminate\Support\Facades\Route;

// Rute '/' tidak menggunakan middleware
Route::get('/', function () {
    return redirect()->route('login');
});


Route::middleware('auth')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // lyrics scraper route 
    Route::prefix('lyrics-scraper')->group(function () {
        Route::get('/index', [LyricsScraperController::class, 'index'])->name('lyrics-scraper.index');
        Route::get('/data', [LyricsScraperController::class, 'data'])->name('lyrics-scraper.data');
        Route::post('/create', [LyricsScraperController::class, 'store'])->name('lyrics-scraper.store');
        Route::get('details/{projectName}', [LyricsScraperController::class, 'details'])->name('lyrics-scraper.detail');
    });

    // Process Scraping Route
    Route::get('/lyrics/scraper/process', [LyricsScraperController::class, 'processScrapeLyric'])
        ->name('lyrics-scraper.process');


    // Export CSV Route
    Route::get('/lyrics/export/{project_name}', [LyricsScraperController::class, 'exportCsv'])->name('lyrics.export');

});
Auth::routes();
