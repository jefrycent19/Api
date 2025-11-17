<?php
// api_detalle_ventas.php
// CRUD para la tabla detalle_ventas

require_once "utilidades.php";

configurarCors();
$metodo = $_SERVER["REQUEST_METHOD"];

try {
    $cn = obtenerConexion();

    switch ($metodo) {

        // LISTAR / BUSCAR
        case "GET":
            // Se puede filtrar por id o por id_venta
            if (isset($_GET["id"])) {
                $sql = "SELECT d.*, v.fecha_venta, p.nombre AS nombre_producto
                        FROM detalle_ventas d
                        INNER JOIN ventas v ON d.id_venta = v.id
                        INNER JOIN productos p ON d.id_producto = p.id
                        WHERE d.id = ?";
                $st  = $cn->prepare($sql);
                $st->execute([$_GET["id"]]);
                $fila = $st->fetch();
                if ($fila === false) {
                    responder(false, "Detalle de venta no encontrado.", null, 404);
                }
                responder(true, "Detalle de venta encontrado.", $fila, 200);
            } elseif (isset($_GET["id_venta"])) {
                $sql = "SELECT d.*, v.fecha_venta, p.nombre AS nombre_producto
                        FROM detalle_ventas d
                        INNER JOIN ventas v ON d.id_venta = v.id
                        INNER JOIN productos p ON d.id_producto = p.id
                        WHERE d.id_venta = ?";
                $st  = $cn->prepare($sql);
                $st->execute([$_GET["id_venta"]]);
                $lista = $st->fetchAll();
                responder(true, "Detalles de la venta.", $lista, 200);
            } else {
                $sql = "SELECT d.*, v.fecha_venta, p.nombre AS nombre_producto
                        FROM detalle_ventas d
                        INNER JOIN ventas v ON d.id_venta = v.id
                        INNER JOIN productos p ON d.id_producto = p.id";
                $st  = $cn->query($sql);
                $lista = $st->fetchAll();
                responder(true, "Lista de detalles de ventas.", $lista, 200);
            }
            break;

        // INSERTAR
        case "POST":
            $json = leerJson();

            if (empty($json["id_venta"]) || empty($json["id_producto"]) ||
                empty($json["cantidad"]) || !isset($json["precio_unitario"])) {
                responder(false, "id_venta, id_producto, cantidad y precio_unitario son obligatorios.", null, 400);
            }

            $sql = "INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio_unitario)
                    VALUES (?, ?, ?, ?)";
            $st  = $cn->prepare($sql);
            $st->execute([
                $json["id_venta"],
                $json["id_producto"],
                $json["cantidad"],
                $json["precio_unitario"]
            ]);

            $idGenerado = $cn->lastInsertId();

            responder(true, "Detalle de venta registrado correctamente.", ["id" => $idGenerado], 201);
            break;

        // EDITAR
        case "PUT":
            parse_str($_SERVER["QUERY_STRING"] ?? "", $query);
            if (empty($query["id"])) {
                responder(false, "Debe indicar el id del detalle en la URL (?id=).", null, 400);
            }

            $json = leerJson();

            if (empty($json["id_venta"]) || empty($json["id_producto"]) ||
                empty($json["cantidad"]) || !isset($json["precio_unitario"])) {
                responder(false, "id_venta, id_producto, cantidad y precio_unitario son obligatorios.", null, 400);
            }

            $sql = "UPDATE detalle_ventas
                    SET id_venta = ?, id_producto = ?, cantidad = ?, precio_unitario = ?
                    WHERE id = ?";
            $st  = $cn->prepare($sql);
            $st->execute([
                $json["id_venta"],
                $json["id_producto"],
                $json["cantidad"],
                $json["precio_unitario"],
                $query["id"]
            ]);

            if ($st->rowCount() === 0) {
                responder(false, "No se encontró el detalle para actualizar.", null, 404);
            }

            responder(true, "Detalle de venta actualizado correctamente.", ["id" => $query["id"]], 200);
            break;

        // ELIMINAR
        case "DELETE":
            parse_str($_SERVER["QUERY_STRING"] ?? "", $query);
            if (empty($query["id"])) {
                responder(false, "Debe indicar el id del detalle en la URL (?id=).", null, 400);
            }

            $sql = "DELETE FROM detalle_ventas WHERE id = ?";
            $st  = $cn->prepare($sql);
            $st->execute([$query["id"]]);

            if ($st->rowCount() === 0) {
                responder(false, "No existe el detalle indicado.", null, 404);
            }

            responder(true, "Detalle de venta eliminado correctamente.", null, 200);
            break;

        default:
            responder(false, "Método HTTP no permitido en este endpoint.");
    }

} catch (Exception $ex) {
    error_log($ex->getMessage());
    responder(false, "Error en el servidor.", null, 500);
}
?>