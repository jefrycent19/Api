<?php
// config.php
// Configuración de conexión a la base de datos del mini súper

$servidor   = "caboose.proxy.rlwy.net"; 
$usuario    = "root"; 
$contrasena = "PzRWvcHsVHhCXyLsRJhjdygSBwgwtEmG"; 
$baseDatos  = "railway"; 
$puerto     = 50284;              // Cambiar en el hosting

/**
 * Crea y devuelve una conexión PDO.
 */
function obtenerConexion() {
    global $servidor, $baseDatos, $usuario, $contrasena;

    $dsn = "mysql:host={$servidor};dbname={$baseDatos};charset=utf8";

    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];

    return new PDO($dsn, $usuario, $contrasena, $opciones);
}
?>