<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
// --- Imports Necesarios ---
use Illuminate\Http\Request;
use Illuminate\Http\Response;      // Para tipo de retorno
use Illuminate\Http\JsonResponse; // Para tipo de retorno y respuesta JSON
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
// --- Fin Imports Necesarios ---
// Imports de Inertia (los dejamos por si acaso)
use Inertia\Inertia;
use Inertia\Response as InertiaResponse; // Alias para evitar conflicto

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view (for Inertia/web).
     * Probablemente no será llamado por tu SPA Vue separada.
     */
    public function create(): InertiaResponse
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     * MODIFICADO para devolver JSON a peticiones SPA.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): Response|JsonResponse // Tipo de retorno modificado
    {
        // La validación se ejecuta primero. Si falla y la petición pide JSON
        // (tiene Accept: application/json), Laravel automáticamente
        // devolverá una respuesta JSON con los errores (código 422).
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Si la validación pasa, crea el usuario
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Dispara el evento Registered (para cosas como enviar email de verificación si está configurado)
        event(new Registered($user));

        // Inicia sesión con el usuario recién creado
        Auth::login($user);

        // Verifica si la petición pide una respuesta JSON
        if ($request->wantsJson()) {
            // Para la SPA: Registro exitoso.
            // Devolvemos código 201 Created y opcionalmente los datos del usuario creado.
            return response()->json($user, 201);
            // Alternativa (si no necesitas devolver el usuario): return response(status: 201);
        }

        // Para una petición web normal (si la hubiera), mantiene la redirección original
        return redirect(route('dashboard', absolute: false));
    }
}