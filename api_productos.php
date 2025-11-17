<?php
// api_productos.php
// CRUD para la tabla productos

require_once "utilidades.php";

configurarCors();
$metodo = $_SERVER["REQUEST_METHOD"];

try {
    $cn = obtenerConexion();

    switch ($metodo) {

        // LISTAR / BUSCAR
        case "GET":
            if (isset($_GET["id"])) {
                $sql = "SELECT p.*, c.nombre AS nombre_categoria
                        FROM productos p
                        INNER JOIN categorias c ON p.id_categoria = c.id
                        WHERE p.id = ?";
                $st  = $cn->prepare($sql);
                $st->execute([$_GET["id"]]);
                $prod = $st->fetch();
                if ($prod === false) {
                    responder(false, "Producto no encontrado.", null, 404);
                }
                responder(true, "Producto encontrado.", $prod, 200);
            } else {
                $sql = "SELECT p.*, c.nombre AS nombre_categoria
                        FROM productos p
                        INNER JOIN categorias c ON p.id_categoria = c.id";
                $st  = $cn->query($sql);
                $lista = $st->fetchAll();
                responder(true, "Lista de productos.", $lista, 200);
            }
            break;

        // INSERTAR
        case "POST":
            $json = leerJson();

            if (empty($json["id_categoria"]) || empty($json["nombre"]) ||
                !isset($json["precio"]) || !isset($json["existencia"])) {
                responder(false, "id_categoria, nombre, precio y existencia son obligatorios.", null, 400);
            }

            $sql = "INSERT INTO productos (id_categoria, nombre, precio, existencia)
                    VALUES (?, ?, ?, ?)";
            $st  = $cn->prepare($sql);
            $st->execute([
                $json["id_categoria"],
                $json["nombre"],
                $json["precio"],
                $json["existencia"]
            ]);

            $id = $cn->lastInsertId();
            responder(true, "Producto agregado correctamente.", ["id" => $id, "nombre" => $json["nombre"]], 201);
            break;

        // EDITAR
        case "PUT":
            parse_str($_SERVER["QUERY_STRING"] ?? "", $query);
            if (empty($query["id"])) {
                responder(false, "Debe indicar el id del producto en la URL (?id=).", null, 400);
            }

            $json = leerJson();

            if (empty($json["id_categoria"]) || empty($json["nombre"]) ||
                !isset($json["precio"]) || !isset($json["existencia"])) {
                responder(false, "id_categoria, nombre, precio y existencia son obligatorios.", null, 400);
            }

            $sql = "UPDATE productos
                    SET id_categoria = ?, nombre = ?, precio = ?, existencia = ?
                    WHERE id = ?";
            $st  = $cn->prepare($sql);
            $st->execute([
                $json["id_categoria"],
                $json["nombre"],
                $json["precio"],
                $json["existencia"],
                $query["id"]
            ]);

            if ($st->rowCount() === 0) {
                responder(false, "No se encontró el producto para actualizar.", null, 404);
            }

            responder(true, "Producto actualizado correctamente.", ["id" => $query["id"], "nombre" => $json["nombre"]], 200);
            break;

        // ELIMINAR
        case "DELETE":
            parse_str($_SERVER["QUERY_STRING"] ?? "", $query);
            if (empty($query["id"])) {
                responder(false, "Debe indicar el id del producto en la URL (?id=).", null, 400);
            }

            try {
                $sql = "DELETE FROM productos WHERE id = ?";
                $st  = $cn->prepare($sql);
                $st->execute([$query["id"]]);

                if ($st->rowCount() === 0) {
                    responder(false, "No existe el producto indicado.", null, 404);
                }

                responder(true, "Producto eliminado correctamente.", null, 200);
            } catch (Exception $e) {
                responder(false, "No se puede eliminar el producto, posiblemente está en detalles de ventas.", null, 409);
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