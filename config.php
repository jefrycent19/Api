<?php
header("Content-Type: application/json; charset=utf-8");

$DB_HOST = getenv("DB_HOST");
$DB_USER = getenv("DB_USER");
$DB_PASS = getenv("DB_PASS");
$DB_NAME = getenv("DB_NAME");
$DB_PORT = getenv("DB_PORT");

try {
    $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8;port={$DB_PORT}";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (Exception $e) {
    echo json_encode([
        "exito" => false,
        "mensaje" => "Error al conectar: " . $e->getMessage(),
        "datos" => null
    ]);
    exit;
}

function obtenerConexion() {
    global $pdo;
    return $pdo;
}
