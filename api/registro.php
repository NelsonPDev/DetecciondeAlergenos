<?php
header('Content-Type: application/json');
require_once '../config.php';

// Obtener datos POST
$nombre = $_POST['nombre'] ?? '';
$email = $_POST['email'] ?? '';
$telefono = $_POST['telefono'] ?? '';
$edad = $_POST['edad'] ?? '';
$genero = $_POST['genero'] ?? '';
$password = $_POST['password'] ?? '';

// Validaciones básicas
if (empty($nombre) || empty($email) || empty($password) || empty($edad)) {
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Todos los campos son requeridos'
    ]);
    exit;
}

// Validar formato de email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'exito' => false,
        'mensaje' => 'El formato del correo electrónico no es válido'
    ]);
    exit;
}

// Validar contraseña (mínimo 6 caracteres, una mayúscula y un número)
if (!preg_match('/^(?=.*[A-Z])(?=.*\d).{6,}$/', $password)) {
    echo json_encode([
        'exito' => false,
        'mensaje' => 'La contraseña debe tener mínimo 6 caracteres, una mayúscula y un número'
    ]);
    exit;
}

try {
    // Conectar a la base de datos
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception('Error de conexión: ' . $conn->connect_error);
    }

    // Verificar si el email ya existe
    $stmt = $conn->prepare('SELECT id FROM usuarios WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'El correo electrónico ya está registrado'
        ]);
        $stmt->close();
        $conn->close();
        exit;
    }

    $stmt->close();

    // Hash de la contraseña
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Insertar nuevo usuario
    $stmt = $conn->prepare('INSERT INTO usuarios (nombre, email, telefono, edad, genero, password, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, NOW())');
    
    if (!$stmt) {
        throw new Exception('Error en la consulta preparada: ' . $conn->error);
    }

    $stmt->bind_param('sssiss', $nombre, $email, $telefono, $edad, $genero, $password_hash);
    
    if ($stmt->execute()) {
        $usuario_id = $stmt->insert_id;

        echo json_encode([
            'exito' => true,
            'mensaje' => 'Cuenta creada exitosamente. Redirigiendo a login...',
            'usuario_id' => $usuario_id
        ]);
    } else {
        throw new Exception('Error al insertar: ' . $stmt->error);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error al registrar usuario: ' . $e->getMessage()
    ]);
}
?>
