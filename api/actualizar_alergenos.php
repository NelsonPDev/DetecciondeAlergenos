<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['exito' => false, 'mensaje' => 'Usuario no autenticado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$usuario_id = $_SESSION['usuario_id'];

$alergenos_a_agregar = $data['agregar'] ?? [];
$alergenos_a_eliminar = $data['eliminar'] ?? [];

if (empty($alergenos_a_agregar) && empty($alergenos_a_eliminar)) {
    echo json_encode(['exito' => true, 'mensaje' => 'No hay cambios que guardar.']);
    exit;
}

$conexion->begin_transaction();

try {
    // Eliminar alérgenos
    if (!empty($alergenos_a_eliminar)) {
        $stmt_eliminar = $conexion->prepare("DELETE FROM usuario_alergenos WHERE usuario_id = ? AND nombre_alergeno = ?");
        foreach ($alergenos_a_eliminar as $alergeno) {
            $stmt_eliminar->bind_param("is", $usuario_id, $alergeno);
            $stmt_eliminar->execute();
        }
        $stmt_eliminar->close();
    }

    // Agregar alérgenos
    if (!empty($alergenos_a_agregar)) {
        $stmt_agregar = $conexion->prepare("INSERT IGNORE INTO usuario_alergenos (usuario_id, nombre_alergeno) VALUES (?, ?)");
        foreach ($alergenos_a_agregar as $alergeno) {
            $stmt_agregar->bind_param("is", $usuario_id, $alergeno);
            $stmt_agregar->execute();
        }
        $stmt_agregar->close();
    }

    $conexion->commit();
    echo json_encode(['exito' => true, 'mensaje' => 'Cambios guardados correctamente.']);

} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(['exito' => false, 'mensaje' => 'Error al guardar los cambios: ' . $e->getMessage()]);
}
?>
