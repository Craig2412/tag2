<?php

namespace Database\Seeders;

use App\Models\Atencion;
use App\Models\AtencionPersonal;
use App\Models\User;
use Illuminate\Database\Seeder;

class AtencionesPersonalSeeder extends Seeder
{
    public function run(): void
    {
        $atencion = Atencion::first();
        $personal = User::role('personal')->first();

        if (!$atencion || !$personal) {
            return;
        }

        AtencionPersonal::firstOrCreate([
            'id_atencion' => $atencion->id,
            'id_personal' => $personal->id,
        ]);
    }
}
