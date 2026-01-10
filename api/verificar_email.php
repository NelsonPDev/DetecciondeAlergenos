<?php
require_once '../config.php'; // Incluye y ejecuta config.php, estableciendo $GLOBALS['conexion']

header('Content-Type: application/json');

// Leer el cuerpo de la solicitud JSON
$data = json_decode(file_get_contents('php://input'), true);

// Verificar que se haya proporcionado un correo electrónico
if (!isset($data['email']) || empty($data['email'])) {
    // Aunque el cliente debería validar, es bueno tener una doble verificación.
    echo json_encode(['existe' => false, 'mensaje' => 'No se proporcionó correo electrónico.']);
    exit;
}

$email = $data['email'];

// Validar el formato del correo electrónico en el servidor
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['existe' => false, 'mensaje' => 'Formato de correo electrónico inválido.']);
    exit;
}

// Usar la conexión global de mysqli establecida en config.php
$conexion = $GLOBALS['conexion'];

try {
    // Preparar la consulta para buscar el correo electrónico
    $consulta = "SELECT id FROM usuarios WHERE email = ?";
    $sentencia = $conexion->prepare($consulta);
    
    // Si la preparación de la consulta falla, lanza una excepción.
    if (!$sentencia) {
        throw new Exception('Fallo al preparar la consulta: ' . $conexion->error);
    }

    // Vincular el parámetro de email
    $sentencia->bind_param('s', $email);
    
    // Ejecutar la consulta
    $sentencia->execute();
    
    // Almacenar el resultado para poder verificar el número de filas
    $sentencia->store_result();

    // Comprobar si se encontró algún resultado
    if ($sentencia->num_rows > 0) {
        // El correo ya existe
        echo json_encode(['existe' => true]);
    } else {
        // El correo no existe
        echo json_encode(['existe' => false]);
    }
    
    // Cerrar la sentencia
    $sentencia->close();

} catch (Exception $e) {
    // Manejar cualquier error durante el proceso
    http_response_code(500); // Internal Server Error
    // En un entorno de producción, es mejor registrar este mensaje que mostrarlo.
    echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>