<?php
// api/obtener_alergenos_usuario.php

session_start();
header('Content-Type: application/json');
require_once '../config.php';

$respuesta = [
    'exito' => false,
    'alergenos' => []
];

try {
    // Obtener usuario_id de la sesión o parámetro
    $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : intval($_GET['usuario_id'] ?? 0);

    if ($usuario_id <= 0) {
        throw new Exception('Usuario inválido');
    }

    $query = 'SELECT nombre_alergeno FROM usuario_alergenos 
              WHERE usuario_id = ? 
              ORDER BY nombre_alergeno';
    
    $stmt = $conexion->prepare($query);
    if (!$stmt) {
        throw new Exception('Error en la consulta: ' . $conexion->error);
    }
    
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    while ($row = $resultado->fetch_assoc()) {
        $respuesta['alergenos'][] = $row['nombre_alergeno'];
    }
    
    $respuesta['exito'] = true;
    $stmt->close();
} catch (Exception $e) {
    $respuesta['mensaje'] = $e->getMessage();
}

echo json_encode($respuesta);
?>
