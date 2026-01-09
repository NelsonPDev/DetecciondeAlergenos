<?php
header('Content-Type: application/json');
session_start();
require_once '../config.php';

// Obtener datos POST
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Validaciones básicas
if (empty($email) || empty($password)) {
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Correo y contraseña son requeridos'
    ]);
    exit;
}

try {
    // Conectar a la base de datos
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception('Error de conexión: ' . $conn->connect_error);
    }

    // Buscar usuario por email
    $stmt = $conn->prepare('SELECT id, nombre, email, password FROM usuarios WHERE email = ?');
    
    if (!$stmt) {
        throw new Exception('Error en la consulta preparada: ' . $conn->error);
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'El correo o contraseña son incorrectos'
        ]);
        $stmt->close();
        $conn->close();
        exit;
    }

    $usuario = $resultado->fetch_assoc();
    $stmt->close();

    // Verificar contraseña
    if (!password_verify($password, $usuario['password'])) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'El correo o contraseña son incorrectos'
        ]);
        $conn->close();
        exit;
    }

    // Crear sesión
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nombre'] = $usuario['nombre'];
    $_SESSION['usuario_email'] = $usuario['email'];

    echo json_encode([
        'exito' => true,
        'mensaje' => 'Sesión iniciada correctamente',
        'usuario_id' => $usuario['id'],
        'usuario_nombre' => $usuario['nombre'],
        'redirect' => '../dashboard.html'
    ]);

    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error al iniciar sesión: ' . $e->getMessage()
    ]);
}
?>
