<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Accept");

$file = __DIR__ . "/servicios.json";
$BASE_URL = "https://web-production-b5c7d.up.railway.app";
$IMAGES_PATH = $BASE_URL . "/imagenes/";

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Cargar JSON
function cargarServicios($file) {
    if (!file_exists($file)) {
        file_put_contents($file, "[]");
    }
    return json_decode(file_get_contents($file), true);
}

// Guardar JSON
function guardarServicios($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Extraer solo el nombre del archivo de una URL
function extraerNombreImagen($urlImagen) {
    if (empty($urlImagen)) {
        return "default.png";
    }
    
    // Si no contiene /, es solo el nombre
    if (strpos($urlImagen, '/') === false) {
        return $urlImagen;
    }
    
    // Extraer el nombre después del último /
    $partes = explode('/', $urlImagen);
    return end($partes);
}

// Construir URL completa de imagen
function construirUrlImagen($nombreImagen, $IMAGES_PATH) {
    if (empty($nombreImagen)) {
        return $IMAGES_PATH . "default.png";
    }
    
    $nombreImagen = trim($nombreImagen);
    
    // Si ya es una URL completa, devolverla
    if (strpos($nombreImagen, 'http://') === 0 || strpos($nombreImagen, 'https://') === 0) {
        return $nombreImagen;
    }
    
    // Construir URL completa
    return $IMAGES_PATH . $nombreImagen;
}

$method = $_SERVER['REQUEST_METHOD'];
$servicios = cargarServicios($file);

switch ($method) {
    case "GET":
        // Construir URLs completas para cada servicio antes de enviar
        $serviciosConUrls = array_map(function($servicio) use ($IMAGES_PATH) {
            if (isset($servicio['imagen'])) {
                $servicio['imagen'] = construirUrlImagen($servicio['imagen'], $IMAGES_PATH);
            }
            return $servicio;
        }, $servicios);
        
        echo json_encode($serviciosConUrls);
        break;
        
    case "POST":
        $input = json_decode(file_get_contents("php://input"), true);
        
        // Extraer solo el nombre del archivo y guardarlo
        if (isset($input['imagen'])) {
            $input['imagen'] = extraerNombreImagen($input['imagen']);
        } else {
            $input['imagen'] = "default.png";
        }
        
        $input["id"] = count($servicios) > 0 ? max(array_column($servicios, 'id')) + 1 : 1;
        $servicios[] = $input;
        guardarServicios($file, $servicios);
        
        http_response_code(201);
        echo json_encode(["status" => "creado", "id" => $input["id"]]);
        break;
        
    case "PUT":
        $input = json_decode(file_get_contents("php://input"), true);
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(["error" => "ID no proporcionado"]);
            exit;
        }
        
        // Extraer solo el nombre del archivo
        if (isset($input['imagen'])) {
            $input['imagen'] = extraerNombreImagen($input['imagen']);
        }
        
        $encontrado = false;
        foreach ($servicios as &$s) {
            if ($s["id"] == $id) {
                $s = array_merge($s, $input);
                $encontrado = true;
                break;
            }
        }
        
        if ($encontrado) {
            guardarServicios($file, $servicios);
            http_response_code(200);
            echo json_encode(["status" => "actualizado"]);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "ID no encontrado"]);
        }
        break;
        
    case "DELETE":
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(["error" => "ID no proporcionado"]);
            exit;
        }
        
        $cantidadOriginal = count($servicios);
        $servicios = array_filter($servicios, fn($s) => $s["id"] != $id);
        
        if (count($servicios) < $cantidadOriginal) {
            guardarServicios($file, array_values($servicios));
            http_response_code(200);
            echo json_encode(["status" => "eliminado"]);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "ID no encontrado"]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["error" => "Método no permitido"]);
}
