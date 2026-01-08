<?php
header('Content-Type: application/json');
require_once '../config.php';

$usuario_id = $_GET['id'] ?? '';

if (empty($usuario_id)) {
    echo json_encode(null);
    exit;
}

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception('Error de conexión: ' . $conn->connect_error);
    }

    $stmt = $conn->prepare('SELECT id, nombre, email, telefono, fecha_nacimiento, genero, fecha_registro FROM usuarios WHERE id = ?');
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {
        echo json_encode(null);
    } else {
        $usuario = $resultado->fetch_assoc();
        echo json_encode($usuario);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
