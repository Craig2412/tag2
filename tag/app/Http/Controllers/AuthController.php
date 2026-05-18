<?php

namespace App\Http\Controllers;

use App\Events\PermissionsUpdated;
use App\Models\Usuario;
use App\Models\Cliente;
use App\Models\Personal;
use App\Models\TipoContribuyente;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * Registrar nuevo usuario (cliente)
     *
     * Crea un usuario con rol cliente y su respectivo perfil.
     *
     * @unauthenticated
     * @bodyParam nombre string required Nombre del usuario. Ejemplo: Juan
     * @bodyParam apellido string required Apellido del usuario. Ejemplo: Pérez
     * @bodyParam correo string required Correo electrónico para login. Ejemplo: juan@perez.com
     * @bodyParam correo_contacto string Correo electrónico para comunicaciones. Ejemplo: contacto@perez.com
     * @bodyParam clave string required Contraseña (mínimo 8 caracteres). Ejemplo: password123
     * @bodyParam cedula string Número de cédula de identidad. Ejemplo: 12345678
     * @bodyParam telefono string Número de teléfono. Ejemplo: 04121234567
     * @bodyParam id_tipo_contribuyente int ID del tipo de contribuyente. Ejemplo: 1
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'cedula' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:255'],
            'correo_contacto' => ['nullable', 'email', 'max:255'],
            'id_tipo_contribuyente' => ['nullable', 'exists:tipos_contribuyentes,id'],
            'correo' => ['required', 'email', 'max:255', 'unique:usuarios,correo'],
            'clave' => ['required', 'string', Password::min(8)],
        ]);

        return DB::transaction(function () use ($data, $request) {
            $userRole = Role::where('name', 'user')->first();
            $tipoContribuyenteNormal = TipoContribuyente::firstOrCreate(
                ['tipo_contribuyente' => 'Normal'],
                ['porcentaje_iva' => 16]
            );

            // 1. Crear Usuario (Auth)
            $usuario = Usuario::create([
                'nombre_usuario' => $data['nombre'] . ' ' . $data['apellido'],
                'correo' => $data['correo'],
                'clave' => Hash::make($data['clave']),
                'esta_activo' => true,
            ]);

            if ($userRole) {
                $usuario->syncRoles([$userRole]);
            }

            // 2. Crear Perfil de Cliente
            $cliente = Cliente::create([
                'usuario_id' => $usuario->id,
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'cedula' => $data['cedula'] ?? null,
                'telefono' => $data['telefono'] ?? null,
                'correo_contacto' => $data['correo_contacto'] ?? $data['correo'], // Default al correo de login si no hay
                'id_tipo_contribuyente' => $data['id_tipo_contribuyente'] ?? $tipoContribuyenteNormal->id,
            ]);

            $token = $usuario->createToken('api-token', $this->abilitiesFor($usuario))->plainTextToken;
            $usuario->load(['roles', 'permissions']);

            return response()->json([
                'usuario' => $usuario,
                'cliente' => $cliente,
                'token' => $token,
            ], 201);
        });
    }

    /**
     * Registrar nuevo usuario personal
     *
     * Crea un usuario con rol personal (agente interno) y su respectivo perfil.
     *
     * @unauthenticated
     * @bodyParam nombre string required Nombre. Ejemplo: Pedro
     * @bodyParam apellido string required Apellido. Ejemplo: Gómez
     * @bodyParam correo string required Correo electrónico para login. Ejemplo: pedro@gomez.com
     * @bodyParam correo_institucional string Correo institucional. Ejemplo: pedro.institucional@tag.com
     * @bodyParam clave string required Contraseña. Ejemplo: password123
     * @bodyParam porcentaje_comision number Porcentaje de comisión. Ejemplo: 10
     */
    public function registerPersonal(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'cedula' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:255'],
            'porcentaje_comision' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'correo' => ['required', 'email', 'max:255', 'unique:usuarios,correo'],
            'correo_institucional' => ['nullable', 'email', 'max:255'],
            'clave' => ['required', 'string', Password::min(8)],
        ]);

        return DB::transaction(function () use ($data) {
            $personalRole = Role::where('name', 'personal')->first();

            // 1. Crear Usuario (Auth)
            $usuario = Usuario::create([
                'nombre_usuario' => $data['nombre'] . ' ' . $data['apellido'],
                'correo' => $data['correo'],
                'clave' => Hash::make($data['clave']),
                'esta_activo' => true,
            ]);

            if ($personalRole) {
                $usuario->syncRoles([$personalRole]);
            }

            // 2. Crear Perfil Personal
            $personal = Personal::create([
                'usuario_id' => $usuario->id,
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'cedula' => $data['cedula'] ?? null,
                'telefono' => $data['telefono'] ?? null,
                'correo_institucional' => $data['correo_institucional'] ?? $data['correo'],
                'porcentaje_comision' => $data['porcentaje_comision'] ?? 0,
            ]);

            $usuario->load(['roles', 'permissions']);

            return response()->json([
                'usuario' => $usuario,
                'personal' => $personal,
            ], 201);
        });
    }

    /**
     * Iniciar sesión
     *
     * Autentica al usuario y devuelve su token de acceso.
     *
     * @unauthenticated
     * @bodyParam correo string required Correo electrónico del usuario. Ejemplo: admin@example.com
     * @bodyParam clave string required Contraseña del usuario. Ejemplo: password
     */
    public function login(Request $request)
    {
        return $this->loginWithRole($request, null);
    }

    /**
     * Iniciar sesión como administrador
     *
     * Autentica al usuario verificando que posea el rol admin.
     *
     * @unauthenticated
     * @bodyParam correo string required Correo electrónico. Ejemplo: admin@example.com
     * @bodyParam clave string required Contraseña. Ejemplo: password
     */
    public function loginAdmin(Request $request)
    {
        return $this->loginWithRole($request, 'admin');
    }

    /**
     * Iniciar sesión como usuario regular
     *
     * Autentica al usuario verificando que posea el rol user.
     *
     * @unauthenticated
     * @bodyParam correo string required Correo electrónico. Ejemplo: user@example.com
     * @bodyParam clave string required Contraseña. Ejemplo: password
     */
    public function loginUser(Request $request)
    {
        return $this->loginWithRole($request, 'user');
    }

    /**
     * Cerrar sesión
     *
     * Invalida el token de acceso actual del usuario autenticado.
     */
    public function logout(Request $request)
    {
        $usuario = $request->user();
        $request->user()?->currentAccessToken()?->delete();

        AuditLogger::logAuthEvent('LOGOUT', $request, $usuario, true, 'Logout exitoso', [], 200);

        return response()->json(['message' => 'Sesión cerrada correctamente']);
    }

    /**
     * Get the authenticated user's current profile, roles, and permissions.
     * Used by the Next.js frontend to silently refresh the session JWT.
     */
    public function me(Request $request)
    {
        $usuario = $request->user()->load(['roles', 'permissions']);
        return response()->json(['usuario' => $usuario]);
    }

    /**
     * Manually trigger a PermissionsUpdated broadcast for a user.
     * Useful for testing and for admin interfaces that change roles.
     *
     * @param int $userId
     */
    public static function broadcastPermissionsChanged(int $userId): void
    {
        broadcast(new PermissionsUpdated($userId))->toOthers();
    }

    private function abilitiesFor(Usuario $usuario): array
    {
        return $usuario->getAllPermissions()->pluck('name')->values()->all();
    }

    private function loginWithRole(Request $request, ?string $requiredRole)
    {
        $data = $request->validate([
            'correo' => ['required', 'email'],
            'clave' => ['required', 'string'],
        ]);

        $usuario = Usuario::where('correo', $data['correo'])->first();

        if (!$usuario || !Hash::check($data['clave'], $usuario->clave)) {
            AuditLogger::logAuthEvent(
                'LOGIN',
                $request,
                null,
                false,
                'Credenciales inválidas',
                ['correo' => $data['correo']],
                401
            );

            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        if ($requiredRole && !$usuario->hasRole($requiredRole)) {
            AuditLogger::logAuthEvent(
                'LOGIN',
                $request,
                $usuario,
                false,
                'Acceso denegado por rol',
                ['required_role' => $requiredRole],
                403
            );

            return response()->json(['message' => 'Acceso denegado'], 403);
        }

        $token = $usuario->createToken('api-token', $this->abilitiesFor($usuario))->plainTextToken;

        AuditLogger::logAuthEvent(
            'LOGIN',
            $request,
            $usuario,
            true,
            'Login exitoso',
            ['required_role' => $requiredRole],
            200
        );

        $usuario->load(['roles', 'permissions']);

        return response()->json([
            'usuario' => $usuario,
            'token' => $token,
        ]);
    }
}
