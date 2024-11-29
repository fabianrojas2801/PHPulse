<?php
require_once('DB.php'); // Consultas con PDO

class GenericAPI 
{
    private $entity; // Nombre de la entidad (por ejemplo: people, user, etc.)
    private $db;

    public function __construct($entity)
    {
        $this->entity = $entity;
        $this->db = new DB(); // Usamos la clase DB genérica para la conexión
    }

    public function API()
    {
        header('Content-Type: application/json');
        $method = $_SERVER['REQUEST_METHOD'];
       
        switch ($method) {
            case 'GET': // Consulta
                $this->getData();
                break;

            case 'POST': // Inserta //check buscar
                if (isset($_GET['check']) && $_GET['check'] === 'true') {
                    $this->checkRow();
                } else {
                $this->saveData();
                }
                break;

            case 'PUT': // Actualiza
                $this->updateData();
                break;

            case 'DELETE': // Elimina
                $this->deleteData();
                break;

            default: // Método NO soportado
                echo json_encode(['status' => 'error', 'message' => 'Método no soportado']);
                break;
        }
    }

    /**
     * Función de respuesta HTTP genérica
     */
    private function response($code = 200, $status = "", $message = "")
    {
        http_response_code($code);
        if (!empty($status) && !empty($message)) {
            $response = array("status" => $status, "message" => $message);
            echo json_encode($response, JSON_PRETTY_PRINT);
        }
    }

    /**
     * Obtener datos según el tipo de entidad
     */
    private function getData()
    {
        if (isset($_GET['action']) && $_GET['action'] === $this->entity) {
            if (isset($_GET['id'])) {
                // Mostrar un solo registro si existe el ID
                $response = $this->db->getRecord($this->entity, $_GET['id']);
                echo json_encode($response, JSON_PRETTY_PRINT);
            } else {
                // Mostrar todos los registros
                $response = $this->db->getAllRecords($this->entity);
                echo json_encode($response, JSON_PRETTY_PRINT);
            }
        } else {
            $this->response(400, "error", "Acción no válida");
        }
    }

    /**
     * Guardar un nuevo registro
     */
    private function saveData()
    {
        if (isset($_GET['action']) && $_GET['action'] === $this->entity) {
            // Decodificar JSON
            $obj = json_decode(file_get_contents('php://input'));
            $objArr = (array)$obj;

            if (empty($objArr)) {
                $this->response(422, "error", "Nada para agregar. Verifica el JSON");
            } else {
                // Validación condicional según la entidad
               /* if ($this->entity === 'usuarios' || $this->entity === 'clientes') {
                    if (!isset($obj->name)) {
                        $this->response(422, "error", "La propiedad 'name' no está definida");
                        return;
                    }
                }*/

                // Insertar los datos en la base de datos
                $this->db->insert($this->entity, $obj);
                $this->response(200, "success", "Nuevo registro agregado");
            }
        } else {
            $this->response(400, "error", "Acción no válida");
        }
    }

    /**
     * Actualizar un registro
     */
    private function updateData()
    {
        if (isset($_GET['action']) && isset($_GET['id']) && $_GET['action'] === $this->entity) {
            // Decodificar JSON
            $obj = json_decode(file_get_contents('php://input'));
            $objArr = (array)$obj;

            if (empty($objArr)) {
                $this->response(422, "error", "Nada para actualizar. Verifica el JSON");
            } else {
                // Validación condicional según la entidad
              /*  if ($this->entity === 'usuarios' || $this->entity === 'clientes') {
                    if (!isset($obj->name)) {
                        $this->response(422, "error", "La propiedad 'name' no está definida");
                        return;
                    }
                }*/

                // Actualizar los datos en la base de datos
                $this->db->update($this->entity, $_GET['id'], $obj);
                $this->response(200, "success", "Registro actualizado");
            }
        } else {
            $this->response(400, "error", "Acción o ID no válido");
        }
    }

    /**
     * Eliminar un registro
     */
    public function deleteData()
    {
        if (isset($_GET['action']) && isset($_GET['id']) && $_GET['action'] === $this->entity) {
            $this->db->delete($this->entity, $_GET['id']);
            // Enviar respuesta JSON de éxito
            $this->response(200, "success", "Registro eliminado");
        } else {
            $this->response(400, "error", "Acción o ID no válido");
        }
    }

/**
 * Permite realizar una búsqueda en las entidades por key:value, aplicado al Where de forma automática
 */

public function checkRow()
{
    if (isset($_GET['action']) && $_GET['action'] === $this->entity) {
        // Decodificar el JSON con los campos para verificar
        $obj = json_decode(file_get_contents('php://input'));
        $objArr = (array)$obj;

        if (empty($objArr)) {
            $this->response(422, "error", "No se proporcionaron datos para verificar.");
            return;
        } 

        try {
            $exists = $this->db->checkRow($this->entity, $objArr);
            if ($exists) {
                $this->response(200, "success", "La fila existe en la tabla.");
            } else {
                $this->response(404, "error", "No se encontró ninguna fila con los datos proporcionados.");
            }
        } catch (Exception $e) {
            $this->response(500, "error", "Error al verificar los datos: " . $e->getMessage());
        }
    } else {
        $this->response(400, "error", "Acción no válida.");
    }
}

}

?>