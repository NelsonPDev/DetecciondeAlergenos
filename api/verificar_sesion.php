<?php
header('Content-Type: application/json');
session_start();

if (isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'logueado' => true,
        'usuario' => [
            'id' => $_SESSION['usuario_id'],
            'nombre' => $_SESSION['usuario_nombre'],
            'email' => $_SESSION['usuario_email']
        ]
    ]);
} else {
    echo json_encode([
        'logueado' => false,
        'usuario' => null
    ]);
}
?>
