<?php

use App\Http\Controllers\CierreSemanalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MetodoPagoController;
use App\Http\Controllers\PagoEmpleadoController;
use App\Http\Controllers\ReportePagoController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\TrabajadorController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'submitEmail'])->name('login.submit-email');
    Route::get('/login/password', [LoginController::class, 'showPasswordForm'])->name('login.password');
    Route::post('/login/password', [LoginController::class, 'submitPassword'])->name('login.password.submit');
});

Route::middleware(['auth', 'session.timeout'])->group(function (): void {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::post('/session/keepalive', [LoginController::class, 'keepalive'])->name('session.keepalive');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:moderador')->group(function (): void {
        Route::resource('reportes', ReportePagoController::class)->except(['show']);
    });

    Route::middleware('role')->group(function (): void {
        Route::resource('cierres', CierreSemanalController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
        Route::resource('pagos', PagoEmpleadoController::class)->except(['show']);
        Route::resource('trabajadores', TrabajadorController::class)->except(['show']);
        Route::resource('metodos', MetodoPagoController::class)->except(['show']);
        Route::resource('roles', RolController::class)->except(['show']);
    });
});