<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AtencionController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ClientesEmpresaController;
use App\Http\Controllers\CuentaProveedorController;
use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\ConfiguracionSistemaController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\EstatusController;
use App\Http\Controllers\KiuController;
use App\Http\Controllers\MetodoPagoController;
use App\Http\Controllers\OrigenController;
use App\Http\Controllers\OrdenCompraController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PagoProveedorController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\ServicioCotizacionController;
use App\Http\Controllers\TasaController;
use App\Http\Controllers\TasaCambioController;
use App\Http\Controllers\TemporalidadController;
use App\Http\Controllers\MetaController;
use App\Http\Controllers\MetaPersonalController;
use App\Http\Controllers\LogroPersonalController;
use App\Http\Controllers\TipoCotizacionController;
use App\Http\Controllers\TipoContribuyenteController;
use App\Http\Controllers\TipoProveedorController;
use App\Http\Controllers\TipoServicioController;
use App\Http\Controllers\PersonalController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\PersonalEmpresaController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\BroadcastingController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login/admin', [AuthController::class, 'loginAdmin']);
Route::post('/login/user', [AuthController::class, 'loginUser']);

Route::get('/status', function () {
    return response()->json(['status' => 'ok']);
});

// Ruta para que Next.js obtenga el contrato de forma segura
Route::get('/v1/contrato', [ContractController::class, 'download']);

Route::middleware('auth:sanctum')->group(function () {
    // User profile: used by Next.js to silently refresh the session JWT
    Route::get('/me', [AuthController::class, 'me']);

    // Broadcasting channel auth: validated via routes/channels.php
    Route::post('/broadcasting/auth', [BroadcastingController::class, 'auth']);

    // Rutas de métricas
    Route::get('metricas/personal/{idPersonal}', [\App\Http\Controllers\MetricasController::class, 'porPersonal']);
    Route::get('metricas/generales', [\App\Http\Controllers\MetricasController::class, 'generales']);
    Route::apiResource('entidades-bancarias', \App\Http\Controllers\EntidadBancariaController::class);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/register/personal', [AuthController::class, 'registerPersonal'])
    ->middleware('role:admin');

    Route::apiResource('estatus', EstatusController::class);
    Route::apiResource('configuraciones-sistema', ConfiguracionSistemaController::class)
        ->parameters(['configuraciones-sistema' => 'configuracionSistema']);
    Route::apiResource('cuentas-proveedores', CuentaProveedorController::class)
        ->parameters(['cuentas-proveedores' => 'cuentaProveedor']);
    Route::apiResource('tipos-contribuyentes', TipoContribuyenteController::class)
        ->parameters(['tipos-contribuyentes' => 'tipoContribuyente']);
    Route::apiResource('clientes', ClienteController::class);
    Route::apiResource('personal', PersonalController::class);
    Route::apiResource('empresas', EmpresaController::class);
    Route::apiResource('clientes-empresas', ClientesEmpresaController::class)
        ->parameters(['clientes-empresas' => 'clientesEmpresa']);
    Route::apiResource('personal-empresas', PersonalEmpresaController::class)
        ->parameters(['personal-empresas' => 'personalEmpresa']);
    Route::apiResource('origenes', OrigenController::class)
        ->parameters(['origenes' => 'origen']);
    Route::apiResource('atenciones', AtencionController::class)
        ->parameters(['atenciones' => 'atencion']);
    Route::apiResource('cotizaciones', CotizacionController::class)
        ->parameters(['cotizaciones' => 'cotizacion']);
    Route::apiResource('tipos-cotizaciones', TipoCotizacionController::class)
        ->parameters(['tipos-cotizaciones' => 'tipoCotizacion']);
    Route::apiResource('metodos-pago', MetodoPagoController::class)
        ->parameters(['metodos-pago' => 'metodoPago']);
    Route::apiResource('ordenes-compra', OrdenCompraController::class)
        ->parameters(['ordenes-compra' => 'ordenCompra']);
    Route::apiResource('pagos', PagoController::class);
    Route::apiResource('pagos-ordenes-compra', \App\Http\Controllers\PagoOrdenCompraController::class)
        ->parameters(['pagos-ordenes-compra' => 'pagoOrdenCompra']);
    Route::apiResource('pagos-proveedores', PagoProveedorController::class)
        ->parameters(['pagos-proveedores' => 'pagoProveedor']);
    Route::apiResource('tipos-proveedores', TipoProveedorController::class)
        ->parameters(['tipos-proveedores' => 'tipoProveedor']);
    Route::apiResource('proveedores', ProveedorController::class)
        ->parameters(['proveedores' => 'proveedor']);
    Route::apiResource('tipo-servicio', TipoServicioController::class)
        ->parameters(['tipo-servicio' => 'tipoServicio']);
    Route::apiResource('tasas', TasaController::class)
        ->parameters(['tasas' => 'tasa']);
    Route::apiResource('tasas-cambio', TasaCambioController::class)
        ->parameters(['tasas-cambio' => 'tasaCambio']);
    Route::apiResource('servicios', ServicioController::class);
    Route::apiResource('temporalidades', TemporalidadController::class)
        ->parameters(['temporalidades' => 'temporalidad']);
    Route::apiResource('metas', MetaController::class);
    Route::apiResource('metas-personal', MetaPersonalController::class)
        ->parameters(['metas-personal' => 'metaPersonal']);
    Route::get('logros-personal', [LogroPersonalController::class, 'index'])
        ->middleware('role:admin');
    Route::get('audit-logs/export/csv', [AuditLogController::class, 'exportCsv'])
        ->middleware('role:admin');
    Route::get('audit-logs', [AuditLogController::class, 'index'])
        ->middleware('role:admin');

    Route::prefix('kiu')->controller(KiuController::class)->group(function () {
        Route::post('session', 'session');
        Route::post('availability', 'availability');
        Route::post('pricing', 'pricing');
        Route::post('booking', 'booking');
        Route::post('ticketing', 'ticketing');
        Route::post('post-sale', 'postSale');
    });

    Route::get('/admin-only', function () {
        return response()->json(['message' => 'Admin access granted']);
    })->middleware('role:admin');
});

/*
Explicacion rapida de rutas y funciones usadas:

Rutas publicas (sin token):
- POST /register: llama AuthController::register para crear un usuario y devolver su token.
- POST /login: llama AuthController::login para iniciar sesion sin exigir rol.
- POST /login/admin: llama AuthController::loginAdmin y solo permite usuarios con rol admin.
- POST /login/user: llama AuthController::loginUser y solo permite usuarios con rol user.

Rutas protegidas (auth:sanctum):
- POST /logout: llama AuthController::logout para cerrar la sesion actual.
- POST /register/personal: llama AuthController::registerPersonal para crear usuarios con rol personal (solo admin).

- /estatus: CRUD completo con EstatusController (index, store, show, update, destroy).
- /configuraciones-sistema: CRUD completo con ConfiguracionSistemaController.
- /cuentas-proveedores: CRUD completo con CuentaProveedorController.
- /tipos-contribuyentes: CRUD completo con TipoContribuyenteController.
- /empresas: CRUD completo con EmpresaController.
- /origenes: CRUD completo con OrigenController (index, store, show, update, destroy).
- /atenciones: CRUD completo con AtencionController (index, store, show, update, destroy).
    En store y update valida que cliente y personal tengan los roles correctos.
    En store y update valida que el personal tenga el rol correcto.
- /personal-empresas: CRUD completo con PersonalEmpresaController (index, store, show, update, destroy).
    En store y update valida que el personal tenga el rol correcto.
- /cotizaciones: CRUD completo con CotizacionController (index, store, show, update, destroy).
    En store asigna el estatus inicial "por pagar" y recibe id_tasa_asignada.
- /tipos-cotizaciones: CRUD completo con TipoCotizacionController.
- /servicios-cotizaciones: CRUD de la tabla puente con ServicioCotizacionController.
- /metodos-pago: CRUD completo con MetodoPagoController.
- /pagos: CRUD completo con PagoController.
    Distribuye montos entre varias ordenes de compra y actualiza estatus.
- /pagos-proveedores: CRUD completo con PagoProveedorController.
- /tipos-proveedores: CRUD completo con TipoProveedorController.
- /proveedores: CRUD completo con ProveedorController.
- /tipo-servicio: CRUD completo con TipoServicioController.
- /tasas-cambio: CRUD completo con TasaCambioController (fecha se asigna en store).
- /servicios: CRUD completo con ServicioController.

Ruta especial:
- GET /admin-only: devuelve un mensaje solo si el usuario tiene rol admin.

Funciones de rutas:
- Route::apiResource: crea las rutas REST (index, store, show, update, destroy).
- Route::middleware('auth:sanctum'): exige token valido.
- ->middleware('role:admin'): restringe acceso al rol admin.
- ->parameters(...): define el nombre del parametro para el binding de modelo.
*/
