<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Local debug route to return the authenticated user's row (hidden password hash).
if (app()->environment('local')) {
    Route::get('/debug-auth-user', function () {
        $user = auth()->user();
        if (! $user) {
            return response()->json(['user' => null], 401);
        }
        return response()->json($user->makeHidden(['password_hash']));
    })->middleware('auth')->name('debug.auth.user');
}

require __DIR__.'/auth.php';
