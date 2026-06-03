<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

function inertia_page(Request $request, string $component, array $props = [])
{
    $page = [
        'component' => $component,
        'props' => $props,
        'url' => $request->getRequestUri(),
        'version' => file_exists(public_path('mix-manifest.json'))
            ? md5_file(public_path('mix-manifest.json'))
            : null,
        'clearHistory' => false,
        'encryptHistory' => false,
    ];

    if ($request->header('X-Inertia')) {
        return response()->json($page)->header('Vary', 'X-Inertia');
    }

    return view('app', ['page' => $page]);
}

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware(['guest.sanctum'])->group(function () {
    Route::get('/login', function (Request $request) {
        return inertia_page($request, 'Auth/Login');
    })->name('login');

    Route::get('/register', function (Request $request) {
        return inertia_page($request, 'Auth/Register');
    })->name('register');

    Route::get('/forgot-password', function (Request $request) {
        return inertia_page($request, 'Auth/ForgotPassword');
    })->name('password.request');

    Route::get('/reset-password/{token}', function (Request $request, string $token) {
        return inertia_page($request, 'Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    })->name('password.reset');
});

Route::middleware(['sanctum.cookie', 'auth:sanctum'])->group(function () {
    Route::get('/dashboard', function (Request $request) {
        return inertia_page($request, 'Dashboard/Index');
    })->name('dashboard');

    Route::get('/change-password', function (Request $request) {
        return inertia_page($request, 'Profile/ChangePassword');
    })->name('password.change');
});
