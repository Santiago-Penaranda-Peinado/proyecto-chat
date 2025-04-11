<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
// --- Imports Necesarios ---
use Illuminate\Http\Request;
use Illuminate\Http\Response;         // Necesario para tipo de retorno
use Illuminate\Http\JsonResponse;    // Necesario para tipo de retorno y respuesta JSON
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException; // Para manejar errores de login
// --- Fin Imports Necesarios ---
// Imports de Inertia (los dejamos por si acaso, aunque 'create' no lo use tu SPA)
use Inertia\Inertia;
use Inertia\Response as InertiaResponse; // Alias para evitar conflicto con Illuminate\Http\Response

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view (for Inertia/web).
     * Probablemente no será llamado por tu SPA Vue separada.
     */
    public function create(): InertiaResponse
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     * MODIFICADO para devolver JSON a peticiones SPA.
     */
    public function store(LoginRequest $request): Response|JsonResponse // Tipo de retorno modificado
    {
        try {
            // Usa las reglas de validación/autenticación de LoginRequest
            $request->authenticate(); 

            // Regenera la sesión (importante para seguridad)
            // --- DEBUG: Comentamos temporalmente la regeneración ---
            // $request->session()->regenerate(); // <---- ¡LÍNEA COMENTADA PARA LA PRUEBA!
            // --- FIN DEBUG ---

            // Verifica si la petición pide una respuesta JSON
            if ($request->wantsJson()) {
                // Para la SPA: Login exitoso, la cookie de sesión ya se estableció.
                // Devolvemos un código 204 "No Content" que indica éxito sin cuerpo.
                return response()->noContent(); // HTTP 204 No Content
            }

            // Para una petición web normal (si la hubiera), mantiene la redirección original
            return redirect()->intended(route('dashboard', absolute: false));

        } catch (ValidationException $e) {
            // Si la autenticación falla (capturada por authenticate())
            if ($request->wantsJson()) {
                // Para la SPA: Devuelve los errores de validación en formato JSON
                 return response()->json([
                    'message' => $e->getMessage(), // Mensaje general de error
                    'errors' => $e->errors(),      // Errores específicos por campo
                 ], 422); // HTTP 422 Unprocessable Entity (error de validación)
            }
             // Para una petición web normal: relanza la excepción para el manejo por defecto
            throw $e;
        }
    }

    /**
     * Destroy an authenticated session.
     * MODIFICADO para devolver JSON a peticiones SPA.
     */
    public function destroy(Request $request): Response|JsonResponse // Tipo de retorno modificado
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // Verifica si la petición pide una respuesta JSON
        if ($request->wantsJson()) {
            // Para la SPA: Logout exitoso.
            return response()->noContent(); // HTTP 204 No Content
        }

        // Para una petición web normal (si la hubiera), mantiene la redirección original
        return redirect('/');
    }
}