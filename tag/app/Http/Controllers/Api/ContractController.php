<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ContractController extends Controller
{
    /**
     * Sirve el archivo openapi.yaml solo si el secreto interno es correcto.
     *
     * @param  Request  $request
     * @return BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function download(Request $request)
    {
        $secret = env('INTERNAL_CONTRACT_SECRET');
        $headerSecret = $request->header('X-Internal-Secret');

        if (!$secret || $headerSecret !== $secret) {
            return response()->json([
                'message' => 'No autorizado. Se requiere un secreto interno válido.',
            ], 403);
        }

        $path = storage_path('app/private/scribe/openapi.yaml');

        if (!File::exists($path)) {
            return response()->json([
                'message' => 'El contrato aún no ha sido generado. Ejecute php artisan app:build-docs.',
            ], 404);
        }

        return response()->file($path, [
            'Content-Type' => 'text/yaml',
        ]);
    }
}
