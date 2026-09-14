<?php

use App\Http\Controllers\CierreSemanalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MetodoPagoController;
use App\Http\Controllers\PagoEmpleadoController;
use App\Http\Controllers\ReportePagoController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\TrabajadorController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::resource('reportes', ReportePagoController::class)->except(['show']);
Route::resource('cierres', CierreSemanalController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
Route::resource('pagos', PagoEmpleadoController::class)->except(['show']);
Route::resource('trabajadores', TrabajadorController::class)->except(['show']);
Route::resource('metodos', MetodoPagoController::class)->except(['show']);
Route::resource('roles', RolController::class)->except(['show']);
