<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResumeController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Redirect::route('resumes.index');
})->middleware(['auth'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/curriculos', [ResumeController::class, 'index'])->name('resumes.index');
    Route::post('/curriculos', [ResumeController::class, 'store'])->name('resumes.store');
    Route::get('/curriculos/{resume}/editar', [ResumeController::class, 'edit'])->name('resumes.edit');
    Route::post('/curriculos/{resume}/foto', [ResumeController::class, 'updatePhoto'])->name('resumes.photo.update');
    Route::delete('/curriculos/{resume}/foto', [ResumeController::class, 'destroyPhoto'])->name('resumes.photo.destroy');
    Route::put('/curriculos/{resume}', [ResumeController::class, 'update'])->name('resumes.update');
    Route::delete('/curriculos/{resume}', [ResumeController::class, 'destroy'])->name('resumes.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
