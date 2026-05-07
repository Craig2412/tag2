<?php

namespace App\Http\Controllers;

use App\Models\Meta;
use App\Http\Resources\MetaResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class MetaController extends Controller
{
    /**
     * Listar todas las metas globales
     */
    public function index()
    {
        return MetaResource::collection(
            Meta::with(['temporalidad' => fn($q) => $q->withTrashed()])
                ->orderBy('id')
                ->get()
        );
    }

    /**
     * Crear una nueva meta global
     *
     * @bodyParam nombre string required Nombre descriptivo de la meta. Ejemplo: Cierre de Atenciones Mensual
     * @bodyParam tipo_entidad string required Tipo de entidad: atencion, cotizacion, orden_compra
     * @bodyParam estatus_objetivo string required Slug del estado que marca el hito. Ejemplo: aprobado
     * @bodyParam es_monetario boolean required Si true, suma montos; si false, cuenta registros. No aplica para atencion.
     * @bodyParam valor_objetivo number required Meta numérica o monetaria a alcanzar. Ejemplo: 50
     * @bodyParam id_temporalidad int required ID de la temporalidad (Mensual, Semanal, etc). Ejemplo: 1
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => [
                'required', 'string', 'max:150',
                Rule::unique('metas', 'nombre')->whereNull('deleted_at'),
            ],
            'tipo_entidad' => ['required', Rule::in(['atencion', 'cotizacion', 'orden_compra'])],
            'estatus_objetivo' => ['required', 'string', 'max:50'],
            'es_monetario' => ['required', 'boolean'],
            'valor_objetivo' => ['required', 'numeric', 'min:0.01'],
            'id_temporalidad' => ['required', Rule::exists('temporalidades', 'id')->whereNull('deleted_at')],
        ]);

        // Regla de negocio: las atenciones no tienen valor monetario, solo se cuentan.
        if ($data['tipo_entidad'] === 'atencion' && $data['es_monetario']) {
            return response()->json([
                'message' => 'Las metas de tipo "atencion" no pueden ser monetarias.',
                'errors'  => ['es_monetario' => ['Las atenciones solo se pueden contar, no sumar montos.']],
            ], 422);
        }

        // Valida que el slug exista en la tabla de estados del dominio correspondiente.
        if (!$this->slugExiste($data['tipo_entidad'], $data['estatus_objetivo'])) {
            return response()->json([
                'message' => 'El slug de estado no es válido para el tipo de entidad indicado.',
                'errors'  => ['estatus_objetivo' => ["El slug \"{$data['estatus_objetivo']}\" no existe en los estados de {$data['tipo_entidad']}."]],
            ], 422);
        }

        $item = Meta::create($data);
        $item->load(['temporalidad' => fn($q) => $q->withTrashed()]);

        return new MetaResource($item);
    }

    /**
     * Obtener una meta global específica
     */
    public function show(Meta $metum)
    {
        return new MetaResource(
            $metum->load(['temporalidad' => fn($q) => $q->withTrashed()])
        );
    }

    /**
     * Actualizar una meta global
     *
     * @bodyParam nombre string Nombre descriptivo de la meta.
     * @bodyParam tipo_entidad string Tipo de entidad: atencion, cotizacion, orden_compra
     * @bodyParam estatus_objetivo string Slug del estado que marca el hito. Ejemplo: aprobado
     * @bodyParam es_monetario boolean Si true, suma montos; si false, cuenta registros.
     * @bodyParam valor_objetivo number Meta numérica o monetaria a alcanzar.
     * @bodyParam id_temporalidad int ID de la temporalidad.
     */
    public function update(Request $request, Meta $metum)
    {
        $data = $request->validate([
            'nombre' => [
                'sometimes', 'required', 'string', 'max:150',
                Rule::unique('metas', 'nombre')->ignore($metum->id)->whereNull('deleted_at'),
            ],
            'tipo_entidad' => ['sometimes', 'required', Rule::in(['atencion', 'cotizacion', 'orden_compra'])],
            'estatus_objetivo' => ['sometimes', 'required', 'string', 'max:50'],
            'es_monetario' => ['sometimes', 'required', 'boolean'],
            'valor_objetivo' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'id_temporalidad' => ['sometimes', 'required', Rule::exists('temporalidades', 'id')->whereNull('deleted_at')],
        ]);

        // Usamos los valores enviados o los heredados del modelo para las validaciones cruzadas.
        $tipoEntidad  = $data['tipo_entidad']  ?? $metum->tipo_entidad;
        $esMon        = $data['es_monetario']  ?? $metum->es_monetario;
        $slug         = $data['estatus_objetivo'] ?? null;

        // Regla de negocio: las atenciones no tienen valor monetario.
        if ($tipoEntidad === 'atencion' && $esMon) {
            return response()->json([
                'message' => 'Las metas de tipo "atencion" no pueden ser monetarias.',
                'errors'  => ['es_monetario' => ['Las atenciones solo se pueden contar, no sumar montos.']],
            ], 422);
        }

        // Valida el slug solo si fue enviado en el payload.
        if ($slug && !$this->slugExiste($tipoEntidad, $slug)) {
            return response()->json([
                'message' => 'El slug de estado no es válido para el tipo de entidad indicado.',
                'errors'  => ['estatus_objetivo' => ["El slug \"{$slug}\" no existe en los estados de {$tipoEntidad}."]],
            ], 422);
        }

        $metum->update($data);
        $metum->load(['temporalidad' => fn($q) => $q->withTrashed()]);

        return new MetaResource($metum);
    }

    /**
     * Eliminar una meta global
     */
    public function destroy(Meta $metum)
    {
        $metum->delete();

        return response()->json(['data' => ['message' => 'Eliminado correctamente']]);
    }

    // ──────────────────────────────────────────────────────
    // Helpers privados
    // ──────────────────────────────────────────────────────

    /**
     * Verifica que el slug exista en la tabla de estados del dominio correspondiente.
     */
    private function slugExiste(string $tipoEntidad, string $slug): bool
    {
        $tabla = match ($tipoEntidad) {
            'atencion'    => 'estados_atenciones',
            'cotizacion'  => 'estados_cotizaciones',
            'orden_compra' => 'estados_ordenes_compra',
            default        => null,
        };

        return $tabla ? DB::table($tabla)->where('slug', $slug)->exists() : false;
    }
}
