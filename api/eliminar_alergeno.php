<?php
// api/eliminar_alergeno.php - Eliminar alérgeno del perfil del usuario

session_start();
header('Content-Type: application/json');
require_once '../config.php';

$respuesta = [
    'exito' => false,
    'mensaje' => ''
];

try {
    // Verificar que el usuario esté autenticado
    if (!isset($_SESSION['usuario_id'])) {
        throw new Exception('Usuario no autenticado');
    }

    $usuario_id = $_SESSION['usuario_id'];
    
    // Validar que se envíe el nombre del alérgeno
    $metodo = $_SERVER['REQUEST_METHOD'];
    
    if ($metodo === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $nombre_alergeno = $input['nombre_alergeno'] ?? '';
    } else {
        $nombre_alergeno = $_GET['nombre_alergeno'] ?? '';
    }

    if (empty($nombre_alergeno)) {
        throw new Exception('Nombre del alérgeno requerido');
    }

    $nombre_alergeno = trim($nombre_alergeno);

    // Verificar que el alérgeno pertenece al usuario
    $check_stmt = $conexion->prepare('
        SELECT id FROM usuario_alergenos 
        WHERE usuario_id = ? AND nombre_alergeno = ?
    ');
    
    if (!$check_stmt) {
        throw new Exception('Error en la consulta: ' . $conexion->error);
    }

    $check_stmt->bind_param('is', $usuario_id, $nombre_alergeno);
    $check_stmt->execute();
    $resultado = $check_stmt->get_result();

    if ($resultado->num_rows === 0) {
        throw new Exception('Alérgeno no encontrado en tu perfil');
    }

    $check_stmt->close();

    // Eliminar el alérgeno
    $stmt = $conexion->prepare('
        DELETE FROM usuario_alergenos 
        WHERE usuario_id = ? AND nombre_alergeno = ?
    ');

    if (!$stmt) {
        throw new Exception('Error en la eliminación: ' . $conexion->error);
    }

    $stmt->bind_param('is', $usuario_id, $nombre_alergeno);
    
    if (!$stmt->execute()) {
        throw new Exception('Error al eliminar alérgeno: ' . $stmt->error);
    }

    $respuesta['exito'] = true;
    $respuesta['mensaje'] = 'Alérgeno eliminado correctamente';
    $respuesta['nombre_alergeno'] = $nombre_alergeno;

    $stmt->close();

} catch (Exception $e) {
    $respuesta['mensaje'] = $e->getMessage();
}

echo json_encode($respuesta);
?>
