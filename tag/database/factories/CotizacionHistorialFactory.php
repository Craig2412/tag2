<?php

namespace Database\Factories;

use App\Models\CotizacionHistorial;
use Illuminate\Database\Eloquent\Factories\Factory;

class CotizacionHistorialFactory extends Factory
{
    protected $model = CotizacionHistorial::class;

    public function definition(): array
    {
        return [
            'cotizacion_id' => 1,
            'estatus_anterior' => 1,
            'estatus_nuevo' => 2,
            'usuario_id' => 1,
            'comentario' => $this->faker->sentence(),
        ];
    }
}
