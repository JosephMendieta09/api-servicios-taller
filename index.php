
<?php
// index.php - Punto de entrada principal
// Redirige todo a la API

// Obtener la ruta solicitada
$request_uri = $_SERVER['REQUEST_URI'];

// Si se solicita la raíz, mostrar mensaje de bienvenida
if ($request_uri === '/' || $request_uri === '/index.php') {
    header('Content-Type: application/json');
    echo json_encode([
        'message' => '🚀 API de Servicios de Taller Mecánico',
        'status' => 'online',
        'version' => '1.0',
        'endpoints' => [
            'GET /api.php' => 'Listar todos los servicios',
            'GET /api.php?id=1' => 'Obtener servicio por ID',
            'POST /api.php' => 'Crear nuevo servicio',
            'PUT /api.php?id=1' => 'Actualizar servicio',
            'DELETE /api.php?id=1' => 'Eliminar servicio'
        ],
        'documentation' => 'https://github.com/tu-usuario/api-servicios-taller'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Si se solicita la API, incluirla
if (strpos($request_uri, '/api.php') !== false || strpos($request_uri, 'api.php') !== false) {
    include 'api.php';
    exit;
}

// Para cualquier otra ruta, mostrar error 404
http_response_code(404);
header('Content-Type: application/json');
echo json_encode([
    'error' => 'Endpoint no encontrado',
    'requested' => $request_uri,
    'available_endpoints' => ['/api.php']
], JSON_PRETTY_PRINT);
?>
