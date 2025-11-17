<?php
// utilidades.php
// Funciones auxiliares para todos los endpoints del API

require_once "config.php";

/**
 * Configura CORS para permitir que otros proyectos consuman el API.
 */
function configurarCors() {
    // Permitir orígenes dinámicos cuando sea necesario para soportar credenciales.
    // Si la petición incluye Origin, lo devolvemos; si no, usamos '*'.
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';

    // Nota: si se utiliza Access-Control-Allow-Credentials: true, no debe usarse '*'.
    header("Access-Control-Allow-Origin: " . $origin);
    header("Access-Control-Allow-Headers: Content-Type, Authorization, Accept");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Credentials: true");

    if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
        http_response_code(200);
        exit;
    }
}

/**
 * Lee el cuerpo del request como JSON y lo devuelve como arreglo.
 */
function leerJson() {
    $cuerpo = file_get_contents("php://input");
    $cuerpo = trim($cuerpo);

    if ($cuerpo === "") {
        return [];
    }

    $datos = json_decode($cuerpo, true);

    if ($datos === null && json_last_error() !== JSON_ERROR_NONE) {
        responder(false, "El cuerpo de la petición no es un JSON válido.", null, 400);
    }

    return $datos;
}

/**
 * Envía una respuesta estándar en formato JSON y termina la ejecución.
 */
function responder($exito, $mensaje, $datos = null, $codigo = 200) {
    http_response_code($codigo);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode([
        "exito"   => $exito,
        "mensaje" => $mensaje,
        "datos"   => $datos
    ]);
    exit;
}
?>