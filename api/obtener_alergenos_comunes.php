<?php
header('Content-Type: application/json');
require_once '../config.php';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception('Error de conexión: ' . $conn->connect_error);
    }

    // Obtener todos los alergenos disponibles
    $sql = 'SELECT id, nombre, descripcion, color, icono FROM alergenos ORDER BY nombre ASC';
    $resultado = $conn->query($sql);

    if (!$resultado) {
        throw new Exception('Error en la consulta: ' . $conn->error);
    }

    $alergenos = [];
    while ($fila = $resultado->fetch_assoc()) {
        $alergenos[] = $fila;
    }

    echo json_encode([
        'exito' => true,
        'alergenos' => $alergenos
    ]);

    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error al obtener alergenos: ' . $e->getMessage()
    ]);
}
?>
