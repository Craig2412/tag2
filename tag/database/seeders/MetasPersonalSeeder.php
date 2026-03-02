<?php

namespace Database\Seeders;

use App\Models\Meta;
use App\Models\MetaPersonal;
use App\Models\User;
use Illuminate\Database\Seeder;

class MetasPersonalSeeder extends Seeder
{
    public function run(): void
    {
        $personalIds = User::role('personal')->orderBy('id')->pluck('id')->values();
        $metas = Meta::orderBy('id')->get();

        if ($personalIds->isEmpty() || $metas->isEmpty()) {
            return;
        }

        foreach ($metas as $index => $meta) {
            $idPersonal = $personalIds[$index % $personalIds->count()];

            MetaPersonal::firstOrCreate([
                'id_meta' => $meta->id,
                'id_personal' => $idPersonal,
            ]);
        }
    }
}
