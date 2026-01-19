<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['exito' => false, 'mensaje' => 'Usuario no autenticado']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Get filter parameters
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;
$search_query = $_GET['search_query'] ?? null;

$sql = "
    SELECT id, codigo_barras, nombre_producto, marca, imagen_url, alergenos_detectados, fecha_escaneo
    FROM historial_escaneos
    WHERE usuario_id = ?
";

$params = [$usuario_id];
$types = "i";

if ($start_date) {
    $sql .= " AND fecha_escaneo >= ?";
    $params[] = $start_date . ' 00:00:00'; // Start of the day
    $types .= "s";
}
if ($end_date) {
    $sql .= " AND fecha_escaneo <= ?";
    $params[] = $end_date . ' 23:59:59'; // End of the day
    $types .= "s";
}
if ($search_query) {
    $sql .= " AND (nombre_producto LIKE ? OR alergenos_detectados LIKE ?)";
    $params[] = '%' . $search_query . '%';
    $params[] = '%' . $search_query . '%';
    $types .= "ss";
}

$sql .= " ORDER BY fecha_escaneo DESC";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    echo json_encode(['exito' => false, 'mensaje' => 'Error en la preparación de la consulta: ' . $conexion->error]);
    exit;
}

// Bind parameters dynamically
$stmt->bind_param($types, ...$params);
$stmt->execute();
$resultado = $stmt->get_result();

$historial = [];
while ($fila = $resultado->fetch_assoc()) {
    // Decode JSON string back to array for alergenos_detectados
    $fila['alergenos_detectados'] = json_decode($fila['alergenos_detectados'], true);
    $historial[] = $fila;
}

$stmt->close();
$conexion->close();

echo json_encode(['exito' => true, 'historial' => $historial]);
?>