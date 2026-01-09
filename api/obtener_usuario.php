<?php
header('Content-Type: application/json');
session_start();
require_once '../config.php';

// Verificar si hay sesión activa
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['exito' => false, 'mensaje' => 'No autorizado']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception('Error de conexión: ' . $conn->connect_error);
    }

    $stmt = $conn->prepare('SELECT id, nombre, email, fecha_nacimiento FROM usuarios WHERE id = ?');
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['exito' => false, 'mensaje' => 'Usuario no encontrado']);
    } else {
        $usuario = $resultado->fetch_assoc();
        echo json_encode([
            'exito' => true,
            'usuario' => [
                'id' => $usuario['id'],
                'nombre' => $usuario['nombre'],
                'email' => $usuario['email'],
                'fecha_nacimiento' => $usuario['fecha_nacimiento']
            ]
        ]);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['exito' => false, 'error' => $e->getMessage()]);
}
?>
