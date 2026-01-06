<?php
// api/obtener_alergenos.php

header('Content-Type: application/json');

require_once '../config.php';

$respuesta = [
    'exito' => false,
    'alergenos' => []
];

try {
    $query = 'SELECT id, nombre, descripcion FROM alergenos ORDER BY nombre';
    $resultado = $conexion->query($query);

    if ($resultado) {
        while ($row = $resultado->fetch_assoc()) {
            $respuesta['alergenos'][] = $row;
        }
        $respuesta['exito'] = true;
    }
} catch (Exception $e) {
    $respuesta['mensaje'] = $e->getMessage();
}

$conexion->close();
echo json_encode($respuesta);
?>
