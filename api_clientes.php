<?php
// api_clientes.php
// CRUD para la tabla clientes

require_once "utilidades.php";

configurarCors();
$metodo = $_SERVER["REQUEST_METHOD"];

try {
    $cn = obtenerConexion();

    switch ($metodo) {

        // LISTAR / BUSCAR
        case "GET":
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

        // INSERTAR
        case "POST":
            $json = leerJson();

            if (empty($json["cedula"]) || empty($json["nombre"])) {
                responder(false, "La cédula y el nombre del cliente son obligatorios.");
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

        // EDITAR
        case "PUT":
            parse_str($_SERVER["QUERY_STRING"] ?? "", $query);
            if (empty($query["cedula"])) {
                responder(false, "Debe indicar la cédula en la URL (?cedula=).", null, 400);
            }

            $json = leerJson();
            if (empty($json["nombre"])) {
                responder(false, "El nombre del cliente es obligatorio.");
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

        // ELIMINAR
        case "DELETE":
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
            responder(false, "Método HTTP no permitido en este endpoint.");
    }

} catch (Exception $ex) {
    error_log($ex->getMessage());
    responder(false, "Error en el servidor.", null, 500);
}
?>