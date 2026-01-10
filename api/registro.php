<?php
header('Content-Type: application/json');
require_once '../config.php';

// Obtener datos POST
$nombre = $_POST['nombre'] ?? '';
$email = $_POST['email'] ?? '';
$telefono = $_POST['telefono'] ?? '';
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
$genero = $_POST['genero'] ?? '';
$password = $_POST['password'] ?? '';
$alergenos = $_POST['alergenos'] ?? '[]';
$alergenos_personalizados = $_POST['alergenos_personalizados'] ?? '';

// Validaciones básicas
if (empty($nombre) || empty($email) || empty($password) || empty($fecha_nacimiento)) {
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

// Validar fecha de nacimiento y edad
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_nacimiento)) {
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Formato de fecha inválido'
    ]);
    exit;
}

try {
    $fecha = new DateTime($fecha_nacimiento);
    $hoy = new DateTime();
    $edad = $hoy->diff($fecha)->y;

    if ($edad < 13 || $edad > 120) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'Debes tener entre 13 y 120 años'
        ]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Fecha de nacimiento inválida'
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
    $stmt = $conn->prepare('INSERT INTO usuarios (nombre, email, password, fecha_nacimiento, fecha_registro) VALUES (?, ?, ?, ?, NOW())');
    
    if (!$stmt) {
        throw new Exception('Error en la consulta preparada: ' . $conn->error);
    }

    $stmt->bind_param('ssss', $nombre, $email, $password_hash, $fecha_nacimiento);
    
    if ($stmt->execute()) {
        $usuario_id = $stmt->insert_id;
        
        // Procesar alérgenos si se enviaron
        $alergenos_seleccionados = json_decode($_POST['alergenos'] ?? '[]', true);

        if (!empty($alergenos_seleccionados) && is_array($alergenos_seleccionados)) {
            $stmt_alergeno = $conn->prepare("INSERT INTO usuario_alergenos (usuario_id, nombre_alergeno) VALUES (?, ?)");
            if ($stmt_alergeno) {
                foreach ($alergenos_seleccionados as $alergeno_nombre) {
                    $stmt_alergeno->bind_param("is", $usuario_id, $alergeno_nombre);
                    $stmt_alergeno->execute();
                }
                $stmt_alergeno->close();
            }
        }

        // Iniciar sesión automáticamente
        session_start();
        $_SESSION['usuario_id'] = $usuario_id;
        $_SESSION['usuario_nombre'] = $nombre;
        $_SESSION['usuario_email'] = $email;

        echo json_encode([
            'exito' => true,
            'mensaje' => 'Cuenta creada exitosamente. ¡Bienvenido!',
            'usuario_id' => $usuario_id,
            'redirect' => '../dashboard.html'
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
