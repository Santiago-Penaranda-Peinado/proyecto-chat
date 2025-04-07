<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Session\Middleware\StartSession; // <-- IMPORTANTE: Importar middleware

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Ruta para obtener el usuario autenticado via Sanctum
// Esta ruta ya usa 'auth:sanctum' que DEBERÍA manejar la sesión stateful,
// pero no añadimos StartSession aquí explícitamente por ahora.
Route::middleware('auth:web')->get('/user', function (Request $request) { // <-- Cambiado aquí
    return $request->user();
});

// --- RUTAS DE AUTENTICACIÓN CON StartSession MANUAL ---

Route::post('register', [RegisteredUserController::class, 'store'])
      ->middleware(StartSession::class); // <-- Añadido StartSession

Route::post('login', [AuthenticatedSessionController::class, 'store'])
      ->middleware(StartSession::class); // <-- Añadido StartSession

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
       // Aplicamos ambos: auth para asegurar que esté logueado,
       // y StartSession para asegurar que la sesión esté disponible para el logout.
      ->middleware(['auth:sanctum', StartSession::class]) 
      ->name('api.logout');

// --- FIN RUTAS DE AUTENTICACIÓN ---

// --- Aquí añadirás más rutas API para tu chat ---