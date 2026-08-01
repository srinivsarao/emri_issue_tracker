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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::view('/issues', 'pages.issues')
        ->middleware('menu.access:issues')
        ->name('issues');

    Route::view('/raise-issue', 'pages.raise-issue')
        ->middleware('menu.access:raise.issue')
        ->name('raise.issue');

    Route::view('/reports', 'pages.reports')
        ->middleware('menu.access:reports')
        ->name('reports');

    Route::view('/administration', 'pages.administration')
        ->middleware('menu.access:administration')
        ->name('administration');

    Route::view('/central-admin', 'pages.central-admin')
        ->middleware('menu.access:central.admin')
        ->name('central.admin');

    Route::view('/state-admin', 'pages.state-admin')
        ->middleware('menu.access:state.admin')
        ->name('state.admin');

    Route::view('/ho-admin', 'pages.ho-admin')
        ->middleware('menu.access:ho.admin')
        ->name('ho.admin');

    Route::view('/vendor-admin', 'pages.vendor-admin')
        ->middleware('menu.access:vendor.admin')
        ->name('vendor.admin');

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
