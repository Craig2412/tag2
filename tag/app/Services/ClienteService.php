<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ClienteService
{
    /**
     * Crea un cliente y su usuario asociado (si se envían datos) dentro de una transacción.
     */
    public function createCliente(array $data): Cliente
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
                    $usuario->assignRole('cliente'); // Rol por defecto
                }

                $data['usuario_id'] = $usuario->id;
            }

            // 2. Crear el cliente
            return Cliente::create(collect($data)->except('usuario')->toArray());
        });
    }

    /**
     * Actualiza un cliente y su usuario asociado dentro de una transacción.
     */
    public function updateCliente(Cliente $cliente, array $data): Cliente
    {
        return DB::transaction(function () use ($cliente, $data) {
            // 1. Actualizar el perfil del cliente
            $cliente->update(collect($data)->except('usuario')->toArray());

            // 2. Actualizar el usuario si viene anidado y el cliente ya tiene uno asociado
            if (isset($data['usuario']) && $cliente->usuario_id) {
                $usuario = $cliente->usuario;
                $usuarioData = collect($data['usuario'])->except(['roles', 'clave'])->toArray();
                
                if (!empty($data['usuario']['clave'])) {
                    $usuarioData['clave'] = Hash::make($data['usuario']['clave']);
                }
                
                $usuario->update($usuarioData);

                if (isset($data['usuario']['roles'])) {
                    $usuario->syncRoles($data['usuario']['roles']);
                }
            }

            return $cliente->fresh();
        });
    }
}
