<?php

namespace App\Services;

use App\Events\AtencionEstatusActualizado;
use App\Models\Atencion;
use App\Models\Cliente;
use App\Models\ClienteEmpresa;
use App\Models\EstadoAtencion;
use App\Models\EtapaComercial;
use App\Models\Personal;
use App\Models\PersonalEmpresa;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AtencionService
{
    public function __construct(private readonly ClienteService $clienteService)
    {
    }

    /**
     * Crea una atención resolviendo (o creando) el cliente dentro de una transacción.
     *
     * @throws ValidationException
     */
    public function createAtencion(array $data): Atencion
    {
        $estado = EstadoAtencion::where('slug', 'abierta')->first();

        if (! $estado) {
            throw new \RuntimeException('Estado "abierta" no configurado en el catálogo');
        }

        return DB::transaction(function () use ($data, $estado) {
            $cliente = ! empty($data['cliente'])
                ? $this->clienteService->findOrCreateCliente($data['cliente'])
                : Cliente::findOrFail($data['id_cliente']);

            $personalId = $this->resolverPersonalAsignado($cliente->id);

            if (! $personalId) {
                throw ValidationException::withMessages([
                    'id_cliente' => ['No hay personal disponible para asignar la atención'],
                ]);
            }

            $attentionData = collect($data)->except('cliente')->toArray();
            $attentionData['id_cliente'] = $cliente->id;
            $attentionData['id_personal'] = $personalId;
            $attentionData['id_estado_atencion'] = $estado->id;

            // Asignar etapa comercial por defecto ("atencion")
            $etapaDefault = EtapaComercial::where('slug', 'atencion')->first();
            if ($etapaDefault) {
                $attentionData['id_etapa_comercial'] = $etapaDefault->id;
            }

            $atencion = Atencion::create($attentionData);

            // Registrar estatus inicial en el historial
            event(new AtencionEstatusActualizado(
                atencion: $atencion,
                estatusAnterior: null,
                estatusNuevo: $atencion->id_estado_atencion,
                comentario: 'Atención creada',
            ));

            return $atencion;
        });
    }

    /**
     * Selecciona el personal con menos atenciones abiertas (o el de la empresa del cliente).
     */
    private function resolverPersonalAsignado(int $idCliente): ?int
    {
        $clienteEmpresa = ClienteEmpresa::where('id_cliente', $idCliente)
            ->orderBy('id')
            ->first();

        if ($clienteEmpresa) {
            $personalEmpresa = PersonalEmpresa::where('id_empresa', $clienteEmpresa->id_empresas)
                ->inRandomOrder()
                ->first();

            if ($personalEmpresa) {
                return $personalEmpresa->id_personal;
            }
        }

        $estadoCerradoGanado = EstadoAtencion::where('slug', 'cerrada_ganada')->value('id') ?? 0;
        $estadoCerradoPerdido = EstadoAtencion::where('slug', 'cerrada_perdida')->value('id') ?? 0;
        $personalIds = Personal::pluck('id')->all();

        if (empty($personalIds)) {
            return null;
        }

        $conteos = DB::table('atenciones')
            ->select('id_personal', DB::raw('COUNT(*) as total'))
            ->whereNull('deleted_at')
            ->whereNotIn('id_estado_atencion', [$estadoCerradoGanado, $estadoCerradoPerdido])
            ->whereIn('id_personal', $personalIds)
            ->groupBy('id_personal')
            ->pluck('total', 'id_personal')
            ->all();

        $minimo = null;
        $candidatos = [];

        foreach ($personalIds as $personalId) {
            $total = (int) ($conteos[$personalId] ?? 0);

            if ($minimo === null || $total < $minimo) {
                $minimo = $total;
                $candidatos = [$personalId];

                continue;
            }

            if ($total === $minimo) {
                $candidatos[] = $personalId;
            }
        }

        if (empty($candidatos)) {
            return null;
        }

        return $candidatos[array_rand($candidatos)];
    }
}
