<?php
// api/obtener_alergenos_comunes.php - Lista de alérgenos comunes

header('Content-Type: application/json');

$alergenos_comunes = [
    [
        'nombre' => 'Gluten',
        'descripcion' => 'Proteína presente en trigo, cebada, centeno'
    ],
    [
        'nombre' => 'Lactosa',
        'descripcion' => 'Azúcar presente en productos lácteos'
    ],
    [
        'nombre' => 'Frutos Secos',
        'descripcion' => 'Incluye almendras, nueces, pistachos, avellanas'
    ],
    [
        'nombre' => 'Maní/Cacahuate',
        'descripcion' => 'Legumbre con alto riesgo alergénico'
    ],
    [
        'nombre' => 'Huevo',
        'descripcion' => 'Alérgeno común en productos de panadería'
    ],
    [
        'nombre' => 'Pescado',
        'descripcion' => 'Alérgeno presente en productos del mar'
    ],
    [
        'nombre' => 'Crustáceos',
        'descripcion' => 'Camarones, cangrejos, langostas'
    ],
    [
        'nombre' => 'Soja',
        'descripcion' => 'Alérgeno presente en muchos productos procesados'
    ],
    [
        'nombre' => 'Mostaza',
        'descripcion' => 'Condimento alergénico'
    ],
    [
        'nombre' => 'Ajonjolí/Sésamo',
        'descripcion' => 'Semilla alergénica'
    ],
    [
        'nombre' => 'Moluscos',
        'descripcion' => 'Incluye almejas, mejillones, ostras'
    ],
    [
        'nombre' => 'Conservantes/Sulfitos',
        'descripcion' => 'Aditivos alimentarios'
    ]
];

echo json_encode([
    'exito' => true,
    'alergenos' => $alergenos_comunes,
    'total' => count($alergenos_comunes)
]);
?>
