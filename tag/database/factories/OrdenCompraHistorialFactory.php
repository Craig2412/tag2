<?php

namespace Database\Factories;

use App\Models\OrdenCompraHistorial;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrdenCompraHistorialFactory extends Factory
{
    protected $model = OrdenCompraHistorial::class;

    public function definition(): array
    {
        return [
            'orden_compra_id' => 1,
            'estatus_anterior' => 1,
            'estatus_nuevo' => 2,
            'usuario_id' => 1,
            'comentario' => $this->faker->sentence(),
        ];
    }
}
