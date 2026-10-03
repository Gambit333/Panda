<?php

use App\Http\Controllers\AbonoAdelantoController;
use App\Http\Controllers\AdelantoController;
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
    Route::post('/login/recuperar', [LoginController::class, 'recuperarPassword'])->name('login.recuperar');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Cada módulo pide su clave de permiso: role:reportes, role:cierres, ...
    Route::middleware('role:reportes')->group(function (): void {
        Route::resource('reportes', ReportePagoController::class)->except(['show']);
    });

    Route::middleware('role:cierres')->group(function (): void {
        Route::resource('cierres', CierreSemanalController::class)
            ->only(['index', 'create', 'store', 'show', 'destroy']);
    });

    Route::middleware('role:pagos')->group(function (): void {
        Route::resource('pagos', PagoEmpleadoController::class)->except(['show']);
    });

    Route::middleware('role:adelantos')->group(function (): void {
        Route::resource('adelantos', AdelantoController::class)->except(['show']);
        Route::post('adelantos/{adelanto}/abonos', [AbonoAdelantoController::class, 'store'])->name('adelantos.abonos.store');
        Route::delete('adelantos/abonos/{abono}', [AbonoAdelantoController::class, 'destroy'])->name('adelantos.abonos.destroy');
    });

    Route::middleware('role:roles')->group(function (): void {
        // El singular de "roles" es "role" pero el controlador usa Rol $rol:
        // sin esto no hay model binding y editar/actualizar/borrar un rol no hace nada.
        Route::resource('roles', RolController::class)
            ->parameters(['roles' => 'rol'])
            ->except(['show']);
    });

    Route::middleware('role:trabajadores')->group(function (): void {
        // El singular de "trabajadores" es "trabajadore", no "trabajador": sin
        // parameters() el binding se pierde y editar/eliminar no hacen nada.
        Route::resource('trabajadores', TrabajadorController::class)
            ->parameters(['trabajadores' => 'trabajador'])
            ->except(['show']);
    });

    Route::middleware('role:metodos')->group(function (): void {
        Route::resource('metodos', MetodoPagoController::class)->except(['show']);
    });
});
