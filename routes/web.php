<?php

use App\Http\Controllers\PlantController;
use App\Http\Controllers\ProfileController; // comes with Laravel Breeze
use Illuminate\Support\Facades\Route;

// ---- Public ----
Route::view('/', 'welcome')->name('home');
Route::get('/start', [PlantController::class, 'start'])->name('start');
Route::get('/map', [PlantController::class, 'map'])->name('map');
Route::get('/map/data', [PlantController::class, 'mapData'])->name('map.data');

// ---- Logged in ----
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [PlantController::class, 'dashboard'])->name('dashboard');

    Route::get('/intro', [PlantController::class, 'intro'])->name('intro');
    Route::get('/plants/choose', [PlantController::class, 'choose'])->name('plants.choose');
    Route::post('/plants', [PlantController::class, 'store'])->name('plants.store');

    Route::get('/garden', [PlantController::class, 'garden'])->name('garden');
    Route::get('/garden/state', [PlantController::class, 'gardenState'])->name('garden.state');

    Route::get('/plants/{plant}', [PlantController::class, 'show'])->name('plants.show');
    Route::patch('/plants/{plant}/description', [PlantController::class, 'updateDescription'])->name('plants.description');
    Route::delete('/plants/{plant}', [PlantController::class, 'destroy'])->name('plants.destroy');
    Route::post('/plants/{plant}/care', [PlantController::class, 'care'])->middleware('throttle:60,1')->name('plants.care');
    Route::post('/plants/{plant}/interact', [PlantController::class, 'interact'])->middleware('throttle:60,1')->name('plants.interact');
    Route::post('/plants/{plant}/target', [PlantController::class, 'storeTarget'])->name('plants.target');
    Route::delete('/plants/{plant}/target', [PlantController::class, 'destroyTarget'])->name('plants.target.destroy');
    Route::patch('/plants/{plant}/location', [PlantController::class, 'location'])->name('plants.location');

    Route::get('/ar', [PlantController::class, 'ar'])->name('ar');

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php'; // login, register, logout (Breeze)
