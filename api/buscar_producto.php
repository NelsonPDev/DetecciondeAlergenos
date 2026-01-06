<?php
// api/buscar_producto.php

header('Content-Type: application/json');

require_once '../config.php';

$respuesta = [
    'exito' => false,
    'producto' => null,
    'alergenos' => []
];

try {
    $codigo_barras = trim($_POST['codigo_barras'] ?? '');

    if (empty($codigo_barras)) {
        throw new Exception('Código de barras vacío');
    }

    // Buscar producto
    $query = 'SELECT * FROM productos WHERE codigo_barras = ?';
    $stmt = $conexion->prepare($query);
    $stmt->bind_param('s', $codigo_barras);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $producto = $resultado->fetch_assoc();
        $respuesta['producto'] = $producto;

        // Obtener alergenos del producto
        $alergenos_query = 'SELECT a.id, a.nombre, pa.tipo_presencia, pa.trazas
                           FROM producto_alergenos pa
                           JOIN alergenos a ON pa.alergeno_id = a.id
                           WHERE pa.producto_id = ?
                           ORDER BY a.nombre';
        
        $stmt2 = $conexion->prepare($alergenos_query);
        $stmt2->bind_param('i', $producto['id']);
        $stmt2->execute();
        $alergenos_resultado = $stmt2->get_result();

        while ($alergeno = $alergenos_resultado->fetch_assoc()) {
            $respuesta['alergenos'][] = $alergeno;
        }

        $respuesta['exito'] = true;
        $stmt2->close();

        // Registrar escaneo
        $usuario_id = null;
        $tiene_alergenos = count($respuesta['alergenos']) > 0 ? 1 : 0;
        $alergenos_detectados = json_encode($respuesta['alergenos']);

        $insert_query = 'INSERT INTO historial_escaneos 
                        (usuario_id, producto_id, codigo_barras, tiene_alergenos, alergenos_detectados)
                        VALUES (?, ?, ?, ?, ?)';
        
        $insert_stmt = $conexion->prepare($insert_query);
        $insert_stmt->bind_param('isisi', $usuario_id, $producto['id'], $codigo_barras, $tiene_alergenos, $alergenos_detectados);
        $insert_stmt->execute();
        $insert_stmt->close();

    } else {
        $respuesta['exito'] = true; // Se encontró pero está vacío
        $respuesta['producto'] = null;
    }

    $stmt->close();
} catch (Exception $e) {
    $respuesta['exito'] = false;
    $respuesta['mensaje'] = $e->getMessage();
}

$conexion->close();
echo json_encode($respuesta);
?>
