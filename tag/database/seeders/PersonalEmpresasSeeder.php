<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\PersonalEmpresa;
use App\Models\User;
use Illuminate\Database\Seeder;

class PersonalEmpresasSeeder extends Seeder
{
    public function run(): void
    {
        $personal = User::role('personal')->first();
        $empresa = Empresa::first();

        if (!$personal || !$empresa) {
            return;
        }

        PersonalEmpresa::firstOrCreate([
            'id_personal' => $personal->id,
            'id_empresa' => $empresa->id,
        ]);
    }
}
