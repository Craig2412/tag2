<?php

namespace Database\Factories;

use App\Models\AtencionHistorial;
use Illuminate\Database\Eloquent\Factories\Factory;

class AtencionHistorialFactory extends Factory
{
    protected $model = AtencionHistorial::class;

    public function definition(): array
    {
        return [
            'atencion_id' => 1,
            'estatus_anterior' => 1,
            'estatus_nuevo' => 2,
            'usuario_id' => 1,
            'comentario' => $this->faker->sentence(),
        ];
    }
}
