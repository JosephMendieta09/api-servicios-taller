<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

$file = __DIR__ . "/servicios.json";

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

$method = $_SERVER['REQUEST_METHOD'];
$servicios = cargarServicios($file);

switch ($method) {

    case "GET":
        echo json_encode($servicios);
        break;

    case "POST":
        $input = json_decode(file_get_contents("php://input"), true);
        $input["id"] = count($servicios) + 1;
        $servicios[] = $input;
        guardarServicios($file, $servicios);
        echo json_encode(["status" => "creado"]);
        break;

    case "PUT":
        $input = json_decode(file_get_contents("php://input"), true);
        $id = $input["id"] ?? null;

        foreach ($servicios as &$s) {
            if ($s["id"] == $id) {
                $s = array_merge($s, $input);
                guardarServicios($file, $servicios);
                echo json_encode(["status" => "actualizado"]);
                exit;
            }
        }
        echo json_encode(["error" => "id no encontrado"]);
        break;

    case "DELETE":
        $input = json_decode(file_get_contents("php://input"), true);
        $id = $input["id"] ?? null;

        $servicios = array_filter($servicios, fn($s) => $s["id"] != $id);
        guardarServicios($file, array_values($servicios));

        echo json_encode(["status" => "eliminado"]);
        break;

    default:
        echo json_encode(["error" => "Método no permitido"]);
}
