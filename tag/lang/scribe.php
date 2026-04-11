<?php

return [
    'labels' => [
        'search' => 'Buscar',
        'base_url' => 'URL Base',
    ],

    'auth' => [
        'none' => 'Esta API no requiere autenticación.',
        'instruction' => [
            'query' => <<<'TEXT'
                Para autenticar las solicitudes, incluya un parámetro de consulta **`:parameterName`** en la petición.
                TEXT,
            'body' => <<<'TEXT'
                Para autenticar las solicitudes, incluya un parámetro **`:parameterName`** en el cuerpo de la petición.
                TEXT,
            'query_or_body' => <<<'TEXT'
                Para autenticar las solicitudes, incluya un parámetro **`:parameterName`** ya sea en la cadena de consulta o en el cuerpo de la petición.
                TEXT,
            'bearer' => <<<'TEXT'
                Para autenticar las solicitudes, incluya un encabezado **`Authorization`** con el valor **`"Bearer :placeholder"`**.
                TEXT,
            'basic' => <<<'TEXT'
                Para autenticar las solicitudes, incluya un encabezado **`Authorization`** con el formato **`"Basic {credenciales}"`**.
                El valor de `{credenciales}` debe ser su nombre de usuario/id y su contraseña, unidos con dos puntos (:),
                y luego codificados en base64.
                TEXT,
            'header' => <<<'TEXT'
                Para autenticar las solicitudes, incluya un encabezado **`:parameterName`** con el valor **`":placeholder"`**.
                TEXT,
        ],
        'details' => <<<'TEXT'
            Todos los endpoints que requieren autenticación están marcados con el distintivo `requiere autenticación` en la documentación.
            TEXT,
    ],

    'headings' => [
        'introduction' => 'Introducción',
        'auth' => 'Autenticación de solicitudes',
    ],

    'endpoint' => [
        'request' => 'Petición',
        'headers' => 'Encabezados',
        'url_parameters' => 'Parámetros de URL',
        'body_parameters' => 'Parámetros del cuerpo',
        'query_parameters' => 'Parámetros de consulta',
        'response' => 'Respuesta',
        'response_fields' => 'Campos de respuesta',
        'example_request' => 'Ejemplo de petición',
        'example_response' => 'Ejemplo de respuesta',
        'responses' => [
            'binary' => 'Datos binarios',
            'empty' => 'Respuesta vacía',
        ],
    ],

    'try_it_out' => [
        'open' => 'Probar ⚡',
        'cancel' => 'Cancelar 🛑',
        'send' => 'Enviar solicitud 💥',
        'loading' => '⏱ Enviando...',
        'received_response' => 'Respuesta recibida',
        'request_failed' => 'La solicitud falló con error',
        'error_help' => <<<'TEXT'
            Consejo: Verifique que esté correctamente conectado a la red.
            Si usted es el administrador de esta API, asegúrese de que esté en ejecución y que haya habilitado CORS.
            Puede revisar la consola de DevTools para obtener información de depuración.
            TEXT,
    ],

    'links' => [
        'postman' => 'Ver colección de Postman',
        'openapi' => 'Ver especificación OpenAPI',
    ],
];
