<?php
// sesiones.php - Gestión de sesiones y autenticación

session_start();

// Verificar si el usuario está logueado
function verificar_sesion() {
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_nombre'])) {
        header('Location: login.html');
        exit;
    }
}

// Obtener datos del usuario logueado
function obtener_usuario_sesion() {
    if (isset($_SESSION['usuario_id'])) {
        return [
            'id' => $_SESSION['usuario_id'],
            'nombre' => $_SESSION['usuario_nombre'],
            'email' => $_SESSION['usuario_email']
        ];
    }
    return null;
}

// Crear sesión de usuario
function crear_sesion($usuario_id, $usuario_nombre, $usuario_email) {
    $_SESSION['usuario_id'] = $usuario_id;
    $_SESSION['usuario_nombre'] = $usuario_nombre;
    $_SESSION['usuario_email'] = $usuario_email;
    $_SESSION['fecha_login'] = date('Y-m-d H:i:s');
}

// Destruir sesión
function cerrar_sesion() {
    session_destroy();
    header('Location: login.html');
    exit;
}

// Verificar si la sesión es válida
function es_usuario_logueado() {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}
?>
