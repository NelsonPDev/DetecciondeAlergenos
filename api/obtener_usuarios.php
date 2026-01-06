<?php
// api/obtener_usuarios.php

header('Content-Type: application/json');

require_once '../config.php';

$respuesta = [
    'exito' => false,
    'usuarios' => []
];

try {
    $query = 'SELECT id, nombre, edad FROM usuarios ORDER BY nombre';
    $resultado = $conexion->query($query);

    if ($resultado) {
        while ($row = $resultado->fetch_assoc()) {
            // Obtener alergenos del usuario
            $alergenos_query = 'SELECT a.id FROM usuario_alergenos ua 
                               JOIN alergenos a ON ua.alergeno_id = a.id 
                               WHERE ua.usuario_id = ?';
            $stmt = $conexion->prepare($alergenos_query);
            $stmt->bind_param('i', $row['id']);
            $stmt->execute();
            $alergenos_resultado = $stmt->get_result();
            
            $alergenos_ids = [];
            while ($alergeno = $alergenos_resultado->fetch_assoc()) {
                $alergenos_ids[] = $alergeno['id'];
            }
            
            $row['alergenos'] = $alergenos_ids;
            $respuesta['usuarios'][] = $row;
            $stmt->close();
        }
        $respuesta['exito'] = true;
    }
} catch (Exception $e) {
    $respuesta['mensaje'] = $e->getMessage();
}

$conexion->close();
echo json_encode($respuesta);
?>
