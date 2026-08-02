<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CitaController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\ConversacionController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DetalleTecnicoController;
use App\Http\Controllers\Api\EquipoController;
use App\Http\Controllers\Api\IntegracionController;
use App\Http\Controllers\Api\PagoController;
use App\Http\Controllers\Api\ServicioController;
use App\Http\Controllers\Api\TecnicoController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\OtpController;
use App\Http\Controllers\Api\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'estado' => 'ok',
    'servicio' => 'Electro Frío API',
]));

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/auth/otp/solicitar', [OtpController::class, 'solicitar'])->middleware('throttle:5,1');
Route::post('/auth/otp/verificar', [OtpController::class, 'verificar'])->middleware('throttle:10,1');

Route::get('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verificar']);
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'recibir']);
Route::get('/whatsapp/webhook', [WhatsAppWebhookController::class, 'verificar']);
Route::post('/whatsapp/webhook', [WhatsAppWebhookController::class, 'recibir']);

Route::get('/detalle-tecnicos/{detalleTecnico}/evidencias/{evidencia}/archivo', [DetalleTecnicoController::class, 'verEvidencia'])
    ->middleware('signed')
    ->name('evidencias.archivo');

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::middleware('role:administrador')->group(function () {
        Route::apiResource('usuarios', UsuarioController::class);
        Route::post('/integraciones/whatsapp/conectar', [IntegracionController::class, 'conectar'])
            ->middleware('throttle:10,1');
    });

    Route::get('/mensajes/no-leidos', [ConversacionController::class, 'noLeidos']);
    Route::get('/integraciones/whatsapp/estado', [IntegracionController::class, 'whatsapp']);
    Route::get('/conversaciones', [ConversacionController::class, 'index']);
    Route::post('/conversaciones', [ConversacionController::class, 'store']);
    Route::get('/conversaciones/{conversacion}', [ConversacionController::class, 'show']);
    Route::post('/conversaciones/{conversacion}/mensajes', [ConversacionController::class, 'enviar']);
    Route::post('/conversaciones/{conversacion}/leer', [ConversacionController::class, 'marcarComoLeida']);

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', [DashboardController::class, 'index']);

/*
|--------------------------------------------------------------------------
| Clientes
|--------------------------------------------------------------------------
*/

Route::get('/clientes', [ClienteController::class, 'index']);
Route::post('/clientes', [ClienteController::class, 'store']);
Route::get('/clientes/{cliente}', [ClienteController::class, 'show']);
Route::put('/clientes/{cliente}', [ClienteController::class, 'update']);
Route::patch('/clientes/{cliente}', [ClienteController::class, 'update']);
Route::delete('/clientes/{cliente}', [ClienteController::class, 'destroy']);

Route::get('/resumen-clientes', [ClienteController::class, 'resumen']);

/*
|--------------------------------------------------------------------------
| Técnicos
|--------------------------------------------------------------------------
*/

Route::get('/tecnicos', [TecnicoController::class, 'index']);
Route::post('/tecnicos', [TecnicoController::class, 'store']);
Route::get('/tecnicos/{tecnico}', [TecnicoController::class, 'show']);
Route::put('/tecnicos/{tecnico}', [TecnicoController::class, 'update']);
Route::patch('/tecnicos/{tecnico}', [TecnicoController::class, 'update']);
Route::delete('/tecnicos/{tecnico}', [TecnicoController::class, 'destroy']);

/*
|--------------------------------------------------------------------------
| Servicios
|--------------------------------------------------------------------------
*/

Route::get('/servicios', [ServicioController::class, 'index']);
Route::post('/servicios', [ServicioController::class, 'store']);
Route::get('/servicios/{servicio}', [ServicioController::class, 'show']);
Route::put('/servicios/{servicio}', [ServicioController::class, 'update']);
Route::patch('/servicios/{servicio}', [ServicioController::class, 'update']);
Route::delete('/servicios/{servicio}', [ServicioController::class, 'destroy']);

Route::get('/resumen-servicios', [ServicioController::class, 'resumen']);

/*
|--------------------------------------------------------------------------
| Equipos
|--------------------------------------------------------------------------
*/

Route::get('/equipos', [EquipoController::class, 'index']);
Route::post('/equipos', [EquipoController::class, 'store']);
Route::get('/equipos/{equipo}', [EquipoController::class, 'show']);
Route::put('/equipos/{equipo}', [EquipoController::class, 'update']);
Route::patch('/equipos/{equipo}', [EquipoController::class, 'update']);
Route::delete('/equipos/{equipo}', [EquipoController::class, 'destroy']);

/*
|--------------------------------------------------------------------------
| Citas
|--------------------------------------------------------------------------
*/

Route::get('/citas', [CitaController::class, 'index']);
Route::post('/citas', [CitaController::class, 'store']);
Route::get('/citas/{cita}', [CitaController::class, 'show']);
Route::get('/citas/{cita}/documento', [CitaController::class, 'documento']);
Route::put('/citas/{cita}', [CitaController::class, 'update']);
Route::patch('/citas/{cita}', [CitaController::class, 'update']);
Route::delete('/citas/{cita}', [CitaController::class, 'destroy']);

Route::put('/citas/{cita}/finalizar', [CitaController::class, 'finalizar']);
Route::patch('/citas/{cita}/finalizar', [CitaController::class, 'finalizar']);
Route::put('/citas/{cita}/estado', [CitaController::class, 'cambiarEstado']);
Route::patch('/citas/{cita}/estado', [CitaController::class, 'cambiarEstado']);

/*
|--------------------------------------------------------------------------
| Pagos
|--------------------------------------------------------------------------
*/

Route::get('/pagos', [PagoController::class, 'index']);
Route::post('/pagos', [PagoController::class, 'store']);
Route::get('/pagos/{pago}', [PagoController::class, 'show']);
Route::put('/pagos/{pago}', [PagoController::class, 'update']);
Route::patch('/pagos/{pago}', [PagoController::class, 'update']);
Route::delete('/pagos/{pago}', [PagoController::class, 'destroy']);

/*
|--------------------------------------------------------------------------
| Detalle Técnico
|--------------------------------------------------------------------------
*/

Route::get('/detalle-tecnicos', [DetalleTecnicoController::class, 'index']);
Route::post('/detalle-tecnicos', [DetalleTecnicoController::class, 'store']);
Route::get('/detalle-tecnicos/{detalleTecnico}', [DetalleTecnicoController::class, 'show']);
Route::put('/detalle-tecnicos/{detalleTecnico}', [DetalleTecnicoController::class, 'update']);
Route::patch('/detalle-tecnicos/{detalleTecnico}', [DetalleTecnicoController::class, 'update']);
Route::post('/detalle-tecnicos/{detalleTecnico}/evidencias', [DetalleTecnicoController::class, 'subirEvidencia']);
Route::delete('/detalle-tecnicos/{detalleTecnico}/evidencias/{evidencia}', [DetalleTecnicoController::class, 'eliminarEvidencia']);
Route::delete('/detalle-tecnicos/{detalleTecnico}', [DetalleTecnicoController::class, 'destroy']);
});
