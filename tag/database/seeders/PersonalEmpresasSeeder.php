<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\PersonalEmpresa;
use App\Models\Personal;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class PersonalEmpresasSeeder extends Seeder
{
    public function run(): void
    {
        $usuario = Usuario::role('personal')->first();
        $empresa = Empresa::first();

        if (!$usuario || !$empresa) {
            return;
        }

        $personalModel = Personal::where('usuario_id', $usuario->id)->first();
        
        if ($personalModel) {
            PersonalEmpresa::firstOrCreate([
                'id_personal' => $personalModel->id,
                'id_empresa' => $empresa->id,
            ]);
        }
    }
}
