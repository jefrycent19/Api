<?php
$host = getenv("DB_HOST");
$user = getenv("DB_USER");
$pass = getenv("DB_PASS");
$db   = getenv("DB_NAME");

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode([
        "exito" => false,
        "mensaje" => "Error en la conexión: " . $conn->connect_error
    ]));
}

$conn->set_charset("utf8");
?>
