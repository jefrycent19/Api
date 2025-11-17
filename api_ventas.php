<?php
// api_ventas.php
// CRUD para la tabla ventas

require_once "utilidades.php";

configurarCors();
$metodo = $_SERVER["REQUEST_METHOD"];

try {
    $cn = obtenerConexion();

    switch ($metodo) {

        // LISTAR / BUSCAR
        case "GET":
            if (isset($_GET["id"])) {
                $sql = "SELECT v.*, c.nombre AS nombre_cliente
                        FROM ventas v
                        INNER JOIN clientes c ON v.id_cliente = c.cedula
                        WHERE v.id = ?";
                $st  = $cn->prepare($sql);
                $st->execute([$_GET["id"]]);
                $venta = $st->fetch();
                if ($venta === false) {
                    responder(false, "Venta no encontrada.", null, 404);
                }
                responder(true, "Venta encontrada.", $venta, 200);
            } else {
                $sql = "SELECT v.*, c.nombre AS nombre_cliente
                        FROM ventas v
                        INNER JOIN clientes c ON v.id_cliente = c.cedula";
                $st  = $cn->query($sql);
                $lista = $st->fetchAll();
                responder(true, "Lista de ventas.", $lista, 200);
            }
            break;

        // INSERTAR
        case "POST":
            $json = leerJson();

            if (empty($json["id_cliente"]) || empty($json["fecha_venta"])) {
                responder(false, "id_cliente y fecha_venta son obligatorios.", null, 400);
            }

            if (empty($json["tipo_venta"])) {
                $json["tipo_venta"] = "contado";
            }

            $sql = "INSERT INTO ventas (id_cliente, fecha_venta, tipo_venta, metodo_pago, total)
                    VALUES (?, ?, ?, ?, ?)";
            $st  = $cn->prepare($sql);
            $st->execute([
                $json["id_cliente"],
                $json["fecha_venta"],
                $json["tipo_venta"],
                $json["metodo_pago"] ?? null,
                $json["total"] ?? null
            ]);

            $idGenerado = $cn->lastInsertId();

            responder(true, "Venta registrada correctamente.", ["id" => $idGenerado], 201);
            break;

        // EDITAR
        case "PUT":
            parse_str($_SERVER["QUERY_STRING"] ?? "", $query);
            if (empty($query["id"])) {
                responder(false, "Debe indicar el id de la venta en la URL (?id=).", null, 400);
            }

            $json = leerJson();

            if (empty($json["id_cliente"]) || empty($json["fecha_venta"])) {
                responder(false, "id_cliente y fecha_venta son obligatorios.", null, 400);
            }

            if (empty($json["tipo_venta"])) {
                $json["tipo_venta"] = "contado";
            }

            $sql = "UPDATE ventas
                    SET id_cliente = ?, fecha_venta = ?, tipo_venta = ?, metodo_pago = ?, total = ?
                    WHERE id = ?";
            $st  = $cn->prepare($sql);
            $st->execute([
                $json["id_cliente"],
                $json["fecha_venta"],
                $json["tipo_venta"],
                $json["metodo_pago"] ?? null,
                $json["total"] ?? null,
                $query["id"]
            ]);

            if ($st->rowCount() === 0) {
                responder(false, "No se encontró la venta para actualizar.", null, 404);
            }

            responder(true, "Venta actualizada correctamente.", ["id" => $query["id"]], 200);
            break;

        // ELIMINAR
        case "DELETE":
            parse_str($_SERVER["QUERY_STRING"] ?? "", $query);
            if (empty($query["id"])) {
                responder(false, "Debe indicar el id de la venta en la URL (?id=).", null, 400);
            }

            try {
                $sql = "DELETE FROM ventas WHERE id = ?";
                $st  = $cn->prepare($sql);
                $st->execute([$query["id"]]);

                if ($st->rowCount() === 0) {
                    responder(false, "No existe la venta indicada.", null, 404);
                }

                responder(true, "Venta eliminada correctamente.", null, 200);
            } catch (Exception $e) {
                responder(false, "No se puede eliminar la venta, posiblemente tiene detalles asociados.", null, 409);
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