<?php
// api/buscar_producto.php - Búsqueda de productos con Open Food Facts

session_start();
require_once '../config.php';
require_once 'open_food_facts.php';

header('Content-Type: application/json');

try {
    // Validar entrada
    if (!isset($_GET['codigo_barras']) && !isset($_POST['codigo_barras'])) {
        throw new Exception('Código de barras requerido');
    }
    
    $codigo_barras = trim($_GET['codigo_barras'] ?? $_POST['codigo_barras'] ?? '');
    
    if (empty($codigo_barras)) {
        throw new Exception('Código de barras vacío');
    }
    
    // Buscar en Open Food Facts
    $api = new OpenFoodFactsAPI();
    $producto = $api->buscarProductoPorCodigo($codigo_barras);
    
    if (!$producto) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'Producto no encontrado en Open Food Facts'
        ]);
        exit;
    }
    
    // Si el usuario está autenticado, guardar en historial
    if (isset($_SESSION['usuario_id'])) {
        $usuario_id = $_SESSION['usuario_id'];
        
        $stmt = $conexion->prepare("
            INSERT INTO historial_escaneos 
            (usuario_id, codigo_barras, nombre_producto, marca, imagen_url, alergenos_detectados) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        if (!$stmt) {
            throw new Exception('Error en prepared statement: ' . $conexion->error);
        }
        
        $alergenos_json = json_encode($producto['alergenos']);
        $stmt->bind_param(
            "isssss", 
            $usuario_id, 
            $codigo_barras,
            $producto['nombre'],
            $producto['marca'],
            $producto['imagen_url'],
            $alergenos_json
        );
        
        $stmt->execute();
        $stmt->close();
    }
    
    // Obtener alérgenos del usuario si está autenticado
    $alergenos_usuario = [];
    if (isset($_SESSION['usuario_id'])) {
        $result = $conexion->query("
            SELECT nombre_alergeno FROM usuario_alergenos
            WHERE usuario_id = " . $_SESSION['usuario_id']
        );
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $alergenos_usuario[] = $row['nombre_alergeno'];
            }
        }
    }
    
    // Comparar alérgenos detectados con los del usuario
    $alergenos_coincidentes = array_intersect(
        $producto['alergenos'],
        $alergenos_usuario
    );
    
    // Preparar mensaje de advertencia si hay coincidencias
    $mensaje_advertencia = '';
    if (!empty($alergenos_coincidentes)) {
        $mensaje_advertencia = '¡Atención! Este producto contiene ' . implode(', ', $alergenos_coincidentes) . ', que son alérgenos que has registrado. No debes consumirlo.';
    }

    echo json_encode([
        'exito' => true,
        'producto' => [
            'codigo_barras' => $producto['codigo_barras'],
            'nombre' => $producto['nombre'],
            'marca' => $producto['marca'],
            'imagen' => $producto['imagen_url'],
            'url' => $producto['url']
        ],
        // Cambiado para coincidir con el frontend
        'alergenos_detectados' => $producto['alergenos'], 
        'alergenos_coincidentes' => array_values($alergenos_coincidentes),
        'ingredientes' => $producto['ingredientes'],
        'mensaje_advertencia' => $mensaje_advertencia
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'exito' => false,
        'error' => $e->getMessage()
    ]);
}
?>
