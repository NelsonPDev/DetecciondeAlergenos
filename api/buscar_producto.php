<?php
// api/buscar_producto.php

header('Content-Type: application/json');
session_start();

require_once '../config.php';

$respuesta = [
    'exito' => false,
    'producto' => null,
    'alergenos' => []
];

try {
    $codigo_barras = trim($_POST['codigo_barras'] ?? '');
    $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null;

    if (empty($codigo_barras)) {
        throw new Exception('Código de barras vacío');
    }

    // Conectar a la base de datos
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception('Error de conexión: ' . $conn->connect_error);
    }

    // Buscar producto
    $stmt = $conn->prepare('SELECT id, codigo_barras, nombre, marca, categoria, descripcion FROM productos WHERE codigo_barras = ?');
    $stmt->bind_param('s', $codigo_barras);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $producto = $resultado->fetch_assoc();
        $respuesta['producto'] = $producto;

        // Obtener alergenos del producto
        $stmt2 = $conn->prepare('
            SELECT a.id, a.nombre, pa.tipo_presencia, pa.trazas
            FROM producto_alergenos pa
            JOIN alergenos a ON pa.alergeno_id = a.id
            WHERE pa.producto_id = ?
            ORDER BY a.nombre
        ');
        
        $stmt2->bind_param('i', $producto['id']);
        $stmt2->execute();
        $alergenos_resultado = $stmt2->get_result();

        while ($alergeno = $alergenos_resultado->fetch_assoc()) {
            $respuesta['alergenos'][] = $alergeno;
        }

        $respuesta['exito'] = true;
        $stmt2->close();

        // Registrar escaneo si hay usuario logueado
        if ($usuario_id) {
            $tiene_alergenos = count($respuesta['alergenos']) > 0 ? 1 : 0;

            $insert_stmt = $conn->prepare('
                INSERT INTO historial_escaneos (usuario_id, producto_id, codigo_barras, fecha_escaneo, tiene_alergenos)
                VALUES (?, ?, ?, NOW(), ?)
            ');
            
            $insert_stmt->bind_param('iisi', $usuario_id, $producto['id'], $codigo_barras, $tiene_alergenos);
            $insert_stmt->execute();
            $insert_stmt->close();
        }

    } else {
        $respuesta['exito'] = true;
        $respuesta['producto'] = null;
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    $respuesta['exito'] = false;
    $respuesta['mensaje'] = $e->getMessage();
}

echo json_encode($respuesta);
?>
