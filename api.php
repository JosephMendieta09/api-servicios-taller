<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$baseUrl = "https://smayckel.xo.je";
$defaultImage = $baseUrl . "/imagenes/default.png";

$file = "servicios.json";

if (!file_exists($file)) {
    http_response_code(500);
    echo json_encode(["error" => "Archivo de datos no encontrado"]);
    exit;
}

$jsonContent = file_get_contents($file);
$data = json_decode($jsonContent, true);

if ($data === null) {
    http_response_code(500);
    echo json_encode(["error" => "Error al leer datos"]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

switch ($method) {
    case 'GET':
        if ($id === null) {
            // Listar todos los servicios
            echo json_encode([
                "success" => true,
                "count" => count($data),
                "data" => $data
            ]);
        } else {
            // Buscar servicio específico
            $found = false;
            foreach ($data as $srv) {
                if ($srv['id'] == $id) {
                    echo json_encode([
                        "success" => true,
                        "data" => $srv
                    ]);
                    $found = true;
                    exit;
                }
            }
            if (!$found) {
                http_response_code(404);
                echo json_encode([
                    "success" => false,
                    "error" => "Servicio no encontrado"
                ]);
            }
        }
        break;

    case 'POST':
        // Crear nuevo servicio
        $input = json_decode(file_get_contents("php://input"), true);
        
        // Validar datos
        if (!$input || !isset($input['nombre']) || !isset($input['precio'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "Datos inválidos. Se requiere 'nombre' y 'precio'"
            ]);
            exit;
        }

        // Generar nuevo ID
        $lastId = 0;
        foreach ($data as $srv) {
            if ($srv['id'] > $lastId) {
                $lastId = $srv['id'];
            }
        }
        $newId = $lastId + 1;

        // Crear nuevo servicio
        $new = [
            "id" => $newId,
            "nombre" => trim($input["nombre"]),
            "precio" => floatval($input["precio"]),
            "imagen" => isset($input["imagen"]) ? $input["imagen"] : $defaultImage
        ];

        $data[] = $new;
        
        // Guardar datos
        if (file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            http_response_code(201);
            echo json_encode([
                "success" => true,
                "mensaje" => "Servicio creado exitosamente",
                "data" => $new
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "error" => "No se pudo guardar el servicio"
            ]);
        }
        break;

    case 'PUT':
        // Actualizar servicio existente
        if ($id === null) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "Falta el parámetro 'id' en la URL"
            ]);
            exit;
        }

        $input = json_decode(file_get_contents("php://input"), true);
        $found = false;

        foreach ($data as &$srv) {
            if ($srv['id'] == $id) {
                // Actualizar solo los campos proporcionados
                if (isset($input['nombre'])) {
                    $srv['nombre'] = trim($input['nombre']);
                }
                if (isset($input['precio'])) {
                    $srv['precio'] = floatval($input['precio']);
                }
                if (isset($input['imagen'])) {
                    $srv['imagen'] = $input['imagen'];
                }
                
                $found = true;
                
                // Guardar cambios
                if (file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                    echo json_encode([
                        "success" => true,
                        "mensaje" => "Servicio actualizado exitosamente",
                        "data" => $srv
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode([
                        "success" => false,
                        "error" => "No se pudo guardar los cambios"
                    ]);
                }
                exit;
            }
        }

        if (!$found) {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "error" => "Servicio no encontrado"
            ]);
        }
        break;

    case 'DELETE':
        // Eliminar servicio
        if ($id === null) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "Falta el parámetro 'id' en la URL"
            ]);
            exit;
        }

        $found = false;
        $deletedService = null;

        foreach ($data as $key => $srv) {
            if ($srv['id'] == $id) {
                $deletedService = $srv;
                unset($data[$key]);
                $found = true;
                break;
            }
        }

        if ($found) {
            // Reindexar array y guardar
            $data = array_values($data);
            
            if (file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                echo json_encode([
                    "success" => true,
                    "mensaje" => "Servicio eliminado exitosamente",
                    "data" => $deletedService
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    "success" => false,
                    "error" => "No se pudo eliminar el servicio"
                ]);
            }
        } else {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "error" => "Servicio no encontrado"
            ]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode([
            "success" => false,
            "error" => "Método no permitido"
        ]);
        break;
}
