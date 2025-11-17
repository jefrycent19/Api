<?php
// api_categorias.php
// CRUD para la tabla categorias

require_once "utilidades.php";

configurarCors();
$metodo = $_SERVER["REQUEST_METHOD"];

try {
    $cn = obtenerConexion();

    switch ($metodo) {

        // LISTAR / BUSCAR
        case "GET":
            if (isset($_GET["id"])) {
                $sql = "SELECT * FROM categorias WHERE id = ?";
                $st  = $cn->prepare($sql);
                $st->execute([$_GET["id"]]);
                $cat = $st->fetch();
                if ($cat === false) {
                    responder(false, "Categoría no encontrada.", null, 404);
                }
                responder(true, "Categoría encontrada.", $cat, 200);
            } else {
                $sql = "SELECT * FROM categorias";
                $st  = $cn->query($sql);
                $lista = $st->fetchAll();
                responder(true, "Lista de categorías.", $lista, 200);
            }
            break;

        // INSERTAR
        case "POST":
            $json = leerJson();
            if (empty($json["nombre"])) {
                responder(false, "El nombre de la categoría es obligatorio.", null, 400);
            }

            $sql = "INSERT INTO categorias (nombre) VALUES (?)";
            $st  = $cn->prepare($sql);
            $st->execute([$json["nombre"]]);
            $id = $cn->lastInsertId();

            responder(true, "Categoría creada correctamente.", ["id" => $id, "nombre" => $json["nombre"]], 201);
            break;

        // EDITAR
        case "PUT":
            parse_str($_SERVER["QUERY_STRING"] ?? "", $query);
            if (empty($query["id"])) {
                responder(false, "Debe indicar el id de la categoría en la URL (?id=).", null, 400);
            }

            $json = leerJson();
            if (empty($json["nombre"])) {
                responder(false, "El nombre de la categoría es obligatorio.", null, 400);
            }

            $sql = "UPDATE categorias SET nombre = ? WHERE id = ?";
            $st  = $cn->prepare($sql);
            $st->execute([$json["nombre"], $query["id"]]);

            if ($st->rowCount() === 0) {
                responder(false, "No se encontró la categoría para actualizar.", null, 404);
            }

            responder(true, "Categoría actualizada correctamente.", ["id" => $query["id"], "nombre" => $json["nombre"]], 200);
            break;

        // ELIMINAR
        case "DELETE":
            parse_str($_SERVER["QUERY_STRING"] ?? "", $query);
            if (empty($query["id"])) {
                responder(false, "Debe indicar el id de la categoría en la URL (?id=).", null, 400);
            }

            try {
                $sql = "DELETE FROM categorias WHERE id = ?";
                $st  = $cn->prepare($sql);
                $st->execute([$query["id"]]);

                if ($st->rowCount() === 0) {
                    responder(false, "No existe la categoría indicada.", null, 404);
                }

                responder(true, "Categoría eliminada correctamente.", null, 200);
            } catch (Exception $e) {
                responder(false, "No se puede eliminar la categoría, posiblemente está en uso por productos.", null, 409);
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