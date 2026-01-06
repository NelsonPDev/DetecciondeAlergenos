<?php
// api/registrar_usuario.php

header('Content-Type: application/json');

require_once '../config.php';

$respuesta = [
    'exito' => false,
    'mensaje' => ''
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Obtener datos
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $edad = intval($_POST['edad'] ?? 0);
    $genero = trim($_POST['genero'] ?? '');
    $gravedad = trim($_POST['gravedad'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');
    $alergenos = isset($_POST['alergenos']) ? $_POST['alergenos'] : [];
    $terminos = isset($_POST['terminos']) ? true : false;

    // Validaciones
    if (empty($nombre) || strlen($nombre) < 3) {
        throw new Exception('El nombre debe tener al menos 3 caracteres');
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Correo electrónico inválido');
    }

    if (empty($telefono) || strlen($telefono) < 7) {
        throw new Exception('Teléfono inválido');
    }

    if (empty($edad) || $edad < 1 || $edad > 120) {
        throw new Exception('Edad inválida');
    }

    if (empty($genero)) {
        throw new Exception('Género no seleccionado');
    }

    if (empty($alergenos)) {
        throw new Exception('Debes seleccionar al menos un alergeno');
    }

    if (empty($gravedad)) {
        throw new Exception('Gravedad de alergias no seleccionada');
    }

    if (!$terminos) {
        throw new Exception('Debes aceptar los términos y condiciones');
    }

    // Verificar si el email ya existe
    $stmt = $conexion->prepare('SELECT id FROM usuarios WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        throw new Exception('Este correo electrónico ya está registrado');
    }
    $stmt->close();

    // Insertar usuario
    $stmt = $conexion->prepare('
        INSERT INTO usuarios (nombre, email, telefono, edad, genero)
        VALUES (?, ?, ?, ?, ?)
    ');

    $stmt->bind_param('sssss', $nombre, $email, $telefono, $edad, $genero);
    
    if (!$stmt->execute()) {
        throw new Exception('Error al insertar usuario: ' . $stmt->error);
    }

    $usuario_id = $stmt->insert_id;
    $stmt->close();

    // Insertar alergenos del usuario
    $stmt = $conexion->prepare('
        INSERT INTO usuario_alergenos (usuario_id, alergeno_id, gravedad, observaciones)
        VALUES (?, ?, ?, ?)
    ');

    foreach ($alergenos as $alergeno_id) {
        $alergeno_id = intval($alergeno_id);
        $stmt->bind_param('iiss', $usuario_id, $alergeno_id, $gravedad, $observaciones);
        
        if (!$stmt->execute()) {
            throw new Exception('Error al asignar alergenos: ' . $stmt->error);
        }
    }
    $stmt->close();

    $respuesta['exito'] = true;
    $respuesta['mensaje'] = 'Registro completado exitosamente';
    $respuesta['usuario_id'] = $usuario_id;

} catch (Exception $e) {
    $respuesta['exito'] = false;
    $respuesta['mensaje'] = $e->getMessage();
}

$conexion->close();
echo json_encode($respuesta);
?>
