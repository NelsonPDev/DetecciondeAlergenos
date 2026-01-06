<?php
// config.php - Configuración de la base de datos

// Definir parámetros de conexión
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'deteccion_alergenos');

// Crear conexión
$conexion = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

// Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Establecer charset UTF-8
$conexion->set_charset("utf8");

// Variable global para usar en otros archivos
$GLOBALS['conexion'] = $conexion;
?>
