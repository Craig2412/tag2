<?php

namespace App\Http\Controllers;

use App\Models\Estatus;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    /**
     * Registrar nuevo usuario (cliente)
     *
     * Crea un usuario con rol cliente y devuelve su token de acceso.
     *
     * @unauthenticated
     * @bodyParam nombre string required Nombre del usuario. Ejemplo: Juan
     * @bodyParam apellido string required Apellido del usuario. Ejemplo: Pérez
     * @bodyParam email string required Correo electrónico. Ejemplo: juan@perez.com
     * @bodyParam password string required Contraseña (mínimo 8 caracteres). Ejemplo: password123
     * @bodyParam cedula string Número de cédula de identidad. Ejemplo: 12345678
     * @bodyParam telefono string Número de teléfono. Ejemplo: 04121234567
     * @bodyParam porcentaje_comision number Porcentaje de comisión (0-100). Ejemplo: 10
     * @bodyParam id_tipo_contribuyente int ID del tipo de contribuyente. Ejemplo: 1
     */
    public function register(Request $request)
    {
        // Registra un usuario, asigna rol y devuelve el token.
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'cedula' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:255'],
            'porcentaje_comision' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'id_tipo_contribuyente' => ['nullable', 'exists:tipos_contribuyentes,id'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'correo_institucional' => ['nullable', 'email', 'max:255'],
            'password' => ['required', 'string', Password::min(8)],
        ]);

        $userRole = Role::where('name', 'user')->first();
        $estatusActivo = Estatus::where('estatus', 'activo')->first();
        $tipoContribuyenteNormal = \App\Models\TipoContribuyente::firstOrCreate(
            ['tipo_contribuyente' => 'Normal'],
            ['porcentaje_iva' => 16]
        );

        $user = User::create([
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'],
            'cedula' => $data['cedula'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'porcentaje_comision' => $data['porcentaje_comision'] ?? null,
            'id_tipo_contribuyente' => $data['id_tipo_contribuyente'] ?? $tipoContribuyenteNormal->id,
            'id_rol' => $userRole?->id,
            'id_estatus' => $estatusActivo?->id,
            'email' => $data['email'],
            'correo_institucional' => $data['correo_institucional'] ?? null,
            'password' => Hash::make($data['password']),
        ]);

        if ($userRole) {
            $user->syncRoles([$userRole]);
        }

        $token = $user->createToken('api-token', $this->abilitiesFor($user))->plainTextToken;
        $user->load(['roles', 'permissions', 'tipoContribuyente']);

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Registrar nuevo usuario personal
     *
     * Crea un usuario con rol personal (agente interno).
     *
     * @unauthenticated
     * @bodyParam nombre string required Nombre del usuario. Ejemplo: Pedro
     * @bodyParam apellido string required Apellido del usuario. Ejemplo: Gómez
     * @bodyParam email string required Correo electrónico. Ejemplo: pedro@gomez.com
     * @bodyParam password string required Contraseña. Ejemplo: password123
     */
    public function registerPersonal(Request $request)
    {
        // Registra un usuario con rol personal.
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'cedula' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:255'],
            'porcentaje_comision' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'id_tipo_contribuyente' => ['nullable', 'exists:tipos_contribuyentes,id'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'correo_institucional' => ['nullable', 'email', 'max:255'],
            'password' => ['required', 'string', Password::min(8)],
        ]);

        $personalRole = Role::where('name', 'personal')->first();
        $estatusActivo = Estatus::where('estatus', 'activo')->first();
        $tipoContribuyenteNormal = \App\Models\TipoContribuyente::firstOrCreate(
            ['tipo_contribuyente' => 'Normal'],
            ['porcentaje_iva' => 16]
        );

        $user = User::create([
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'],
            'cedula' => $data['cedula'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'porcentaje_comision' => $data['porcentaje_comision'] ?? null,
            'id_tipo_contribuyente' => $data['id_tipo_contribuyente'] ?? $tipoContribuyenteNormal->id,
            'id_rol' => $personalRole?->id,
            'id_estatus' => $estatusActivo?->id,
            'email' => $data['email'],
            'correo_institucional' => $data['correo_institucional'] ?? null,
            'password' => Hash::make($data['password']),
        ]);

        if ($personalRole) {
            $user->syncRoles([$personalRole]);
        }

        $user->load(['roles', 'permissions', 'tipoContribuyente']);

        return response()->json([
            'user' => $user,
        ], 201);
    }

    /**
     * Iniciar sesión
     *
     * Autentica al usuario y devuelve su token de acceso.
     *
     * @unauthenticated
     * @bodyParam email string required Correo electrónico del usuario. Ejemplo: admin@example.com
     * @bodyParam password string required Contraseña del usuario. Ejemplo: password
     */
    public function login(Request $request)
    {
        // Inicia sesion sin validar rol especifico.
        return $this->loginWithRole($request, null);
    }

    /**
     * Iniciar sesión como administrador
     *
     * Autentica al usuario verificando que posea el rol admin.
     *
     * @unauthenticated
     * @bodyParam email string required Correo electrónico del administrador. Ejemplo: admin@example.com
     * @bodyParam password string required Contraseña. Ejemplo: password
     */
    public function loginAdmin(Request $request)
    {
        // Inicia sesion solo si el usuario es admin.
        return $this->loginWithRole($request, 'admin');
    }

    /**
     * Iniciar sesión como usuario regular
     *
     * Autentica al usuario verificando que posea el rol user.
     *
     * @unauthenticated
     * @bodyParam email string required Correo electrónico del usuario. Ejemplo: user@example.com
     * @bodyParam password string required Contraseña. Ejemplo: password
     */
    public function loginUser(Request $request)
    {
        // Inicia sesion solo si el usuario es user.
        return $this->loginWithRole($request, 'user');
    }

    /**
     * Cerrar sesión
     *
     * Invalida el token de acceso actual del usuario autenticado.
     */
    public function logout(Request $request)
    {
        // Cierra la sesion actual eliminando el token.
        $user = $request->user();
        $request->user()?->currentAccessToken()?->delete();

        AuditLogger::logAuthEvent('LOGOUT', $request, $user, true, 'Logout exitoso', [], 200);

        return response()->json(['message' => 'Sesión cerrada correctamente']);
    }

    private function abilitiesFor(User $user): array
    {
        return $user->getAllPermissions()->pluck('name')->values()->all();
    }

    private function loginWithRole(Request $request, ?string $requiredRole)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            AuditLogger::logAuthEvent(
                'LOGIN',
                $request,
                null,
                false,
                'Credenciales inválidas',
                ['email' => $data['email']],
                401
            );

            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        if ($requiredRole && !$user->hasRole($requiredRole)) {
            AuditLogger::logAuthEvent(
                'LOGIN',
                $request,
                $user,
                false,
                'Acceso denegado por rol',
                ['required_role' => $requiredRole],
                403
            );

            return response()->json(['message' => 'Acceso denegado'], 403);
        }

        $token = $user->createToken('api-token', $this->abilitiesFor($user))->plainTextToken;

        AuditLogger::logAuthEvent(
            'LOGIN',
            $request,
            $user,
            true,
            'Login exitoso',
            ['required_role' => $requiredRole],
            200
        );

        $user->load(['roles', 'permissions', 'tipoContribuyente']);


        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }
}
