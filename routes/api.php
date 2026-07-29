<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\TecnicoController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\DetalleTecnicoController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\WhatsappController;

// PRUEBA RÁPIDA PARA SABER SI EL BACKEND ESTÁ VIVO
Route::get('health', fn () => response()->json(['ok' => true, 'message' => 'Backend funcionando']));

// WHATSAPP BUSINESS
Route::get('whatsapp/status', [WhatsappController::class, 'status']);
Route::post('whatsapp/embedded-signup', [WhatsappController::class, 'embeddedSignup'])
    ->middleware('throttle:10,1');
Route::get('whatsapp/webhook', [WhatsappController::class, 'verifyWebhook']);
Route::post('whatsapp/webhook', [WhatsappController::class, 'receiveWebhook']);

// CLIENTES
Route::get('clientes', [ClienteController::class, 'index']);
Route::post('clientes', [ClienteController::class, 'store']);
Route::get('clientes/{id}', [ClienteController::class, 'show']);
Route::put('clientes/{id}', [ClienteController::class, 'update']);
Route::delete('clientes/{id}', [ClienteController::class, 'destroy']);

// TÉCNICOS
Route::get('tecnicos', [TecnicoController::class, 'index']);
Route::post('tecnicos', [TecnicoController::class, 'store']);
Route::get('tecnicos/{id}', [TecnicoController::class, 'show']);
Route::put('tecnicos/{id}', [TecnicoController::class, 'update']);
Route::delete('tecnicos/{id}', [TecnicoController::class, 'destroy']);

// EQUIPOS
Route::get('equipos', [EquipoController::class, 'index']);
Route::post('equipos', [EquipoController::class, 'store']);
Route::get('equipos/{id}', [EquipoController::class, 'show']);
Route::put('equipos/{id}', [EquipoController::class, 'update']);
Route::delete('equipos/{id}', [EquipoController::class, 'destroy']);

// SERVICIOS
Route::get('servicios', [ServicioController::class, 'index']);
Route::post('servicios', [ServicioController::class, 'store']);
Route::get('servicios/{id}', [ServicioController::class, 'show']);
Route::put('servicios/{id}', [ServicioController::class, 'update']);
Route::delete('servicios/{id}', [ServicioController::class, 'destroy']);

// DETALLES TÉCNICOS
Route::get('detalles-tecnicos', [DetalleTecnicoController::class, 'index']);
Route::post('detalles-tecnicos', [DetalleTecnicoController::class, 'store']);
Route::get('detalles-tecnicos/{id}', [DetalleTecnicoController::class, 'show']);
Route::put('detalles-tecnicos/{id}', [DetalleTecnicoController::class, 'update']);
Route::delete('detalles-tecnicos/{id}', [DetalleTecnicoController::class, 'destroy']);

// PAGOS
Route::get('pagos', [PagoController::class, 'index']);
Route::post('pagos', [PagoController::class, 'store']);
Route::get('pagos/{id}', [PagoController::class, 'show']);
Route::put('pagos/{id}', [PagoController::class, 'update']);
Route::delete('pagos/{id}', [PagoController::class, 'destroy']);

// MARCAS
Route::get('marcas', [MarcaController::class, 'index']);
Route::post('marcas', [MarcaController::class, 'store']);
Route::get('marcas/{id}', [MarcaController::class, 'show']);
Route::put('marcas/{id}', [MarcaController::class, 'update']);
Route::delete('marcas/{id}', [MarcaController::class, 'destroy']);
