<?php
header('Content-Type: application/json');

// Lista de alergenos comunes
$alergenos_comunes = [
    ['id' => 1, 'nombre' => 'Gluten', 'descripcion' => 'Proteína presente en cereales como trigo, cebada y centeno'],
    ['id' => 2, 'nombre' => 'Lactosa', 'descripcion' => 'Azúcar presente en la leche y productos lácteos'],
    ['id' => 3, 'nombre' => 'Frutos Secos', 'descripcion' => 'Incluye nueces, almendras, pistachos, etc.'],
    ['id' => 4, 'nombre' => 'Maní/Cacahuate', 'descripcion' => 'Legumbre, diferente de los frutos secos'],
    ['id' => 5, 'nombre' => 'Huevo', 'descripcion' => 'Alérgeno común presente en muchos alimentos'],
    ['id' => 6, 'nombre' => 'Pescado', 'descripcion' => 'Incluye todos los tipos de pescado'],
    ['id' => 7, 'nombre' => 'Crustáceos', 'descripcion' => 'Camarones, cangrejos, langostas, etc.'],
    ['id' => 8, 'nombre' => 'Soja', 'descripcion' => 'Legumbre utilizada en muchos alimentos procesados'],
    ['id' => 9, 'nombre' => 'Mostaza', 'descripcion' => 'Especias y condimento'],
    ['id' => 10, 'nombre' => 'Ajonjolí/Sésamo', 'descripcion' => 'Semillas utilizadas como condimento'],
    ['id' => 11, 'nombre' => 'Moluscos', 'descripcion' => 'Calamares, mejillones, ostras, etc.'],
    ['id' => 12, 'nombre' => 'Conservantes/Sulfitos', 'descripcion' => 'Aditivos utilizados en conservación de alimentos']
];

$respuesta = [
    'exito' => true,
    'alergenos' => $alergenos_comunes
];

echo json_encode($respuesta);
?>
