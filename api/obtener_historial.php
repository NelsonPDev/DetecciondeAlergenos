<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['exito' => false, 'mensaje' => 'Usuario no autenticado']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

$stmt = $conexion->prepare("
    SELECT id, codigo_barras, nombre_producto, marca, imagen_url, alergenos_detectados, fecha_escaneo
    FROM historial_escaneos
    WHERE usuario_id = ?
    ORDER BY fecha_escaneo DESC
    LIMIT 50
");

if (!$stmt) {
    echo json_encode(['exito' => false, 'mensaje' => 'Error en la preparación de la consulta: ' . $conexion->error]);
    exit;
}

$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$resultado = $stmt->get_result();

$historial = [];
while ($fila = $resultado->fetch_assoc()) {
    $fila['alergenos_detectados'] = json_decode($fila['alergenos_detectados']);
    $historial[] = $fila;
}

$stmt->close();
$conexion->close();

echo json_encode(['exito' => true, 'historial' => $historial]);
?>
