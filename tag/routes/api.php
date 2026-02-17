<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AtencionController;
use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\EstatusController;
use App\Http\Controllers\MetodoPagoController;
use App\Http\Controllers\OrigenController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\ServicioCotizacionController;
use App\Http\Controllers\TasaCambioController;
use App\Http\Controllers\TipoProveedorController;
use App\Http\Controllers\TipoServicioController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login/admin', [AuthController::class, 'loginAdmin']);
Route::post('/login/user', [AuthController::class, 'loginUser']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('estatus', EstatusController::class);
    Route::apiResource('origenes', OrigenController::class);
    Route::apiResource('atenciones', AtencionController::class);
    Route::apiResource('cotizaciones', CotizacionController::class);
    Route::apiResource('servicios-cotizaciones', ServicioCotizacionController::class)
        ->parameters(['servicios-cotizaciones' => 'servicioCotizacion']);
    Route::apiResource('metodos-pago', MetodoPagoController::class)
        ->parameters(['metodos-pago' => 'metodoPago']);
    Route::apiResource('pagos', PagoController::class);
    Route::apiResource('tipos-proveedores', TipoProveedorController::class)
        ->parameters(['tipos-proveedores' => 'tipoProveedor']);
    Route::apiResource('proveedores', ProveedorController::class);
    Route::apiResource('tipo-servicio', TipoServicioController::class)
        ->parameters(['tipo-servicio' => 'tipoServicio']);
    Route::apiResource('tasas-cambio', TasaCambioController::class)
        ->parameters(['tasas-cambio' => 'tasaCambio']);
    Route::apiResource('servicios', ServicioController::class);

    Route::get('/admin-only', function () {
        return response()->json(['message' => 'Admin access granted']);
    })->middleware('role:admin');
});
