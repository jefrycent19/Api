<?php

require_once "utilidades.php";

configurarCors();
$metodo = $_SERVER["REQUEST_METHOD"];

// Función para descifrar datos (AES-256)
function descifrarDatos($encrypted, $clave) {
    $method = 'AES-256-CBC';
    $key = hash('sha256', $clave, true);
    
    // Decodificar base64
    $encrypted = base64_decode($encrypted);
    
    // Extraer IV (primeros 16 bytes)
    $iv = substr($encrypted, 0, 16);
    $ciphertext = substr($encrypted, 16);
    
    // Descifrar
    $decrypted = openssl_decrypt($ciphertext, $method, $key, OPENSSL_RAW_DATA, $iv);
    
    return json_decode($decrypted, true);
}

// Tabla de usuarios (simulada - en producción debe estar en BD)
// Relaciona cédula con clave de encriptación
$usuarios = [
    "208540987" => "MiCl4v3S3gur4_2024", // Cambiar por tus datos reales
    "123456789" => "OtraClaveSegura123"
];

try {
    $cn = obtenerConexion();
    
    // Obtener cédula del programador del header
    $cedulaProgramador = $_SERVER['HTTP_X_CEDULA_PROGRAMADOR'] ?? null;
    
    // Verificar que la cédula existe
    if (!$cedulaProgramador || !isset($usuarios[$cedulaProgramador])) {
        responder(false, "Cédula de programador no autorizada.", null, 403);
    }
    
    $claveEncriptacion = $usuarios[$cedulaProgramador];

    switch ($metodo) {

        case "GET":
            // GET no requiere descifrado
            if (isset($_GET["cedula"])) {
                $sql = "SELECT * FROM clientes WHERE cedula = ?";
                $st  = $cn->prepare($sql);
                $st->execute([$_GET["cedula"]]);
                $cli = $st->fetch();
                if ($cli === false) {
                    responder(false, "Cliente no encontrado.", null, 404);
                }
                responder(true, "Cliente encontrado.", $cli, 200);
            } else {
                $sql = "SELECT * FROM clientes";
                $st  = $cn->query($sql);
                $lista = $st->fetchAll();
                responder(true, "Lista de clientes.", $lista, 200);
            }
            break;

        case "POST":
            // Leer JSON cifrado
            $jsonCifrado = leerJson();
            
            if (empty($jsonCifrado["encrypted"])) {
                responder(false, "Datos cifrados no recibidos.", null, 400);
            }
            
            // Descifrar datos
            $json = descifrarDatos($jsonCifrado["encrypted"], $claveEncriptacion);
            
            if (empty($json["cedula"]) || empty($json["nombre"])) {
                responder(false, "La cédula y el nombre del cliente son obligatorios.", null, 400);
            }

            // Validar cédula única
            $st = $cn->prepare("SELECT 1 FROM clientes WHERE cedula = ?");
            $st->execute([$json["cedula"]]);
            if ($st->fetch()) {
                responder(false, "La cédula ya existe en el sistema.", null, 409);
            }

            $sql = "INSERT INTO clientes (cedula, nombre, correo, telefono, direccion)
                    VALUES (?, ?, ?, ?, ?)";
            $st  = $cn->prepare($sql);
            $st->execute([
                $json["cedula"],
                $json["nombre"],
                $json["correo"]   ?? null,
                $json["telefono"] ?? null,
                $json["direccion"] ?? null
            ]);

            responder(true, "Cliente agregado correctamente.", ["cedula" => $json["cedula"], "nombre" => $json["nombre"]], 201);
            break;

        case "PUT":
            parse_str($_SERVER["QUERY_STRING"] ?? "", $query);
            if (empty($query["cedula"])) {
                responder(false, "Debe indicar la cédula en la URL (?cedula=).", null, 400);
            }

            // Leer JSON cifrado
            $jsonCifrado = leerJson();
            
            if (empty($jsonCifrado["encrypted"])) {
                responder(false, "Datos cifrados no recibidos.", null, 400);
            }
            
            // Descifrar datos
            $json = descifrarDatos($jsonCifrado["encrypted"], $claveEncriptacion);
            
            if (empty($json["nombre"])) {
                responder(false, "El nombre del cliente es obligatorio.", null, 400);
            }

            $sql = "UPDATE clientes
                    SET nombre = ?, correo = ?, telefono = ?, direccion = ?
                    WHERE cedula = ?";
            $st  = $cn->prepare($sql);
            $st->execute([
                $json["nombre"],
                $json["correo"]   ?? null,
                $json["telefono"] ?? null,
                $json["direccion"] ?? null,
                $query["cedula"]
            ]);

            if ($st->rowCount() === 0) {
                responder(false, "No se encontró el cliente para actualizar.", null, 404);
            }

            responder(true, "Cliente actualizado correctamente.", ["cedula" => $query["cedula"], "nombre" => $json["nombre"]], 200);
            break;

        case "DELETE":
            // DELETE no requiere descifrado
            parse_str($_SERVER["QUERY_STRING"] ?? "", $query);
            if (empty($query["cedula"])) {
                responder(false, "Debe indicar la cédula en la URL (?cedula=).", null, 400);
            }

            try {
                $sql = "DELETE FROM clientes WHERE cedula = ?";
                $st  = $cn->prepare($sql);
                $st->execute([$query["cedula"]]);

                if ($st->rowCount() === 0) {
                    responder(false, "No existe el cliente indicado.", null, 404);
                }

                responder(true, "Cliente eliminado correctamente.", null, 200);
            } catch (Exception $e) {
                responder(false, "No se puede eliminar el cliente, posiblemente tiene ventas asociadas.", null, 409);
            }
            break;

        default:
            responder(false, "Método HTTP no permitido en este endpoint.", null, 405);
    }

} catch (Exception $ex) {
    error_log($ex->getMessage());
    responder(false, "Error en el servidor: " . $ex->getMessage(), null, 500);
}
?>