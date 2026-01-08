<?php
// api/obtener_alergenos_usuario.php

header('Content-Type: application/json');

require_once '../config.php';

$respuesta = [
    'exito' => false,
    'alergenos' => []
];

try {
    $usuario_id = intval($_GET['usuario_id'] ?? 0);

    if ($usuario_id <= 0) {
        throw new Exception('Usuario inválido');
    }

    $query = 'SELECT a.id, a.nombre
              FROM usuario_alergenos ua
              JOIN alergenos a ON ua.alergeno_id = a.id
              WHERE ua.usuario_id = ?
              ORDER BY a.nombre';
    
    $stmt = $conexion->prepare($query);
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    while ($row = $resultado->fetch_assoc()) {
        $respuesta['alergenos'][] = $row;
    }
    
    $respuesta['exito'] = true;
    $stmt->close();
} catch (Exception $e) {
    $respuesta['mensaje'] = $e->getMessage();
}

$conexion->close();
echo json_encode($respuesta);
?>
