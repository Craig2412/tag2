<?php

namespace App\Http\Controllers;

use App\Models\Estatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
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

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

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

        return response()->json([
            'user' => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        // Inicia sesion sin validar rol especifico.
        return $this->loginWithRole($request, null);
    }

    public function loginAdmin(Request $request)
    {
        // Inicia sesion solo si el usuario es admin.
        return $this->loginWithRole($request, 'admin');
    }

    public function loginUser(Request $request)
    {
        // Inicia sesion solo si el usuario es user.
        return $this->loginWithRole($request, 'user');
    }

    public function logout(Request $request)
    {
        // Cierra la sesion actual eliminando el token.
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out']);
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
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if ($requiredRole && !$user->hasRole($requiredRole)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $token = $user->createToken('api-token', $this->abilitiesFor($user))->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }
}
