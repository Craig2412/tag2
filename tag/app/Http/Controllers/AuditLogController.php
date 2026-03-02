<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $data = $this->validatedFilters($request, true);
        $query = $this->applyFilters($data);

        $perPage = $data['per_page'] ?? 50;

        return response()->json($query->paginate($perPage));
    }

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
                    'user_id',
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
                        $log->user_id,
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
                    'user_id',
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
                        $log->user_id,
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
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
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

        if (isset($data['user_id'])) {
            $query->where('user_id', $data['user_id']);
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
