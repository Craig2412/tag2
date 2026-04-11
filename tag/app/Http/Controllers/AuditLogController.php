<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Listar registros de auditoría
     *
     * Devuelve los registros de auditoría del sistema con filtros opcionales y paginación.
     *
     * @queryParam id_usuario int Filtrar por ID de usuario. Ejemplo: 1
     * @queryParam action string Filtrar por tipo de acción (ej. login, create, update). Ejemplo: login
     * @queryParam table_name string Filtrar por nombre de tabla. Ejemplo: empresas
     * @queryParam record_id int Filtrar por ID de registro afectado. Ejemplo: 1
     * @queryParam desde date Fecha de inicio del filtro. Ejemplo: 2026-04-01
     * @queryParam hasta date Fecha de fin del filtro. Ejemplo: 2026-04-30
     * @queryParam per_page int Cantidad de resultados por página. Ejemplo: 50
     */
    public function index(Request $request)
    {
        $data = $this->validatedFilters($request, true);
        $query = $this->applyFilters($data);

        $perPage = $data['per_page'] ?? 50;

        return response()->json($query->paginate($perPage));
    }

    /**
     * Exportar registros de auditoría a CSV
     *
     * Descarga un archivo CSV con los registros de auditoría filtrados. Soporta modo completo o resumen.
     *
     * @queryParam id_usuario int Filtrar por ID de usuario.
     * @queryParam action string Filtrar por tipo de acción.
     * @queryParam table_name string Filtrar por nombre de tabla.
     * @queryParam record_id int Filtrar por ID de registro.
     * @queryParam desde date Fecha de inicio del filtro.
     * @queryParam hasta date Fecha de fin del filtro.
     * @queryParam modo string Modo de exportación (completo, resumen). Ejemplo: completo
     */
    public function exportCsv(Request $request)
    {
        $data = $this->validatedFilters($request, false, true);
        $logs = $this->applyFilters($data)->get();
        $modo = $data['modo'] ?? 'completo';

        $filename = 'audit-logs-' . $modo . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($logs, $modo): void {
            $output = fopen('php://output', 'w');

            if ($modo === 'resumen') {
                fputcsv($output, [
                    'id',
                    'fecha_hora',
                    'usuario_id',
                    'user_role',
                    'action',
                    'table_name',
                    'record_id',
                    'route',
                    'http_method',
                    'status_code',
                    'success',
                ]);

                foreach ($logs as $log) {
                    fputcsv($output, [
                        $log->id,
                        $log->created_at,
                        $log->usuario_id,
                        $log->user_role,
                        $log->action,
                        $log->table_name,
                        $log->record_id,
                        $log->route,
                        $log->http_method,
                        $log->status_code,
                        $log->success ? 'true' : 'false',
                    ]);
                }
            } else {
                fputcsv($output, [
                    'id',
                    'fecha_hora',
                    'usuario_id',
                    'user_role',
                    'action',
                    'table_name',
                    'record_id',
                    'ip_address',
                    'route',
                    'http_method',
                    'status_code',
                    'success',
                    'message',
                    'before_data',
                    'after_data',
                ]);

                foreach ($logs as $log) {
                    fputcsv($output, [
                        $log->id,
                        $log->created_at,
                        $log->usuario_id,
                        $log->user_role,
                        $log->action,
                        $log->table_name,
                        $log->record_id,
                        $log->ip_address,
                        $log->route,
                        $log->http_method,
                        $log->status_code,
                        $log->success ? 'true' : 'false',
                        $log->message,
                        json_encode($log->before_data, JSON_UNESCAPED_UNICODE),
                        json_encode($log->after_data, JSON_UNESCAPED_UNICODE),
                    ]);
                }
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function validatedFilters(Request $request, bool $withPerPage, bool $withMode = false): array
    {
        $rules = [
            'id_usuario' => ['sometimes', 'integer', 'exists:usuarios,id'],
            'action' => ['sometimes', 'string', 'max:50'],
            'table_name' => ['sometimes', 'string', 'max:255'],
            'record_id' => ['sometimes', 'integer'],
            'desde' => ['sometimes', 'date'],
            'hasta' => ['sometimes', 'date'],
        ];

        if ($withPerPage) {
            $rules['per_page'] = ['sometimes', 'integer', 'min:1', 'max:200'];
        }

        if ($withMode) {
            $rules['modo'] = ['sometimes', 'in:completo,resumen'];
        }

        return $request->validate($rules);
    }

    private function applyFilters(array $data)
    {
        $query = AuditLog::query()->orderByDesc('id');

        if (isset($data['id_usuario'])) {
            $query->where('usuario_id', $data['id_usuario']);
        }

        if (isset($data['action'])) {
            $query->where('action', $data['action']);
        }

        if (isset($data['table_name'])) {
            $query->where('table_name', $data['table_name']);
        }

        if (isset($data['record_id'])) {
            $query->where('record_id', $data['record_id']);
        }

        if (isset($data['desde'])) {
            $query->whereDate('created_at', '>=', $data['desde']);
        }

        if (isset($data['hasta'])) {
            $query->whereDate('created_at', '<=', $data['hasta']);
        }

        return $query;
    }
}
