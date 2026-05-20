<?php

namespace App\Services;

use App\Models\Personal;
use App\Models\Usuario;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PersonalService
{
    /**
     * Crea un personal y su usuario asociado dentro de una transacción.
     */
    public function createPersonal(array $data): Personal
    {
        return DB::transaction(function () use ($data) {
            // 1. Crear el usuario si viene anidado y no hay usuario_id
            if (isset($data['usuario']) && empty($data['usuario_id'])) {
                $usuarioData = $data['usuario'];
                $usuarioData['clave'] = Hash::make($usuarioData['clave']);

                $usuario = Usuario::create($usuarioData);

                if (isset($usuarioData['roles'])) {
                    $usuario->syncRoles($usuarioData['roles']);
                } else {
                    $usuario->assignRole('personal'); // Rol por defecto
                }

                $data['usuario_id'] = $usuario->id;
            }

            // Validar que exista un usuario_id final
            if (empty($data['usuario_id'])) {
                throw new Exception('Se requiere un usuario_id o los datos para crear un usuario nuevo.');
            }

            // 2. Crear el personal
            return Personal::create(collect($data)->except('usuario')->toArray());
        });
    }

    /**
     * Actualiza un personal y su usuario asociado dentro de una transacción.
     */
    public function updatePersonal(Personal $personal, array $data): Personal
    {
        return DB::transaction(function () use ($personal, $data) {
            // 1. Actualizar el perfil de personal
            $personal->update(collect($data)->except('usuario')->toArray());

            // 2. Actualizar el usuario si viene anidado y el personal ya tiene uno asociado
            if (isset($data['usuario']) && $personal->usuario_id) {
                $usuario = $personal->usuario;
                $usuarioData = collect($data['usuario'])->except(['roles', 'clave'])->toArray();

                if (! empty($data['usuario']['clave'])) {
                    $usuarioData['clave'] = Hash::make($data['usuario']['clave']);
                }

                $usuario->update($usuarioData);

                if (isset($data['usuario']['roles'])) {
                    $usuario->syncRoles($data['usuario']['roles']);
                }
            }

            return $personal->fresh();
        });
    }
}
