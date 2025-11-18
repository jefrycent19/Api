<?php

require_once "utilidades.php";

configurarCors();
$metodo = $_SERVER["REQUEST_METHOD"];

// Función para descifrar datos AES-256-CBC
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

try {

    $cn = obtenerConexion();

    // ---------------------------
    // VALIDAR CÉDULA DESDE HEADER
    // ---------------------------
    $cedulaProgramador = $_SERVER['HTTP_X_CEDULA_PROGRAMADOR'] ?? null;

    if (!$cedulaProgramador) {
        responder(false, "Debe enviar X-Cedula-Programador en el encabezado.", null, 403);
    }

    // --------------------------------------
    // OBTENER CLAVE AES DESDE LA TABLA usuario
    // --------------------------------------
    $sql = "SELECT clave_encriptacion FROM usuario WHERE cedula_programador = ?";
    $st = $cn->prepare($sql);
    $st->execute([$cedulaProgramador]);
    $row = $st->fetch();

    if (!$row) {
        responder(false, "Cédula de programador no autorizada.", null, 403);
    }

    $claveEncriptacion = $row["clave_encriptacion"];


    // -------------------------
    // MANEJO DE MÉTODOS HTTP
    // -------------------------

    switch ($metodo) {

        // ======================
        // GET → NO requiere cifrado
        // ======================
        case "GET":

            if (isset($_GET["cedula"])) {
                $sql = "SELECT * FROM clientes WHERE cedula = ?";
                $st  = $cn->prepare($sql);
                $st->execute([$_GET["cedula"]]);
                $cli = $st->fetch();

                if (!$cli) {
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


        // ======================
        // POST → REQUIERE CIFRADO
        // ======================
        case "POST":

            $jsonCifrado = leerJson();

            if (empty($jsonCifrado["encrypted"])) {
                responder(false, "Datos cifrados no recibidos.", null, 400);
            }

            $json = descifrarDatos($jsonCifrado["encrypted"], $claveEncriptacion);

            if (empty($json["cedula"]) || empty($json["nombre"])) {
                responder(false, "La cédula y el nombre son obligatorios.", null, 400);
            }

            // Validar cédula única
            $st = $cn->prepare("SELECT 1 FROM clientes WHERE cedula = ?");
            $st->execute([$json["cedula"]]);
            if ($st->fetch()) {
                responder(false, "La cédula ya existe.", null, 409);
            }

            $sql = "INSERT INTO clientes (cedula, nombre, correo, telefono, direccion)
                    VALUES (?, ?, ?, ?, ?)";

            $st = $cn->prepare($sql);
            $st->execute([
                $json["cedula"],
                $json["nombre"],
                $json["correo"] ?? null,
                $json["telefono"] ?? null,
                $json["direccion"] ?? null
            ]);

            responder(true, "Cliente agregado correctamente.", ["cedula" => $json["cedula"]], 201);
            break;


        // ======================
        // PUT → REQUIERE CIFRADO
        // ======================
        case "PUT":

            parse_str($_SERVER["QUERY_STRING"] ?? "", $query);

            if (empty($query["cedula"])) {
                responder(false, "Debe enviar la cédula en la URL (?cedula=).", null, 400);
            }

            $jsonCifrado = leerJson();

            if (empty($jsonCifrado["encrypted"])) {
                responder(false, "Datos cifrados no recibidos.", null, 400);
            }

            $json = descifrarDatos($jsonCifrado["encrypted"], $claveEncriptacion);

            if (empty($json["nombre"])) {
                responder(false, "El nombre es obligatorio.", null, 400);
            }

            $sql = "UPDATE clientes
                    SET nombre = ?, correo = ?, telefono = ?, direccion = ?
                    WHERE cedula = ?";

            $st = $cn->prepare($sql);
            $st->execute([
                $json["nombre"],
                $json["correo"] ?? null,
                $json["telefono"] ?? null,
                $json["direccion"] ?? null,
                $query["cedula"]
            ]);

            if ($st->rowCount() === 0) {
                responder(false, "No existe el cliente a actualizar.", null, 404);
            }

            responder(true, "Cliente actualizado correctamente.", ["cedula" => $query["cedula"]], 200);
            break;


        // ======================
        // DELETE → NO requiere cifrado
        // ======================
        case "DELETE":

            parse_str($_SERVER["QUERY_STRING"] ?? "", $query);

            if (empty($query["cedula"])) {
                responder(false, "Debe enviar la cédula en la URL.", null, 400);
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
                responder(false, "No se puede eliminar el cliente, puede tener ventas asociadas.", null, 409);
            }
            break;


        default:
            responder(false, "Método HTTP no permitido.", null, 405);
    }

} catch (Exception $ex) {
    error_log($ex->getMessage());
    responder(false, "Error en el servidor: " . $ex->getMessage(), null, 500);
}
?>

