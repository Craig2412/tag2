<?php

namespace App\Services;

use App\Models\Comision;
use App\Models\Cotizacion;
use App\Models\CuentaPorPagar;
use App\Models\EstadoFinanciero;
use App\Models\PagoProveedorCuenta;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ComisionService
{
    /**
     * Creates the commission and its accounts payable for an approved quote.
     *
     * The method is transactional so it stays atomic even when called outside
     * the quote controller (the controller wraps the whole approval anyway).
     *
     * @param  array{id_proveedor_comisionista:int, tipo:string, valor:float|int|string, id_estado_financiero?:int|null, moneda?:string|null}  $payload
     */
    public function registrarDesdeCotizacion(Cotizacion $cotizacion, array $payload): Comision
    {
        return DB::transaction(function () use ($cotizacion, $payload) {
            $existente = Comision::query()
                ->deCotizacion($cotizacion->id)
                ->activas()
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($existente) {
                return $existente;
            }

            $tipo = $payload['tipo'];
            $valor = (float) $payload['valor'];
            $base = (float) $cotizacion->servicios()->sum('total_servicio');

            $this->validarValor($tipo, $valor, $base);

            $monto = $tipo === Comision::TIPO_PORCENTAJE
                ? round($base * $valor / 100, 2)
                : round($valor, 2);

            if ($monto <= 0) {
                throw ValidationException::withMessages([
                    'comision.valor' => 'El monto calculado de la comisión debe ser mayor a 0.',
                ]);
            }

            $idEstadoFinanciero = $payload['id_estado_financiero']
                ?? EstadoFinanciero::where('slug', 'pendiente')->value('id');

            $cuenta = CuentaPorPagar::create([
                'id_orden_compra' => null,
                'origen' => CuentaPorPagar::ORIGEN_COMISION,
                'id_cotizacion' => $cotizacion->id,
                'id_proveedor' => $payload['id_proveedor_comisionista'],
                'monto_total' => $monto,
                'saldo_pendiente' => $monto,
                'id_estado_financiero' => $idEstadoFinanciero,
            ]);

            return Comision::create([
                'id_cotizacion' => $cotizacion->id,
                'id_proveedor_comisionista' => $payload['id_proveedor_comisionista'],
                'tipo' => $tipo,
                'valor' => $valor,
                'base' => $base,
                'monto' => $monto,
                'id_tasa_cambio' => $cotizacion->id_tasa_cambio,
                'moneda' => $payload['moneda'] ?? $this->resolverMoneda($cotizacion),
                'estado' => Comision::ESTADO_DEVENGADA,
                'id_cuenta_por_pagar' => $cuenta->id,
            ])->load(['comisionista', 'cuentaPorPagar.estadoFinanciero']);
        });
    }

    /**
     * Reverses the active commission of a quote.
     *
     * If the payable already has payments applied, the reversal is blocked with a
     * validation error so the caller's transaction rolls back (the quote keeps its
     * approved status and finance can adjust the payment manually).
     */
    public function anularPorCotizacion(Cotizacion $cotizacion, string $motivo): ?Comision
    {
        return DB::transaction(function () use ($cotizacion, $motivo) {
            $comision = Comision::query()
                ->deCotizacion($cotizacion->id)
                ->activas()
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if (! $comision) {
                return null;
            }

            $cuenta = $comision->cuentaPorPagar;

            if ($cuenta) {
                $pagado = (float) PagoProveedorCuenta::where('id_cuenta_por_pagar', $cuenta->id)
                    ->sum('monto_asignado');

                if ($pagado > 0.009) {
                    throw ValidationException::withMessages([
                        'comision' => 'La comisión tiene pagos aplicados. Ajusta o anula el pago antes de rechazar la cotización.',
                    ]);
                }

                $cuenta->delete();
            }

            $comision->update([
                'estado' => Comision::ESTADO_ANULADA,
                'anulada_at' => now(),
                'anulada_por' => Auth::id(),
                'motivo_anulacion' => $motivo,
            ]);

            return $comision->refresh();
        });
    }

    /**
     * Keeps the commission state aligned with its payable balance.
     * Called after a payment assignment is created or reverted.
     */
    public function sincronizarEstadoPorCuenta(CuentaPorPagar $cuenta): void
    {
        $comision = Comision::where('id_cuenta_por_pagar', $cuenta->id)->first();

        if (! $comision || $comision->estado === Comision::ESTADO_ANULADA) {
            return;
        }

        $estado = ((float) $cuenta->saldo_pendiente <= 0.009)
            ? Comision::ESTADO_PAGADA
            : Comision::ESTADO_DEVENGADA;

        if ($comision->estado !== $estado) {
            $comision->update(['estado' => $estado]);
        }
    }

    private function validarValor(string $tipo, float $valor, float $base): void
    {
        if (! in_array($tipo, [Comision::TIPO_PORCENTAJE, Comision::TIPO_FIJO], true)) {
            throw ValidationException::withMessages([
                'comision.tipo' => 'El tipo de comisión debe ser porcentaje o fijo.',
            ]);
        }

        if ($valor <= 0) {
            throw ValidationException::withMessages([
                'comision.valor' => 'El valor de la comisión debe ser mayor a 0.',
            ]);
        }

        if ($tipo === Comision::TIPO_PORCENTAJE && $valor > 100) {
            throw ValidationException::withMessages([
                'comision.valor' => 'El porcentaje de comisión no puede superar 100.',
            ]);
        }

        if ($tipo === Comision::TIPO_PORCENTAJE && $base <= 0) {
            throw ValidationException::withMessages([
                'comision.valor' => 'La cotización no tiene monto base para calcular el porcentaje.',
            ]);
        }
    }

    private function resolverMoneda(Cotizacion $cotizacion): ?string
    {
        $cotizacion->loadMissing('tasaCambio.monedaCatalogo');

        return $cotizacion->tasaCambio?->monedaCatalogo?->codigo;
    }
}
