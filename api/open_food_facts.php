<?php
// api/open_food_facts.php - Conexión con API de Open Food Facts

class OpenFoodFactsAPI {
    private $base_url = 'https://es.openfoodfacts.org/api/v0';
    private $timeout = 10;
    
    /**
     * Buscar producto por código de barras
     */
    public function buscarProductoPorCodigo($codigo_barras) {
        $url = $this->base_url . '/product/' . $codigo_barras . '.json';
        
        $response = $this->hacerPeticion($url);
        
        if (!$response || $response['status'] == 0) {
            return null;
        }
        
        $producto = $response['product'];
        
        // Priorizar ingredientes en español, si no, usar el por defecto
        $ingredientes = $producto['ingredients_text_es'] ?? $producto['ingredients_text'] ?? 'No se encontró la lista de ingredientes.';

        return [
            'codigo_barras' => $codigo_barras,
            'nombre' => $producto['product_name_es'] ?? $producto['product_name'] ?? 'Producto sin nombre',
            'marca' => $producto['brands'] ?? 'Marca desconocida',
            'imagen_url' => $producto['image_url'] ?? null,
            'alergenos' => $this->extraerAlergenos($producto),
            'ingredientes' => $ingredientes,
            'pais' => $producto['countries'] ?? null,
            'url' => 'https://es.openfoodfacts.org/product/' . $codigo_barras
        ];
    }
    
    /**
     * Extraer alérgenos de la información del producto
     */
    private function extraerAlergenos($producto) {
        $alergenos = [];
        
        // Buscar en campo de alérgenos
        if (isset($producto['allergens']) && !empty($producto['allergens'])) {
            $allergens_str = strtolower($producto['allergens']);
            $alergenos = $this->mapearAlergenos($allergens_str);
        }
        
        // Buscar en ingredientes si no hay alérgenos específicos
        if (empty($alergenos) && isset($producto['ingredients_text'])) {
            $alergenos = $this->detectarAlergenosEnIngredientes(
                strtolower($producto['ingredients_text'])
            );
        }
        
        return $alergenos;
    }
    
    /**
     * Mapear alérgenos encontrados
     */
    private function mapearAlergenos($texto) {
        $mapeo_alergenos = [
            'Gluten' => ['gluten', 'trigo', 'cebada', 'centeno', 'wheat'],
            'Lactosa' => ['leche', 'lactosa', 'lácteo', 'dairy', 'milk'],
            'Frutos Secos' => ['almendra', 'nuez', 'avellana', 'pistacho', 'tree nuts'],
            'Maní/Cacahuate' => ['cacahuete', 'maní', 'peanut'],
            'Huevo' => ['huevo', 'egg'],
            'Pescado' => ['pescado', 'fish'],
            'Crustáceos' => ['camarón', 'cangrejo', 'langosta', 'crustacean'],
            'Soja' => ['soja', 'soy'],
            'Mostaza' => ['mostaza', 'mustard'],
            'Ajonjolí/Sésamo' => ['sésamo', 'ajonjolí', 'sesame'],
            'Moluscos' => ['almeja', 'mejillón', 'ostra', 'mollusk'],
            'Conservantes/Sulfitos' => ['sulfito', 'dióxido de azufre', 'sulfite', 'so2']
        ];
        
        $alergenos_encontrados = [];
        
        foreach ($mapeo_alergenos as $alergeno => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($texto, $keyword) !== false) {
                    $alergenos_encontrados[] = $alergeno;
                    break;
                }
            }
        }
        
        return array_unique($alergenos_encontrados);
    }
    
    /**
     * Detectar alérgenos en ingredientes
     */
    private function detectarAlergenosEnIngredientes($ingredientes) {
        return $this->mapearAlergenos($ingredientes);
    }
    
    /**
     * Hacer petición HTTP
     */
    private function hacerPeticion($url) {
        $options = [
            'http' => [
                'method' => 'GET',
                'timeout' => $this->timeout,
                'header' => 'User-Agent: DeteccionAlergenos/1.0'
            ]
        ];
        
        $context = stream_context_create($options);
        
        try {
            $response = file_get_contents($url, false, $context);
            return json_decode($response, true);
        } catch (Exception $e) {
            return null;
        }
    }
}
?>
